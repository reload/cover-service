<?php

/**
 * @file
 * Console command to validate that image exists.
 */

namespace App\Command\CoverStore;

use App\Entity\Source;
use App\Repository\SourceRepository;
use App\Repository\VendorRepository;
use App\Service\VendorService\VendorImageValidatorService;
use App\Utils\CoverVendor\VendorImageItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CoverStoreValidateImageCommand.
 */
#[AsCommand(name: 'app:cover:validate-image')]
class CoverStoreValidateImageCommand extends Command
{
    /**
     * CoverStoreValidateImageCommand constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param SourceRepository $sourceRepository
     * @param VendorRepository $vendorRepository
     * @param VendorImageValidatorService $imageValidatorService
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SourceRepository $sourceRepository,
        private readonly VendorRepository $vendorRepository,
        private readonly VendorImageValidatorService $imageValidatorService
    ) {
        parent::__construct();
    }

    /**
     * Define the command.
     */
    protected function configure(): void
    {
        $this->setDescription('Validate that uploaded image exists in cover store')
            ->addOption('vendor-id', null, InputOption::VALUE_REQUIRED, 'The id of the vendor to validate image for')
            ->addOption('identifier', null, InputOption::VALUE_OPTIONAL, 'Only for this identifier')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove any entries found with dead cover store URL');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $vendorId = $input->getOption('vendor-id');
        $identifier = $input->getOption('identifier');
        $remove = $input->getOption('remove');

        $vendor = $this->vendorRepository->findOneById($vendorId);

        if (is_null($vendor)) {
            $output->writeln('<error>Unknown vendor id!</error>');
        }

        $queryBuilder = $this->sourceRepository->createQueryBuilder('s')
            ->andWhere('s.vendor = (:vendor)')
            ->andWhere('s.image IS NOT NULL')
            ->setParameter('vendor', $vendor);

        if (!is_null($identifier)) {
            $queryBuilder->andWhere('s.matchId = (:identifier)')
                ->setParameter('identifier', $identifier);
        }

        $batchSize = 200;
        $i = 1;

        $found = [];
        /* @var Source $source */
        foreach ($queryBuilder->getQuery()->toIterable() as $source) {
            $image = new VendorImageItem($source->getImage()->getCoverStoreURL(), $source->getVendor());
            $this->imageValidatorService->validateRemoteImage($image);

            if (!$image->isFound()) {
                $found[] = [
                  $source->getId(),
                  $source->getMatchId(),
                  $source->getImage()->getCoverStoreURL(),
                ];

                if ($remove) {
                    foreach ($source->getSearches() as $search) {
                        $this->entityManager->remove($search);
                    }
                    $this->entityManager->remove($source->getImage());
                    $this->entityManager->remove($source);
                }
            }

            // Free memory when batch size is reached.
            if (0 === ($i % $batchSize)) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            ++$i;
        }

        // Ensure that all entities removed are removed.
        if ($remove) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        // If any was found inform the user.
        if (!empty($found)) {
            $output->writeln('Source entries with dead cover store URL\'s');
            $table = new Table($output);
            $table->setHeaders(['ID', 'Match Id', 'URL'])
                ->setRows($found);
            $table->render();

            if ($remove) {
                $output->writeln('Removed <info>'.count($found).'</info> source entities');
            }
        }

        return Command::SUCCESS;
    }
}
