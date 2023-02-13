<?php
/**
 * @file
 * Command to get remote urls from cover store and update local database.
 */

namespace App\Command;

use App\Entity\Cover;
use App\Repository\CoverRepository;
use App\Service\CoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;

#[AsCommand(
    name: 'app:cover:get-remote-urls',
)]
class GetRemoteUrlsCommand extends Command
{
    /**
     * GetRemoteUrlsCommand constructor.
     */
    public function __construct(
        private readonly CoverRepository $coverRepository,
        private readonly CoverService $coverStoreService,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of records to load.', 0)
            ->addOption('agency-id', null, InputOption::VALUE_OPTIONAL, 'Limit by agency id', '')
            ->setDescription('Update cover store urls in local database (remoteUrl)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getOption('limit');
        $agencyId = $input->getOption('agency-id');

        $query = $this->coverRepository->getNoRemoteUrlQuery($agencyId, $limit);

        /** @var Cover $cover */
        foreach ($query->toIterable() as $cover) {
            try {
                if ($this->coverStoreService->exists($cover->getMaterial()->getIsIdentifier())) {
                    $this->coverStoreService->removeLocalFile($cover);
                    $item = $this->coverStoreService->search($cover->getMaterial()->getIsIdentifier());

                    if (null !== $item) {
                        $cover->setRemoteUrl($item->getUrl());
                    }
                    $cover->setUploaded(true);
                    $this->entityManager->flush();
                }
            } catch (UninitializedPropertyException $e) {
                // Do nothing, as cover was not connect to a material.
            }
        }

        return Command::SUCCESS;
    }
}
