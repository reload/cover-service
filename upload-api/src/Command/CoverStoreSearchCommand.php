<?php

/**
 * @file
 * Console command to search the cover store.
 */

namespace App\Command;

use App\Service\CoverStore\CoverStoreInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cover:search',
)]
class CoverStoreSearchCommand extends Command
{
    /**
     * CoverStoreSearchCommand constructor.
     *
     * @param CoverStoreInterface $store
     */
    public function __construct(private readonly CoverStoreInterface $store)
    {
        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure(): void
    {
        $this->setDescription('Search user uploaded images in the cover store')
            ->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'Identifier to search for in user upload covers');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $items = $this->store->search(
            $input->getOption('identifier')
        );

        $output->writeln($items);

        return Command::SUCCESS;
    }
}
