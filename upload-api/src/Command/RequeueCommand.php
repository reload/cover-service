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
use App\Utils\Types\VendorState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class RequeueCommand.
 */
class RequeueCommand extends Command
{
    private CoverService $coverStoreService;
    private MaterialRepository $materialRepository;
    private StorageInterface $storage;
    private MessageBusInterface $bus;
    private RouterInterface $router;

    protected static $defaultName = 'app:image:requeue';

    /**
     * CleanUpCommand constructor.
     */
    public function __construct(MaterialRepository $materialRepository, CoverService $coverStoreService, StorageInterface $storage, MessageBusInterface $bus, RouterInterface $router)
    {
        parent::__construct();
        $this->materialRepository = $materialRepository;
        $this->coverStoreService = $coverStoreService;
        $this->storage = $storage;
        $this->bus = $bus;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Clean up local stored images after upload detected')
            ->addOption('agency-id', null, InputOption::VALUE_OPTIONAL, 'Limit by agency id')
            ->addOption('identifier', null, InputOption::VALUE_OPTIONAL, 'Only for this identifier')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force re-upload of image even if it exists in the cover store');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $agencyId = $input->getOption('agency-id');
        $identifier = $input->getOption('identifier');
        $force = $input->getOption('force');

        $materials = [];
        if (is_null($identifier)) {
            $materials = $this->materialRepository->getByAgencyId($agencyId);
        } else {
            $material = $this->materialRepository->findOneBy(['isIdentifier' => $identifier]);
            if (isset($material)) {
                $materials[] = $material;
            } else {
                $output->writeln('<error>Material not found</error>');

                return Command::FAILURE;
            }
        }

        foreach ($materials as $material) {
            /** @var Material $material */
            if (!$this->coverStoreService->exists($material->getIsIdentifier()) || $force) {
                $base = 'https://'.rtrim($this->router->generate('homepage'), '/');
                $url = $base.$this->storage->resolveUri($material->getCover(), 'file');

                $message = new CoverUserUploadMessage();
                $message->setIdentifierType($material->getIsType());
                $message->setIdentifier($material->getIsIdentifier());
                $message->setOperation(VendorState::INSERT);
                $message->setImageUrl($url);
                $message->setAccrediting($material->getAgencyId());

                $this->bus->dispatch($message);
            }
        }

        return Command::SUCCESS;
    }
}
