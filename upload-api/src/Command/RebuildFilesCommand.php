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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

#[AsCommand(
    name: 'app:files:rebuild',
)]
class RebuildFilesCommand extends Command
{
    /**
     * RebuildFilesCommand constructor.
     */
    public function __construct(
        private readonly CoverRepository $coverRepository,
        private readonly CoverService $coverStoreService,
        private readonly HttpClientInterface $httpClient,
        private readonly StorageInterface $storage,
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
            ->setDescription('Download all remote files thereby rebuilding local filesystem');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getOption('limit');
        $agencyId = $input->getOption('agency-id');

        $query = $this->coverRepository->getAllWithRemoteUrlQuery($agencyId, $limit);

        /** @var Cover $cover */
        foreach ($query->toIterable() as $cover) {
            if (!$this->coverStoreService->existsLocalFile($cover)) {
                $response = $this->httpClient->request('GET', $cover->getRemoteUrl());
                if (200 !== $response->getStatusCode()) {
                    // Download failed.
                    $output->write('E');
                    continue;
                }

                $file = $this->storage->resolvePath($cover, 'file');
                $fileHandler = fopen($file, 'w');
                foreach ($this->httpClient->stream($response) as $chunk) {
                    fwrite($fileHandler, $chunk->getContent());
                }
                fclose($fileHandler);

                $output->write('.');
            }
        }
        $output->writeln(' ');

        return Command::SUCCESS;
    }
}
