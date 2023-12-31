<?php

/**
 * @file
 */

namespace App\MessageHandler;

use App\Entity\Source;
use App\Entity\Vendor;
use App\Message\CoverStoreMessage;
use App\Message\VendorImageMessage;
use App\Service\VendorService\VendorImageValidatorService;
use App\Utils\CoverVendor\VendorImageItem;
use App\Utils\Types\VendorState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Class VendorImageMessageHandler.
 */
class VendorImageMessageHandler implements MessageHandlerInterface
{
    /**
     * VendorImageMessageHandler constructor.
     *
     * @param EntityManagerInterface $em
     * @param VendorImageValidatorService $imageValidator
     * @param MessageBusInterface $bus
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VendorImageValidatorService $imageValidator,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param VendorImageMessage $message
     */
    public function __invoke(VendorImageMessage $message)
    {
        // Look up vendor to get information about image server.
        $vendorRepos = $this->em->getRepository(Vendor::class);
        $vendor = $vendorRepos->find($message->getVendorId());

        // Look up source to get source url and link it to the image.
        $sourceRepos = $this->em->getRepository(Source::class);
        /** @var Source $source */
        $source = $sourceRepos->findOneBy([
            'matchId' => $message->getIdentifier(),
            'matchType' => $message->getIdentifierType(),
            'vendor' => $vendor,
        ]);

        if (!is_null($source)) {
            switch ($message->getOperation()) {
                case VendorState::INSERT:
                    $this->processInsert($message, $source);
                    break;

                case VendorState::UPDATE:
                    // Validate source have image data before trying to update it.
                    if (is_null($source->getOriginalFile())) {
                        throw new UnrecoverableMessageHandlingException('Source do not have image attached');
                    }
                    $this->processUpdate($message, $source);
                    break;

                default:
                    throw new UnrecoverableMessageHandlingException('Unknown vendor operation');
            }
        } else {
            throw new UnrecoverableMessageHandlingException('Source not found');
        }
    }

    /**
     * Handle image inserts. Send update to cover store processor only if vendor image exists.
     */
    private function processInsert(VendorImageMessage $message, Source $source): void
    {
        $item = new VendorImageItem($source->getOriginalFile(), $source->getVendor());

        // If the image is validated the isFound() will return true/false. The LastModified and ContentLength length
        // will also be set on the $item variable.
        $this->imageValidator->validateRemoteImage($item);

        if ($item->isFound()) {
            // Ensure that database operations are completed before sending new related jobs into queues.
            $source->setOriginalLastModified($item->getOriginalLastModified());
            $source->setOriginalContentLength($item->getOriginalContentLength());
            $this->em->flush();

            // Hack to send message into new queue.
            $coverStoreMessage = new CoverStoreMessage();
            $coverStoreMessage->setIdentifier($message->getIdentifier())
                ->setIdentifierType($message->getIdentifierType())
                ->setImageId($message->getImageId())
                ->setOperation($message->getOperation())
                ->setUseSearchCache($message->useSearchCache())
                ->setVendorId($message->getVendorId())
                ->setAgency($message->getAgency())
                ->setProfile($message->getProfile());
            $this->bus->dispatch($coverStoreMessage);
        } else {
            $source->setOriginalLastModified(null);
            $source->setOriginalContentLength(null);
            $this->em->flush();

            // Log that the image did not exist.
            $this->logger->error('Vendor image error - not found', [
                'service' => 'VendorImageProcessor',
                'identifier' => $message->getIdentifier(),
                'url' => $item->getOriginalFile(),
            ]);
        }

        // Free memory.
        $this->em->clear();
    }

    /**
     * Handle image updates.
     *
     * Send update to cover store processor only if vendor image is updated.
     */
    private function processUpdate(VendorImageMessage $message, Source $source): void
    {
        $item = new VendorImageItem($source->getOriginalFile(), $source->getVendor());

        $this->imageValidator->isRemoteImageUpdated($item, $source);

        if ($item->isUpdated()) {
            $coverStoreMessage = new CoverStoreMessage();
            $coverStoreMessage->setIdentifier($message->getIdentifier())
                ->setIdentifierType($message->getIdentifierType())
                ->setImageId($message->getImageId())
                ->setOperation($message->getOperation())
                ->setUseSearchCache($message->useSearchCache())
                ->setVendorId($message->getVendorId())
                ->setAgency($message->getAgency())
                ->setProfile($message->getProfile());
            $this->bus->dispatch($coverStoreMessage);
        } else {
            $this->logger->info('Remote image is not updated', [
                'service' => 'VendorImageProcessor',
                'identifier' => $message->getIdentifier(),
                'url' => $item->getOriginalFile(),
            ]);
        }
    }
}
