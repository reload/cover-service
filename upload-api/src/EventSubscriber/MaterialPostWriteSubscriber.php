<?php
/**
 * @file
 * Make changes to API entities and add them to queue system.
 */

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Material;
use App\Utils\Message\CoverUploadProcessMessage;
use App\Utils\Types\VendorState;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class MaterialPostWriteSubscriber.
 */
final class MaterialPostWriteSubscriber implements EventSubscriberInterface
{
    private $producer;
    private $storage;

    /**
     * MaterialPostWriteSubscriber constructor.
     *
     * @param ProducerInterface $producer
     * @param StorageInterface $storage
     */
    public function __construct(ProducerInterface $producer, StorageInterface $storage)
    {
        $this->producer = $producer;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                'materialPostWrite', EventPriorities::POST_WRITE,
            ],
        ];
    }

    /**
     * Send event into the queue system to trigger upload an indexing.
     *
     * @param ViewEvent $event
     *   The event
     */
    public function materialPostWrite(ViewEvent $event)
    {
        /** @var Material $material */
        $material = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$material instanceof Material) {
            return;
        }

        switch ($method) {
            case Request::METHOD_POST:
                // @TODO: Find out telling symfony that it's ssl off loaded.
                // We here assumes that the schema is https here. We do not use information from the request as this
                // will be running in a pod behind ssl off-loading and the site thinks it's running http.
                $base = 'https://'.$event->getRequest()->getHttpHost();
                $url = $base.$this->storage->resolveUri($material->cover, 'file');

                $message = new CoverUploadProcessMessage();
                $message->setIdentifierType($material->getIsType());
                $message->setIdentifier($material->getIsIdentifier());
                $message->setOperation(VendorState::INSERT);
                $message->setImageUrl($url);
                $message->setAccrediting($material->getAgencyId());
                break;

            default:
                return;
        }

        $this->producer->sendEvent('UploadImageTopic', JSON::encode($message));
    }
}
