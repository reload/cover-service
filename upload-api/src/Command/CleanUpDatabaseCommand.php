<?php

namespace App\Command;

use App\Entity\Cover;
use App\Repository\CoverRepository;
use App\Service\CoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanUpDatabaseCommand.
 */
class CleanUpDatabaseCommand extends Command
{
    private CoverRepository $coverRepository;
    private CoverService $coverStoreService;
    private EntityManagerInterface $em;

    protected static $defaultName = 'app:database:cleanup';

    /**
     * CleanUpCommand constructor.
     */
    public function __construct(CoverRepository $coverRepository, CoverService $coverStoreService, EntityManagerInterface $entityManager)
    {
        $this->coverRepository = $coverRepository;
        $this->coverStoreService = $coverStoreService;
        $this->em = $entityManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show what would have been removed.')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of records to load.', 0)
            ->setDescription('Try to clean out invalid entities and make cover as upload if so.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getOption('limit');

        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $output->writeln('Dry-run mode...');
        }

        $removed = 0;
        $existsInCS = 0;
        $fileExists = 0;
        $noMaterial = 0;

        $query = $this->coverRepository->getIsNotUploaded($limit);
        /** @var Cover $cover */
        foreach ($query->toIterable() as $cover) {
            $output->write('.');

            if (is_null($cover->getMaterial())) {
                if (!$dryRun) {
                    $this->em->remove($cover);
                    $this->em->flush();
                }
                ++$noMaterial;
                continue;
            }

            if (!$this->coverStoreService->exists($cover->getMaterial()->getIsIdentifier())) {
                // Check if local file exists as it may not do to service moving installation.
                if (!$this->coverStoreService->existsLocalFile($cover)) {
                    if (!$dryRun) {
                        $material = $cover->getMaterial();
                        $this->em->remove($cover);
                        $this->em->remove($material);
                        $this->em->flush();
                    }

                    ++$removed;
                } else {
                    ++$fileExists;
                    $cover->setUploaded(true);
                    $this->em->flush();
                }
            } else {
                ++$existsInCS;
            }
        }

        $output->writeln('');
        $output->writeln('Removed: '.$removed);
        $output->writeln('Exists in CS: '.$existsInCS);
        $output->writeln('File exists: '.$fileExists);
        $output->writeln('No material: '.$noMaterial);

        return Command::SUCCESS;
    }
}
