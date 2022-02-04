<?php

/**
 * @file
 * Console command to search the cover store.
 */

namespace App\Command;

use App\Service\CoverStore\CoverStoreInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CoverStoreSearchCommand.
 */
class CoverStoreSearchCommand extends Command
{
    protected static $defaultName = 'app:cover:search';

    private CoverStoreInterface $store;

    /**
     * CoverStoreSearchCommand constructor.
     *
     * @param CoverStoreInterface $store
     */
    public function __construct(CoverStoreInterface $store)
    {
        $this->store = $store;

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

        return 0;
    }
}
