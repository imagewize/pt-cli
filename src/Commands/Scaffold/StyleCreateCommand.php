<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Commands\Scaffold;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class StyleCreateCommand extends Command
{
    private const VERTICALS = [
        'custom'        => 'Custom — enter your own colors',
        'legal'         => 'Legal — navy blue + gold',
        'plumbing'      => 'Plumbing — dark blue + orange',
        'spa'           => 'Spa & Wellness — sage green + sand',
        'food-beverage' => 'Food & Beverage — burgundy + gold',
    ];

    private const PRESET_PALETTES = [
        'legal' => [
            'primary'           => '#1E3A8A',
            'primary-accent'    => '#3B82F6',
            'primary-alt'       => '#1E40AF',
            'primary-alt-accent'=> '#D4AF37',
        ],
        'plumbing' => [
            'primary'           => '#1E4D8C',
            'primary-accent'    => '#F97316',
            'primary-alt'       => '#1E3A5F',
            'primary-alt-accent'=> '#FF6B2B',
        ],
        'spa' => [
            'primary'           => '#2E7D6B',
            'primary-accent'    => '#A8C5B8',
            'primary-alt'       => '#1A5C4E',
            'primary-alt-accent'=> '#D4A574',
        ],
        'food-beverage' => [
            'primary'           => '#8B2635',
            'primary-accent'    => '#D4A017',
            'primary-alt'       => '#5C1C27',
            'primary-alt-accent'=> '#F5E6C8',
        ],
    ];

    protected function configure(): void
    {
        $this
            ->setName('style:create')
            ->setDescription('Scaffold a WordPress theme style variation JSON for a new vertical')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Style variation display name (e.g. "Ocean Legal")')
            ->addOption('vertical', null, InputOption::VALUE_REQUIRED, 'Preset vertical (' . implode(', ', array_keys(self::VERTICALS)) . ')')
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Output directory (default: ./styles/)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        // Style name
        $name = $input->getOption('name');
        if (!$name) {
            $question = new Question('<info>Style name</info> (e.g. "Ocean Legal"): ');
            $question->setValidator(static function ($value) {
                if (empty(trim((string) $value))) {
                    throw new \RuntimeException('Style name cannot be empty.');
                }
                return $value;
            });
            $name = $helper->ask($input, $output, $question);
        }

        // Slug derived from name
        $slug = $this->nameToSlug((string) $name);

        // Vertical selection
        $vertical = $input->getOption('vertical');
        if (!$vertical) {
            $choices = array_map(
                static fn($k, $v) => "{$k} — {$v}",
                array_keys(self::VERTICALS),
                array_values(self::VERTICALS)
            );
            $question = new ChoiceQuestion('<info>Select vertical</info>:', $choices, 0);
            $answer = $helper->ask($input, $output, $question);
            $vertical = explode(' — ', $answer)[0];
        }

        // Resolve preset colors (or prompt for custom)
        $preset = self::PRESET_PALETTES[$vertical] ?? null;
        $defaults = $preset ?? [
            'primary'           => '#000000',
            'primary-accent'    => '#333333',
            'primary-alt'       => '#111111',
            'primary-alt-accent'=> '#666666',
        ];

        $io->newLine();
        if ($preset) {
            $io->writeln("<comment>Preset colors loaded for \"{$vertical}\". Press Enter to accept each default or type a new hex value.</comment>");
        } else {
            $io->writeln('<comment>Enter hex color values for your custom palette (e.g. #1E3A8A).</comment>');
        }
        $io->newLine();

        $colors = [];
        $colorFields = [
            'primary'           => 'Primary brand color',
            'primary-accent'    => 'Primary accent (lighter/complementary)',
            'primary-alt'       => 'Primary alt (darker variant)',
            'primary-alt-accent'=> 'Primary alt accent (highlight/contrast)',
        ];

        foreach ($colorFields as $key => $label) {
            $default = $defaults[$key];
            $question = new Question("<info>{$label}</info> [<comment>{$default}</comment>]: ", $default);
            $question->setValidator(static function ($value) use ($key) {
                $value = trim((string) $value);
                if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    throw new \RuntimeException("Invalid hex color for {$key}: \"{$value}\". Use format #RRGGBB.");
                }
                return strtoupper($value);
            });
            $colors[$key] = $helper->ask($input, $output, $question);
        }

        // Output directory
        $outputDir = $input->getOption('output-dir');
        if (!$outputDir) {
            $cwd = (string) getcwd();
            $outputDir = is_dir($cwd . '/styles') ? $cwd . '/styles' : $cwd;
        }

        if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            $io->error("Could not create output directory: {$outputDir}");
            return Command::FAILURE;
        }

        $json = $this->buildStyleJson((string) $name, $colors);
        $filename = rtrim($outputDir, '/') . '/' . $slug . '.json';

        if (file_exists($filename)) {
            $question = new Question("<comment>File already exists:</comment> {$filename}\n<info>Overwrite?</info> [y/N]: ", 'n');
            $confirm = $helper->ask($input, $output, $question);
            if (strtolower(trim((string) $confirm)) !== 'y') {
                $io->warning('Aborted.');
                return Command::SUCCESS;
            }
        }

        file_put_contents($filename, $json);

        $io->success("Style variation created: {$filename}");
        $io->note([
            'Title: ' . $name,
            'Slug: ' . $slug,
            'Next: copy to your theme\'s styles/ directory and activate via Appearance → Editor → Styles.',
        ]);

        return Command::SUCCESS;
    }

    private function buildStyleJson(string $title, array $colors): string
    {
        $primary           = $colors['primary'];
        $primaryAccent     = $colors['primary-accent'];
        $primaryAlt        = $colors['primary-alt'];
        $primaryAltAccent  = $colors['primary-alt-accent'];

        $data = [
            '$schema' => 'https://schemas.wp.org/trunk/theme.json',
            'version' => 3,
            'title'   => $title,
            'settings' => [
                'color' => [
                    'defaultDuotone'   => false,
                    'defaultGradients' => false,
                    'defaultPalette'   => false,
                    'duotone' => [
                        ['name' => 'Brand',         'slug' => 'primary',       'colors' => [$primary, '#FDFBF7']],
                        ['name' => 'Accent',        'slug' => 'secondary',     'colors' => [$primaryAccent, '#FDFBF7']],
                        ['name' => 'High Contrast', 'slug' => 'high-contrast', 'colors' => ['#000000', '#ffffff']],
                    ],
                    'gradients' => [
                        ['name' => 'Brand Dark',    'slug' => 'brand-dark',    'gradient' => "linear-gradient(135deg, {$primary}, {$primaryAlt})"],
                        ['name' => 'Brand Shine',   'slug' => 'brand-shine',   'gradient' => "linear-gradient(135deg, {$primaryAccent}, {$primaryAltAccent})"],
                        ['name' => 'Professional',  'slug' => 'professional',  'gradient' => "linear-gradient(135deg, {$primary}, #1F2937)"],
                        ['name' => 'Main Diagonal', 'slug' => 'main-diagonal', 'gradient' => 'linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(31, 41, 55, 0.85))'],
                    ],
                    'palette' => [
                        ['name' => 'Brand',            'slug' => 'primary',            'color' => $primary],
                        ['name' => 'Brand Accent',     'slug' => 'primary-accent',     'color' => $primaryAccent],
                        ['name' => 'Brand Alt',        'slug' => 'primary-alt',        'color' => $primaryAlt],
                        ['name' => 'Brand Alt Accent', 'slug' => 'primary-alt-accent', 'color' => $primaryAltAccent],
                        ['name' => 'Contrast',         'slug' => 'main',               'color' => '#1F2937'],
                        ['name' => 'Contrast Accent',  'slug' => 'main-accent',        'color' => '#6B7280'],
                        ['name' => 'Base',             'slug' => 'base',               'color' => '#FDFBF7'],
                        ['name' => 'Base Accent',      'slug' => 'secondary',          'color' => $primaryAltAccent],
                        ['name' => 'Tint',             'slug' => 'tertiary',           'color' => '#F3F4F6'],
                        ['name' => 'Border Light',     'slug' => 'border-light',       'color' => '#E5E7EB'],
                        ['name' => 'Border Dark',      'slug' => 'border-dark',        'color' => '#D1D5DB'],
                    ],
                ],
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    private function nameToSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = (string) preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = (string) preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}
