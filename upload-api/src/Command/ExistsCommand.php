<?php

namespace App\Command;

use App\Entity\Material;
use App\Repository\MaterialRepository;
use App\Service\CoverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CleanUpCommand.
 */
class ExistsCommand extends Command
{
    private CoverService $coverStoreService;
    private EntityManagerInterface $entityManager;
    private MaterialRepository $materialRepository;

    protected static $defaultName = 'app:cs:exists';

    /**
     * CleanUpCommand constructor.
     */
    public function __construct(MaterialRepository $materialRepository, CoverService $coverStoreService, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->coverStoreService = $coverStoreService;
        $this->entityManager = $entityManager;
        $this->materialRepository = $materialRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('identifier', null, InputOption::VALUE_REQUIRED, 'Material identifier of type PID', 0)
            ->setDescription('Check status for a given material');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = $input->getOption('identifier');

        $inCoverServiceIndex = false;
        $inCoverTable = false;
        $inMaterialTable = false;

        $materials = $this->materialRepository->findBy(['isIdentifier' => $pid]);
        if (!empty($materials)) {
            $inMaterialTable = true;

            /** @var Material $material */
            foreach ($materials as $material) {
                if (!is_null($material->getCover())) {
                    if ($this->coverStoreService->exists($material->getIsIdentifier())) {
                        $output->write((string) $material);
                        $inCoverServiceIndex = true;
                    }
                    $inCoverTable = true;
                }
            }
        }

        $output->writeln('In CoverService: '.$inCoverServiceIndex);
        $output->writeln('In cover table: '.$inCoverTable);
        $output->writeln('In material table: '.$inMaterialTable);

        return Command::SUCCESS;
    }
}
