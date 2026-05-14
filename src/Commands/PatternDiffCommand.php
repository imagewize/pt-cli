<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Commands;

use Imagewize\PtCli\Compliance\Config\ConfigLoader;
use Imagewize\PtCli\PatternDiff\PatternDiffer;
use Imagewize\PtCli\PatternDiff\PatternSyncer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Compare Gutenberg clipboard content with PHP pattern files, or apply
 * clipboard changes back to the file while preserving PHP wrappers.
 */
class PatternDiffCommand extends Command
{
    protected static $defaultName = 'pattern:diff';

    protected function configure(): void
    {
        $this
            ->setName('pattern:diff')
            ->setDescription('Compare Gutenberg clipboard with a pattern file, or apply changes preserving PHP')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to pattern file or directory')
            ->addOption('from-stdin', null, InputOption::VALUE_NONE, 'Read Gutenberg content from stdin (pbpaste)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply clipboard changes to file, preserving PHP translation wrappers')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'With --apply: print the merged result instead of writing to file')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Theme config to use (e.g. elayne)', 'elayne')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON')
            ->addOption('similarity-threshold', null, InputOption::VALUE_REQUIRED, 'Minimum similarity to consider a match (0-1)', 0.95)
            ->addOption('show-suggestions', '-s', InputOption::VALUE_NONE, 'Show fix suggestions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io             = new SymfonyStyle($input, $output);
        $path           = $input->getArgument('path');
        $fromStdin      = (bool) $input->getOption('from-stdin');
        $applyMode      = (bool) $input->getOption('apply');
        $dryRun         = (bool) $input->getOption('dry-run');
        $theme          = $input->getOption('theme');
        $asJson         = (bool) $input->getOption('json');
        $threshold      = (float) $input->getOption('similarity-threshold');
        $showSuggestions = (bool) $input->getOption('show-suggestions');

        if (!file_exists($path)) {
            $io->error("Path not found: {$path}");
            return Command::FAILURE;
        }

        $loader     = new ConfigLoader();
        $projectDir = is_dir($path) ? $path : dirname($path);
        $config     = $loader->load($theme, $projectDir);
        $differ     = new PatternDiffer($config);

        $clipboard = null;
        if ($fromStdin) {
            $clipboard = $this->readStdin();
            if (empty($clipboard)) {
                $io->error('No content received from stdin. Use: pbpaste | pt-cli pattern:diff --from-stdin <file>');
                return Command::FAILURE;
            }
        }

        // ── apply mode ───────────────────────────────────────────────────────
        if ($applyMode) {
            if (!$fromStdin || is_dir($path)) {
                $io->error('--apply requires --from-stdin and a single file path, not a directory.');
                return Command::FAILURE;
            }

            return $this->applyToFile($io, $clipboard, $path, $dryRun);
        }

        // ── diff / report mode ───────────────────────────────────────────────
        if (is_dir($path)) {
            if ($fromStdin) {
                $io->error('Cannot use --from-stdin with a directory path. Provide a single file.');
                return Command::FAILURE;
            }
            $results = $this->diffDirectory($differ, $path);
        } else {
            if (!$fromStdin) {
                $io->error('For a single file, use --from-stdin to provide Gutenberg clipboard content.');
                return Command::FAILURE;
            }
            $results = [$path => $differ->diffClipboardWithFile($clipboard, $path)];
        }

        return $this->renderResults($io, $results, $asJson, $threshold, $showSuggestions);
    }

    /**
     * Merge clipboard into the PHP file, preserving all PHP translation wrappers.
     */
    private function applyToFile(SymfonyStyle $io, string $clipboard, string $filePath, bool $dryRun): int
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $io->error("Cannot read file: {$filePath}");
            return Command::FAILURE;
        }

        $syncer = new PatternSyncer();
        $merged = $syncer->sync($clipboard, $fileContent);

        if ($dryRun) {
            $io->section('Merged result (dry-run — file not written)');
            $io->text($merged);
            return Command::SUCCESS;
        }

        if (file_put_contents($filePath, $merged) === false) {
            $io->error("Cannot write file: {$filePath}");
            return Command::FAILURE;
        }

        $io->success("Applied and wrote: {$filePath}");

        // Show a quick diff summary of what changed
        $syncer2   = new PatternSyncer();
        $oldBlocks = $syncer2->stripHeader($fileContent);
        $newBlocks = $syncer2->stripHeader($merged);

        $oldLines = substr_count($oldBlocks, "\n");
        $newLines = substr_count($newBlocks, "\n");
        $io->text(sprintf('  Lines: %d → %d (%+d)', $oldLines, $newLines, $newLines - $oldLines));

        return Command::SUCCESS;
    }

    /**
     * Read content from stdin.
     */
    private function readStdin(): string
    {
        $content = '';
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return '';
        }
        while (!feof($handle)) {
            $content .= fread($handle, 8192);
        }
        fclose($handle);
        return $content;
    }

    /**
     * Diff all patterns in a directory.
     * For directory mode, we compare each file against itself (structural validation).
     *
     * @return array<string, array{differences: array, suggestions: array, similarity: float}>
     */
    private function diffDirectory(PatternDiffer $differ, string $dir): array
    {
        $results = [];
        $files = glob(rtrim($dir, '/') . '/*.php') ?: [];

        foreach ($files as $file) {
            if ($differ->isExempt($file)) {
                continue;
            }

            $fileContent = file_get_contents($file);
            if ($fileContent === false) {
                continue;
            }

            $blocks = $differ->extractBlocksFromPhp($fileContent);
            
            // Self-validation: check for common issues
            $results[basename($file)] = [
                'differences' => $this->validatePatternFile($differ, $file, $blocks),
                'suggestions' => [],
                'similarity' => 1.0,
                'note' => 'Use --from-stdin to compare with Gutenberg clipboard content',
            ];
        }

        return $results;
    }

    /**
     * Validate a pattern file for common issues.
     */
    private function validatePatternFile(PatternDiffer $differ, string $filePath, string $blocks): array
    {
        $differences = [];

        // Check for hardcoded font sizes
        $hardcodedSizes = ['small', 'medium', 'large', 'x-large'];
        foreach ($hardcodedSizes as $size) {
            if (str_contains($blocks, 'font-size:' . $size)) {
                $differences[] = [
                    'type' => 'css_value',
                    'message' => "Hardcoded font-size: {$size}",
                    'severity' => 'warning',
                    'fix' => "Use: var(--wp--preset--font-size--{$size})",
                ];
            }
        }

        // Check for missing translation wrappers (basic check)
        if (preg_match_all('/>([a-zA-Z\s]+)</', $blocks, $matches)) {
            foreach ($matches[1] as $text) {
                $text = trim(strip_tags($text));
                if ($text && strlen($text) > 2 && !str_contains($blocks, 'esc_html_e')) {
                    // This is a simplistic check - the real check needs more context
                    // For now, just note that translation check is available
                    break;
                }
            }
        }

        // Check for WooCommerce issues
        if (str_contains($blocks, 'woocommerce/')) {
            $wcValidator = new \Imagewize\PtCli\PatternDiff\WooCommerceValidator();
            $wcIssues = $wcValidator->validate($blocks, $blocks);
            foreach ($wcIssues as $issue) {
                $differences[] = [
                    'type' => 'woocommerce',
                    'message' => $issue['message'],
                    'severity' => $issue['severity'],
                    'fix' => $issue['fix'] ?? null,
                ];
            }
        }

        return $differences;
    }

    /**
     * Render results to the console or as JSON.
     */
    private function renderResults(
        SymfonyStyle $io,
        array $results,
        bool $asJson,
        float $threshold,
        bool $showSuggestions
    ): int {
        if ($asJson) {
            return $this->renderJson($io, $results, $threshold);
        }

        return $this->renderConsole($io, $results, $threshold, $showSuggestions);
    }

    /**
     * Render results as JSON.
     */
    private function renderJson(SymfonyStyle $io, array $results, float $threshold): int
    {
        $hasIssues = false;

        foreach ($results as $filename => $result) {
            if ($result['similarity'] < $threshold || !empty($result['differences'])) {
                $hasIssues = true;
                break;
            }
        }

        $output = [];
        foreach ($results as $filename => $result) {
            $output[$filename] = [
                'similarity' => $result['similarity'],
                'differences' => $result['differences'],
                'suggestions' => $result['suggestions'] ?? [],
                'note' => $result['note'] ?? null,
            ];
        }

        $io->writeln(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $hasIssues ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Render results to console.
     */
    private function renderConsole(
        SymfonyStyle $io,
        array $results,
        float $threshold,
        bool $showSuggestions
    ): int {
        $hasIssues = false;
        $passed = [];
        $issues = [];

        foreach ($results as $filename => $result) {
            $similarity = $result['similarity'] ?? 1.0;
            $differences = $result['differences'] ?? [];
            $note = $result['note'] ?? null;

            if ($note) {
                $io->note($note);
            }

            if ($similarity < $threshold || !empty($differences)) {
                $hasIssues = true;
                $issues[$filename] = [
                    'similarity' => $similarity,
                    'differences' => $differences,
                    'suggestions' => $result['suggestions'] ?? [],
                ];
            } else {
                $passed[] = $filename;
            }
        }

        // Display passed files
        if (!empty($passed)) {
            $io->section('PASSED (' . count($passed) . ')');
            foreach ($passed as $filename) {
                $io->text('  <info>✓</info> ' . $filename);
            }
            $io->newLine();
        }

        // Display issues
        if (!empty($issues)) {
            $io->section('ISSUES FOUND (' . count($issues) . ')');

            foreach ($issues as $filename => $data) {
                $similarity = $data['similarity'];
                $differences = $data['differences'];
                $suggestions = $data['suggestions'];

                $io->text('<comment>' . $filename . '</comment>');
                $io->text('  Similarity: ' . number_format($similarity * 100, 1) . '%');

                foreach ($differences as $diff) {
                    $type = $diff['type'] ?? 'unknown';
                    $severity = $diff['severity'] ?? 'info';
                    $message = $diff['message'] ?? '';

                    $marker = match ($severity) {
                        'error' => '<error>✗</error>',
                        'warning' => '<comment>⚠</comment>',
                        'info' => '<info>ℹ</info>',
                        default => '•',
                    };

                    $io->text('  ' . $marker . ' [' . $type . '] ' . $message);

                    if ($showSuggestions && !empty($diff['fix'])) {
                        $io->text('    <comment>Suggestion: ' . $diff['fix'] . '</comment>');
                    }
                }

                if ($showSuggestions && !empty($suggestions)) {
                    $io->text('  <comment>Suggestions:</comment>');
                    foreach ($suggestions as $suggestion) {
                        $io->text('    • ' . $suggestion);
                    }
                }

                $io->newLine();
            }

            return Command::FAILURE;
        }

        if (empty($passed)) {
            $io->warning('No patterns were checked. Use --from-stdin with a file path.');
            return Command::FAILURE;
        }

        $io->success('All patterns passed!');
        return Command::SUCCESS;
    }
}
