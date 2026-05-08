<?php

declare(strict_types=1);

namespace Imagewize\PtCli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $output->writeln('<info>pt-cli check — not yet implemented</info>');

        return Command::SUCCESS;
    }
}
