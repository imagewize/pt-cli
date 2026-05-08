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

class CheckCommand extends Command
{
    protected static $defaultName = 'check';

    protected function configure(): void
    {
        $this
            ->setName('check')
            ->setDescription('Check pattern files for compliance')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to pattern file or directory')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme config to use (e.g. elayne)', 'base')
            ->addOption('autofix', null, InputOption::VALUE_NONE, 'Apply mechanical autofixes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');
        $theme = $input->getOption('theme');
        $autofix = (bool) $input->getOption('autofix');

        if (!file_exists($path)) {
            $io->error("Path not found: {$path}");
            return Command::FAILURE;
        }

        $loader = new ConfigLoader();
        $projectDir = is_dir($path) ? $path : dirname($path);
        $config = $loader->load($theme, $projectDir);

        $checker = new ComplianceChecker($config);

        if (is_dir($path)) {
            $results = $checker->checkDirectory($path, $autofix);
        } else {
            $results = [basename($path) => $checker->checkFile($path, $autofix)];
        }

        return $this->renderResults($io, $results);
    }

    private function renderResults(SymfonyStyle $io, array $results): int
    {
        $passed = [];
        $issues = [];
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
            $io->section('AUTOFIXED (' . count($allFixed) . ' patterns)');
            foreach ($allFixed as $filename => $appliedFixes) {
                $io->text('  <info>' . $filename . '</info>');
                foreach ($appliedFixes as $fix) {
                    $io->text('    • ' . $fix);
                }
            }
        }

        $io->section('PASSED (' . count($passed) . ' patterns)');
        foreach ($passed as $filename) {
            $io->text('  <info>✓</info> ' . $filename);
        }

        if (!empty($issues)) {
            $io->section('ISSUES FOUND (' . count($issues) . ' patterns)');
            foreach ($issues as $filename => $violations) {
                $io->text('<comment>' . $filename . '</comment>');
                foreach ($violations as $violation) {
                    $severity = $violation['severity'] ?? 'error';
                    $marker = $severity === 'error' ? '<error>✗</error>' : '<comment>⚠</comment>';
                    $io->text('  ' . $marker . ' ' . $violation['message']);
                }
                $io->newLine();
            }
            return Command::FAILURE;
        }

        $io->success('All patterns passed compliance checks!');
        return Command::SUCCESS;
    }
}
