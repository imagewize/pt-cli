<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Commands\Scaffold;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class LayoutCreateCommand extends Command
{
    private const CATEGORIES = [
        'elayne/pages',
        'elayne/hero',
        'elayne/features',
        'elayne/call-to-action',
        'elayne/posts',
        'elayne/woocommerce',
        'header',
        'footer',
    ];

    private const LAYOUTS = [
        'full-width'       => 'Single column, constrained — simplest starting point',
        'two-column'       => '50/50 columns block',
        'three-column'     => 'Grid with 3 equal groups',
        'sidebar-left'     => 'Narrow left sidebar (33%) + wide content area (66%)',
        'sidebar-right'    => 'Wide content area (66%) + narrow right sidebar (33%)',
        'hero-image-left'  => 'Cover image left + heading, text, CTA right',
        'hero-image-right' => 'Heading, text, CTA left + cover image right',
        'landing-page'     => 'Hero + 3-column features + CTA — no header/footer wrapper',
    ];

    protected function configure(): void
    {
        $this
            ->setName('layout:create')
            ->setDescription('Scaffold a new Elayne block layout pattern')
            ->addArgument('slug', InputArgument::OPTIONAL, 'Layout slug (without elayne/ prefix)')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Pattern title')
            ->addOption('slug', null, InputOption::VALUE_REQUIRED, 'Pattern slug (with or without elayne/ prefix)')
            ->addOption('layout', 'l', InputOption::VALUE_REQUIRED, 'Layout skeleton (' . implode(', ', array_keys(self::LAYOUTS)) . ')')
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Pattern category')
            ->addOption('keywords', 'k', InputOption::VALUE_REQUIRED, 'Comma-separated keywords')
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Output directory (default: ./patterns/ or ./)')
            ->addOption('shell-only', null, InputOption::VALUE_NONE, 'Generate PHP header + paste marker only — no block markup (editor-first workflow)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        // Title
        $title = $input->getOption('title');
        if (!$title) {
            $question = new Question('<info>Pattern title</info>: ');
            $question->setValidator(static function ($value) {
                if (empty(trim((string) $value))) {
                    throw new \RuntimeException('Title cannot be empty.');
                }
                return $value;
            });
            $title = $helper->ask($input, $output, $question);
        }

        // Slug — --slug option takes priority over positional argument
        $slug = $input->getOption('slug') ?? $input->getArgument('slug');
        if (!$slug) {
            $defaultSlug = $this->titleToSlug($title);
            $question = new Question("<info>Pattern slug</info> [<comment>{$defaultSlug}</comment>]: ", $defaultSlug);
            $slug = $helper->ask($input, $output, $question);
        }
        $slug = (string) $slug;
        if (str_starts_with($slug, 'elayne/')) {
            $slug = substr($slug, 7);
        }
        $slug = $this->titleToSlug($slug);

        // Layout
        $layout = $input->getOption('layout');
        if (!$layout) {
            $layoutChoices = array_keys(self::LAYOUTS);
            $descriptions  = array_values(self::LAYOUTS);
            $labelled = array_map(
                static fn($k, $v) => "{$k} — {$v}",
                $layoutChoices,
                $descriptions
            );
            $question = new ChoiceQuestion('<info>Select layout</info>:', $labelled, 0);
            $answer   = $helper->ask($input, $output, $question);
            $layout   = explode(' — ', $answer)[0];
        }

        // Category
        $category = $input->getOption('category');
        if (!$category) {
            $question = new ChoiceQuestion('<info>Select category</info>:', self::CATEGORIES, 0);
            $category = $helper->ask($input, $output, $question);
        }

        // Keywords
        $keywords = $input->getOption('keywords');
        if ($keywords === null) {
            $question = new Question('<info>Keywords</info> (comma-separated, optional): ', '');
            $keywords = $helper->ask($input, $output, $question);
        }

        // Output directory
        $outputDir = $input->getOption('output-dir');
        if (!$outputDir) {
            $cwd = (string) getcwd();
            $outputDir = is_dir($cwd . '/patterns') ? $cwd . '/patterns' : $cwd;
        }

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            $io->error("Could not create output directory: {$outputDir}");
            return Command::FAILURE;
        }

        $shellOnly = (bool) $input->getOption('shell-only');
        $content = $this->buildLayout($title, $slug, $category, (string) $keywords, $layout, $shellOnly);

        $filename = rtrim($outputDir, '/') . '/' . $slug . '.php';

        if (file_exists($filename)) {
            $question = new Question("<comment>File already exists:</comment> {$filename}\n<info>Overwrite?</info> [y/N]: ", 'n');
            $confirm  = $helper->ask($input, $output, $question);
            if (strtolower(trim((string) $confirm)) !== 'y') {
                $io->warning('Aborted.');
                return Command::SUCCESS;
            }
        }

        file_put_contents($filename, $content);

        $io->success("Layout created: {$filename}");
        $notes = [
            'Slug: elayne/' . $slug,
            'Category: ' . $category,
            'Layout: ' . $layout,
        ];
        if ($shellOnly) {
            $notes[] = 'Editor-first: build in WP editor → Copy all blocks → replace the PASTE BLOCKS HERE comment, then flush WP cache.';
        } else {
            $notes[] = 'Next: replace placeholder text and images, then flush WP cache.';
        }
        $io->note($notes);

        return Command::SUCCESS;
    }

    private function buildLayout(string $title, string $slug, string $category, string $keywords, string $layout, bool $shellOnly): string
    {
        if ($shellOnly) {
            return $this->buildShellTemplate($title, $slug, $category, $keywords);
        }

        $layoutPath = __DIR__ . '/../../../layouts/' . $layout . '.php';

        if (!file_exists($layoutPath)) {
            return $this->buildShellTemplate($title, $slug, $category, $keywords);
        }

        $content = (string) file_get_contents($layoutPath);
        $content = str_replace('TODO: Pattern Title', $title, $content);
        $content = str_replace('elayne/TODO-slug', 'elayne/' . $slug, $content);
        $content = str_replace('elayne/TODO-category', $category, $content);
        $content = str_replace('TODO: One-line description', $title, $content);
        $content = str_replace('TODO keyword1, keyword2', $keywords ?: $slug, $content);

        return $content;
    }

    private function buildShellTemplate(string $title, string $slug, string $category, string $keywords): string
    {
        $keywordsLine = $keywords ?: $slug;

        return <<<PHP
<?php
/**
 * Title: {$title}
 * Slug: elayne/{$slug}
 * Description: {$title}
 * Categories: {$category}
 * Keywords: {$keywordsLine}
 * Viewport Width: 1200
 * Block Types: core/group
 */
?>
<!-- PASTE BLOCKS HERE: Build in WP editor → Copy all blocks → replace this comment -->

PHP;
    }

    private function titleToSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = (string) preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = (string) preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
