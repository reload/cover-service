<?php
/**
 * @file
 * Command to clean up local stored images after upload detected.
 */

namespace App\Command;

use App\Entity\Cover;
use App\Repository\CoverRepository;
use App\Service\CoverStoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanUpCommand.
 */
class CleanUpCommand extends Command
{
    private $coverRepository;
    private $coverStoreService;
    private $entityManager;

    protected static $defaultName = 'app:image:cleanup';

    /**
     * CleanUpCommand constructor.
     */
    public function __construct(CoverRepository $coverRepository, CoverStoreService $coverStoreService, EntityManagerInterface $entityManager)
    {
        $this->coverRepository = $coverRepository;
        $this->coverStoreService = $coverStoreService;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clean up local stored images after upload detected');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $covers = $this->coverRepository->getIsNotUploaded();
        foreach ($covers as $cover) {
            /** @var Cover $cover */
            if ($this->coverStoreService->exists($cover)) {
                $this->coverStoreService->removeLocalFile($cover);

                $cover->setUploaded(true);
                $this->entityManager->flush();
            }
        }

        return 0;
    }
}
