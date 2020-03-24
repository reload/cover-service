<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Material;
use App\Utils\Types\VendorState;
use Enqueue\Client\ProducerInterface;
use App\Utils\Message\CoverUploadProcessMessage;
use Enqueue\Util\JSON;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vich\UploaderBundle\Storage\StorageInterface;

final class UploadImageSubscriber implements EventSubscriberInterface
{
    private $producer;
    private $storage;

    public function __construct(ProducerInterface $producer, StorageInterface $storage)
    {
        $this->producer = $producer;
        $this->storage = $storage;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['uploadImage', EventPriorities::POST_WRITE],
        ];
    }

    public function uploadImage(ViewEvent $event)
    {
        $material = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$material instanceof Material) {
            return;
        }

        $message = new CoverUploadProcessMessage();

        switch ($method) {
            case Request::METHOD_POST:
                $uri = $this->storage->resolveUri($material->cover, 'file');

                $message->setIdentifier($material->getIsIdentifier());
                $message->setVendorId(11);
                $message->setOperation(VendorState::INSERT);
                $message->setImageUrl($uri);
                break;

            case Request::METHOD_DELETE:
                $message->setIdentifier($material->getIsIdentifier());
                $message->setVendorId(11);
                $message->setOperation(VendorState::DELETE);
                break;

            default:
                return;
        }

        /**
         * @TODO: fill in information about the job.
         */


        $this->producer->sendEvent('VendorImageTopic', JSON::encode($message));

    }
}