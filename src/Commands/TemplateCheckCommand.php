<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Commands;

use Imagewize\PtCli\Compliance\ComplianceChecker;
use Imagewize\PtCli\Compliance\Config\ConfigLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Check HTML template and template-part files for block-validation drift.
 *
 * This is the third step in Elayne's two-pass validation workflow:
 *
 *   Pass 1 — WP-CLI structural validator (requires Trellis VM)
 *     wp pattern validate templates/ --fix
 *
 *   Pass 2 — PHP pattern compliance checker (host, no WordPress)
 *     pt-cli check patterns/ --theme=elayne
 *
 *   Pass 3 — HTML template compliance checker (host, no WordPress)
 *     pt-cli check:templates templates/ --theme=elayne
 *     pt-cli check:templates parts/    --theme=elayne
 *
 * Checks HTML files for issues PHP compliance cannot detect:
 *   - taxQuery:{} (object) must be taxQuery:[] (array)
 *   - WooCommerce filter sub-blocks missing HTML div wrappers (WC 9.x+ change)
 *   - woocommerce/product-filters missing CSS custom property style attribute
 *   - wp:template-part blocks missing "theme" attribute
 *   - Unbalanced HTML tags
 */
class TemplateCheckCommand extends Command
{
    protected static $defaultName = 'check:templates';

    protected function configure(): void
    {
        $this
            ->setName('check:templates')
            ->setDescription('Check HTML template and template-part files for block-validation drift')
            ->setHelp(<<<'HELP'
The <info>check:templates</info> command scans <comment>.html</comment> files in the given
directory (typically <comment>templates/</comment> or <comment>parts/</comment>) for issues that
the PHP pattern compliance checker cannot detect.

<comment>Why a separate command?</comment>
  PHP pattern compliance (<info>pt-cli check</info>) only processes <comment>*.php</comment> files.
  WP-CLI structural validation uses server-side PHP parse_blocks(), which is too
  lenient to catch client-side JavaScript save() function mismatches.
  This command fills that gap for HTML template files.

<comment>What it detects:</comment>
  • taxQuery:{} (object) — must be [] (array)
  • WooCommerce filter blocks as self-closing or missing div wrappers
  • woocommerce/product-filters div missing CSS custom property styles
  • wp:template-part blocks missing "theme" attribute (multisite safety)
  • Unbalanced HTML tags

<comment>Autofix</comment> (--autofix) can repair:
  • taxQuery:{} → taxQuery:[]
  • Missing "theme" attribute on wp:template-part blocks

<comment>Usage:</comment>
  pt-cli check:templates demo/web/app/themes/elayne/templates/ --theme=elayne
  pt-cli check:templates demo/web/app/themes/elayne/parts/     --theme=elayne
  pt-cli check:templates path/to/single-template.html         --theme=elayne
  pt-cli check:templates templates/ --theme=elayne --autofix
HELP)
            ->addArgument('path', InputArgument::REQUIRED, 'Path to an HTML template file or directory containing .html files')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme config to use (e.g. elayne)', 'base')
            ->addOption('autofix', null, InputOption::VALUE_NONE, 'Apply mechanical autofixes (taxQuery, template-part theme)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $path    = $input->getArgument('path');
        $theme   = $input->getOption('theme');
        $autofix = (bool) $input->getOption('autofix');

        if (!file_exists($path)) {
            $io->error("Path not found: {$path}");
            return Command::FAILURE;
        }

        $loader     = new ConfigLoader();
        $projectDir = is_dir($path) ? $path : dirname($path);
        $config     = $loader->load($theme, $projectDir);
        $checker    = new ComplianceChecker($config);

        if (is_dir($path)) {
            $results = $checker->checkTemplateDirectory($path, $autofix);
            if (empty($results)) {
                $io->warning("No .html files found in: {$path}");
                return Command::SUCCESS;
            }
        } else {
            if (!str_ends_with($path, '.html')) {
                $io->error("File must be a .html template file: {$path}");
                return Command::FAILURE;
            }
            $results = [basename($path) => $checker->checkTemplateFile($path, $autofix)];
        }

        return $this->renderResults($io, $results);
    }

    private function renderResults(SymfonyStyle $io, array $results): int
    {
        $passed   = [];
        $issues   = [];
        $allFixed = [];

        foreach ($results as $filename => $result) {
            if (!empty($result['fixed'])) {
                $allFixed[$filename] = $result['fixed'];
            }
            if (empty($result['violations'])) {
                $passed[] = $filename;
            } else {
                $issues[$filename] = $result['violations'];
            }
        }

        if (!empty($allFixed)) {
            $io->section('AUTOFIXED (' . count($allFixed) . ' templates)');
            foreach ($allFixed as $filename => $appliedFixes) {
                $io->text('  <info>' . $filename . '</info>');
                foreach ($appliedFixes as $fix) {
                    $io->text('    • ' . $fix);
                }
            }
        }

        $total = count($passed) + count($issues);
        $io->section('PASSED (' . count($passed) . ' / ' . $total . ' templates)');
        foreach ($passed as $filename) {
            $io->text('  <info>✓</info> ' . $filename);
        }

        if (!empty($issues)) {
            $io->section('ISSUES FOUND (' . count($issues) . ' templates)');
            foreach ($issues as $filename => $violations) {
                $io->text('<comment>' . $filename . '</comment>');
                foreach ($violations as $violation) {
                    $severity = $violation['severity'] ?? 'error';
                    $marker   = $severity === 'error' ? '<error>✗</error>' : '<comment>⚠</comment>';
                    $lineInfo = $violation['line'] !== null ? " (line {$violation['line']})" : '';
                    $io->text('  ' . $marker . ' [' . $violation['rule'] . ']' . $lineInfo . ' ' . $violation['message']);
                }
                $io->newLine();
            }

            $io->note('Run with --autofix to repair taxQuery and template-part theme issues automatically.');
            return Command::FAILURE;
        }

        $io->success('All templates passed compliance checks!');
        return Command::SUCCESS;
    }
}
