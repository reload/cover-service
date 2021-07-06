<?php

/**
 * @file
 */

namespace App\MessageHandler;

use App\Entity\Image;
use App\Entity\Source;
use App\Entity\Vendor;
use App\Exception\CoverStoreCredentialException;
use App\Exception\CoverStoreException;
use App\Exception\CoverStoreInvalidResourceException;
use App\Exception\CoverStoreNotFoundException;
use App\Exception\CoverStoreTooLargeFileException;
use App\Exception\CoverStoreUnexpectedException;
use App\Exception\ReQueueMessageException;
use App\Message\CoverStoreMessage;
use App\Message\SearchMessage;
use App\Service\CoverStore\CoverStoreInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class CoverStoreProcessor.
 */
class CoverStoreMessageHandler implements MessageHandlerInterface
{
    private $em;
    private $bus;
    private $logger;
    private $coverStore;

    /**
     * CoverStoreProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     * @param LoggerInterface $informationLogger
     * @param CoverStoreInterface $coverStore
     */
    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus, LoggerInterface $informationLogger, CoverStoreInterface $coverStore)
    {
        $this->em = $entityManager;
        $this->bus = $bus;
        $this->logger = $informationLogger;
        $this->coverStore = $coverStore;
    }

    /**
     * @param CoverStoreMessage $message
     *
     * @return void
     *
     * @throws ReQueueMessageException
     */
    public function __invoke(CoverStoreMessage $message): void
    {
        // Look up vendor to get information about image server.
        $vendorRepos = $this->em->getRepository(Vendor::class);
        $vendor = $vendorRepos->find($message->getVendorId());

        // Look up source to get source url and link it to the image.
        $sourceRepos = $this->em->getRepository(Source::class);
        $source = $sourceRepos->findOneBy([
            'matchId' => $message->getIdentifier(),
            'vendor' => $vendor,
        ]);

        try {
            $identifier = $message->getIdentifier();
            $item = $this->coverStore->upload($source->getOriginalFile(), $vendor->getName(), $identifier, [$identifier]);
        } catch (CoverStoreCredentialException $exception) {
            // Access issues.
            $this->logger->error('Access denied to cover store', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $message->getIdentifier(),
            ]);

            throw new UnrecoverableMessageHandlingException('Access denied to cover store');
        } catch (CoverStoreNotFoundException $exception) {
            // Update image entity and remove source URL.
            $source->setOriginalFile(null);
            $source->setOriginalLastModified(null);
            $source->setOriginalContentLength(null);
            $this->em->flush();

            // Log that the image did not exists.
            $this->logger->error('Cover store error - not found', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $message->getIdentifier(),
                'url' => $source->getOriginalFile(),
            ]);

            throw new UnrecoverableMessageHandlingException('Cover store error - not found');
        } catch (CoverStoreTooLargeFileException $exception) {
            $this->logger->error('Cover was to large', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $message->getIdentifier(),
                'url' => $source->getOriginalFile(),
            ]);

            throw new UnrecoverableMessageHandlingException('Cover was to large');
        } catch (CoverStoreUnexpectedException $exception) {
            $this->logger->error('Cover store unexpected error', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $message->getIdentifier(),
            ]);

            throw new UnrecoverableMessageHandlingException('Cover store unexpected error');
        } catch (CoverStoreInvalidResourceException $exception) {
            $this->logger->error('Cover store invalid resource error', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $message->getIdentifier(),
            ]);

            throw new UnrecoverableMessageHandlingException('Cover store invalid resource error');
        } catch (CoverStoreException $exception) {
            $this->logger->error('Cover store error - retry', [
                'service' => 'CoverStoreProcessor',
                'message' => $exception->getMessage(),
                'identifier' => $message->getIdentifier(),
            ]);

            // Throw exception for retry.
            throw new ReQueueMessageException('Cover store error - retry');
        }

        // Log information about the image uploaded.
        $this->logger->info('Image cover stored', [
            'service' => 'CoverStoreProcessor',
            'provider' => $item->getVendor(),
            'url' => $item->getUrl(),
            'width' => $item->getWidth(),
            'height' => $item->getHeight(),
            'bytes' => $item->getSize(),
            'format' => $item->getImageFormat(),
        ]);

        // Get image entity, if empty create new image entity else update the
        // entity.
        $image = $source->getImage();
        if (empty($image)) {
            $image = new Image();
            $this->em->persist($image);
        }

        $image->setImageFormat($item->getImageFormat())
            ->setSize($item->getSize())
            ->setWidth($item->getWidth())
            ->setHeight($item->getHeight())
            ->setCoverStoreURL($item->getUrl());

        $source->setImage($image);
        $this->em->flush();

        $message->setImageId($image->getId());

        // Send message to next part of the process.
        $searchMessage = new SearchMessage();
        $searchMessage->setIdentifier($message->getIdentifier())
            ->setIdentifierType($message->getIdentifierType())
            ->setOperation($message->getOperation())
            ->setImageId($message->getImageId())
            ->setVendorId($message->getVendorId())
            ->setUseSearchCache($message->useSearchCache());
        $this->bus->dispatch($searchMessage);
    }
}
