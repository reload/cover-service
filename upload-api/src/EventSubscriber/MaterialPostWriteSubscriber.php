<?php
/**
 * @file
 * Make changes to API entities and add them to queue system.
 */

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Material;
use App\Message\CoverUserUploadMessage;
use App\Utils\Types\VendorState;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class MaterialPostWriteSubscriber.
 */
final class MaterialPostWriteSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $bus;
    private StorageInterface $storage;

    /**
     * MaterialPostWriteSubscriber constructor.
     *
     * @param MessageBusInterface $bus
     * @param StorageInterface $storage
     */
    public function __construct(MessageBusInterface $bus, StorageInterface $storage)
    {
        $this->bus = $bus;
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
     *
     * @return void
     */
    public function materialPostWrite(ViewEvent $event)
    {
        /** @var Material $material */
        $material = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$material instanceof Material) {
            return;
        }

        // @TODO: Find out telling symfony that it's ssl off loaded.
        // We here assumes that the schema is https here. We do not use information from the request as this
        // will be running in a pod behind ssl off-loading and the site thinks it's running http.
        if (Request::METHOD_POST == $method) {
            $base = 'https://'.$event->getRequest()->getHttpHost();
            $url = $base.$this->storage->resolveUri($material->cover, 'file');

            $message = new CoverUserUploadMessage();
            $message->setIdentifierType($material->getIsType());
            $message->setIdentifier($material->getIsIdentifier());
            $message->setOperation(VendorState::INSERT);
            $message->setImageUrl($url);
            $message->setAccrediting($material->getAgencyId());
            $this->bus->dispatch($message);
        }
    }
}
