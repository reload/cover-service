<?php
/**
 * @file
 * Command to clean up local stored images after upload detected.
 */

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
 * Class CleanUpCommand.
 */
class CleanUpCommand extends Command
{
    private CoverRepository $coverRepository;
    private CoverService $coverStoreService;
    private EntityManagerInterface $entityManager;

    protected static $defaultName = 'app:image:cleanup';

    /**
     * CleanUpCommand constructor.
     */
    public function __construct(CoverRepository $coverRepository, CoverService $coverStoreService, EntityManagerInterface $entityManager)
    {
        $this->coverRepository = $coverRepository;
        $this->coverStoreService = $coverStoreService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of records to load.', 0)
            ->setDescription('Clean up local stored images after upload detected');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getOption('limit');

        $query = $this->coverRepository->getIsNotUploaded($limit);
        /** @var Cover $cover */
        foreach ($query->toIterable() as $cover) {
            if ($this->coverStoreService->exists($cover->getMaterial()->getIsIdentifier())) {
                $this->coverStoreService->removeLocalFile($cover);

                $cover->setUploaded(true);
                $this->entityManager->flush();
            }
        }

        return Command::SUCCESS;
    }
}
