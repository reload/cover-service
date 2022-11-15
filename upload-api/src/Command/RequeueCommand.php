<?php
/**
 * @file
 * Command to clean up local stored images after upload detected.
 */

namespace App\Command;

use App\Entity\Material;
use App\Message\CoverUserUploadMessage;
use App\Repository\MaterialRepository;
use App\Service\CoverService;
use App\Service\ProgressBarTrait;
use App\Utils\Types\VendorState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

#[AsCommand(
    name: 'app:image:requeue',
)]
class RequeueCommand extends Command
{
    use ProgressBarTrait;

    /**
     * CleanUpCommand constructor.
     */
    public function __construct(
        private readonly MaterialRepository $materialRepository,
        private readonly CoverService $coverStoreService,
        private readonly StorageInterface $storage,
        private readonly MessageBusInterface $bus,
        private readonly RouterInterface $router,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Requeue materials both updates and inserts missing')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of records to load (CoverStore has API limits 5000 req/h).', 5000)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset to start at in the database.', 0)
            ->addOption('agency-id', null, InputOption::VALUE_OPTIONAL, 'Limit by agency id')
            ->addOption('identifier', null, InputOption::VALUE_OPTIONAL, 'Only for this identifier')
            ->addOption('is-not-uploaded', null, InputOption::VALUE_NONE, 'Only look at records that is not marked as uploaded');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $agencyId = $input->getOption('agency-id');
        $identifier = $input->getOption('identifier');
        $isNotUploaded = $input->getOption('is-not-uploaded');
        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        $section = $output->section('Sheet');
        $progressBarSheet = new ProgressBar($section);
        $progressBarSheet->setFormat('[%bar%] %elapsed% (%memory%) - %message%');
        $this->setProgressBar($progressBarSheet);
        $this->progressStart('Loading data from the database');
        $batchSize = 200;
        $i = 1;
        $messagesInserted = 0;
        $messagesUpdated = 0;
        $messagesNotProcessable = 0;

        if (is_null($identifier)) {
            if (is_null($agencyId)) {
                $query = $this->materialRepository->getAll($isNotUploaded, $limit, $offset);
            } else {
                $query = $this->materialRepository->getByAgencyId($agencyId, $isNotUploaded, $limit, $offset);
            }
        } else {
            $material = $this->materialRepository->findOneBy(['isIdentifier' => $identifier]);
            if ($material instanceof Material) {
                $this->progressAdvance();
                $this->progressMessage($i.' material found in DB');
                $this->progressFinish();

                $this->sendMessage($material);

                return Command::SUCCESS;
            } else {
                $output->writeln('<error>Material not found</error>');

                return Command::FAILURE;
            }
        }

        /** @var Material $material */
        foreach ($query->toIterable() as $material) {
            $existsRemote = $this->coverStoreService->exists($material->getIsIdentifier());
            if ($this->coverStoreService->existsLocalFile($material->getCover()) && !$existsRemote) {
                $this->sendMessage($material);
                ++$messagesInserted;
            } else {
                if ($existsRemote) {
                    $this->sendMessage($material);
                    ++$messagesUpdated;
                } else {
                    // Not processable.
                    ++$messagesNotProcessable;
                }
            }

            $this->progressAdvance();
            $this->progressMessage($i.' material(s) found in DB. '.$messagesInserted.' inserted, '.$messagesUpdated.' updated send into queues and not processable '.$messagesNotProcessable);
            ++$i;

            // Free memory when batch size is reached.
            if (0 === ($i % $batchSize)) {
                gc_collect_cycles();
            }
        }

        $this->progressFinish();

        return Command::SUCCESS;
    }

    /**
     * Send upload message into queue system.
     *
     * @param material $material
     *   The material to upload to cover service
     */
    private function sendMessage(Material $material)
    {
        $base = 'https://'.rtrim($this->router->generate('homepage'), '/');
        $url = $base.$this->storage->resolveUri($material->getCover(), 'file');

        $message = new CoverUserUploadMessage();
        $message->setIdentifierType($material->getIsType())
            ->setIdentifier($material->getIsIdentifier())
            ->setImageUrl($url)
            ->setAccrediting($material->getAgencyId())
            ->setAgency($material->getAgencyId());

        $this->bus->dispatch($message);
    }
}
