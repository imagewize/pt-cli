<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Commands\Scaffold;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PatternListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('pattern:list')
            ->setDescription('List available pattern templates, snippets, and categories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('pt-cli');

        $io->section('Templates  (use with: pt-cli pattern:create --template=<name>)');
        $io->table(
            ['Name', 'Description'],
            [
                ['blank',                       'Empty pattern with header only'],
                ['hero-cover',                  'Full-bleed wp:cover with bottom-center content'],
                ['cta-fullwidth',               'Full-width call-to-action band'],
                ['feature-grid-3col',           'Full-width section with 3 feature cards'],
                ['stats-bar-fullwidth',         'Dark full-width stats/numbers bar'],
                ['two-column-text-image',       'Text left, image right two-column layout'],
                ['header-standard',             'Standard header — logo, navigation, social links'],
                ['footer-standard',             'Standard footer — brand blurb, nav columns, subnav'],
                ['testimonials-grid',           'Responsive testimonial card grid with reviewer info'],
                ['pricing-comparison',          'Three-tier pricing table with elevated recommended card'],
                ['blog-post-columns',           'wp:query-driven 3-column post grid (portrait images)'],
                ['team-grid',                   'Team member profile grid — photo, name, title, bio'],
                // WooCommerce store templates
                ['woo-hero',                    'WooCommerce — two-column hero: text + CTA left, decorative cover right'],
                ['woo-ticker',                  'WooCommerce — server-rendered marquee ticker bar (needs render_block filter)'],
                ['woo-shop-categories',         'WooCommerce — CSS bento grid: one large featured card + four smaller cards'],
                ['woo-featured-products',       'WooCommerce — section header with View All + product-collection 4-col grid'],
                ['woo-our-story',               'WooCommerce — two-column brand story: monogram watermark left, text + stats right'],
                ['woo-testimonials',            'WooCommerce — three-column testimonial cards with star ratings and avatar circles'],
                ['woo-newsletter',              'WooCommerce — full-bleed newsletter signup with decorative eyebrow'],
                ['woo-shop-landing',            'WooCommerce — store homepage shell that composes sub-patterns in sequence'],
                ['woo-cart',                    'WooCommerce — full-width cart page wrapper (Inserter: false)'],
                ['woo-checkout',                'WooCommerce — full-width checkout page wrapper (Inserter: false)'],
            ]
        );

        $io->section('Snippets  (copy from vendor/imagewize/pt-cli/snippets/)');
        $io->table(
            ['File', 'Description'],
            [
                ['eyebrow-heading-body.txt',          'Eyebrow label + heading + body paragraph'],
                ['3col-grid-wrapper.txt',             'Responsive 3-column grid wrapper'],
                ['stat-item.txt',                     'Number + label stat card (dark background)'],
                ['testimonial-card.txt',              'Testimonial with stars, quote, author'],
                ['two-button-group.txt',              'Primary + outline button pair'],
                ['overlay-grid-cover-card.txt',       'Portrait cover image card + floating badge (use wp:cover, NOT wp:image)'],
                // WP 6.6+ block validation guards
                ['valid-cover.txt',                   'wp:cover with all required attrs: dimRatio, backgroundColor/customGradient, minHeight (root integer) + minHeightUnit'],
                ['valid-columns-wp66.txt',            'wp:columns without inline gap/margin; isStackedOnMobile:false → is-not-stacked-on-mobile class'],
                ['responsive-grid-min-width.txt',     'wp:group grid layout with minimumColumnWidth — preferred over wp:columns for 3+ columns'],
                ['valid-button-attr-order.txt',       'wp:button with className/colors before style; font size via style.typography.fontSize'],
                ['valid-fullwidth-section.txt',       'alignfull outer group + margin reset (top/bottom:"0" no units) + constrained inner group'],
                ['valid-heading-with-preset.txt',     'wp:heading with fontSize slug in JSON and matching has-{slug}-font-size utility class in HTML'],
            ]
        );

        $io->section('Style Variations  (use with: pt-cli style:create)');
        $io->writeln('  Scaffold a <info>styles/*.json</info> theme variation with a pre-wired color palette.');
        $io->table(
            ['Vertical', 'Description'],
            [
                ['custom',        'Enter your own hex color values'],
                ['legal',         'Navy blue + gold'],
                ['plumbing',      'Dark blue + orange'],
                ['spa',           'Sage green + sand'],
                ['food-beverage', 'Burgundy + gold'],
            ]
        );

        $io->section('Pattern Categories');
        $categories = [
            'header',
            'footer',
            'elayne/hero',
            'elayne/features',
            'elayne/call-to-action',
            'elayne/testimonial',
            'elayne/team',
            'elayne/statistics',
            'elayne/contact',
            'elayne/posts',
            'elayne/pricing',
            'elayne/banner',
            'elayne/card-simple   (minimumColumnWidth: 18rem)',
            'elayne/card-extended (minimumColumnWidth: 19rem)',
            'elayne/card-profiles (minimumColumnWidth: 20rem)',
            'elayne/woocommerce',
        ];

        foreach ($categories as $cat) {
            $io->writeln("  <info>{$cat}</info>");
        }

        $io->newLine();

        return Command::SUCCESS;
    }
}
