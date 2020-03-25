<?php

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
        /** @var Material $material */
        $material = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$material instanceof Material) {
            return;
        }

        $message = new CoverUploadProcessMessage();
        $message->setIdentifierType($material->getIsType());
        $message->setIdentifier($material->getIsIdentifier());

        switch ($method) {
            case Request::METHOD_POST:
                $base = $event->getRequest()->getSchemeAndHttpHost();
                $url = $base.$this->storage->resolveUri($material->cover, 'file');
                $message->setOperation(VendorState::INSERT);
                $message->setImageUrl($url);
                break;

            case Request::METHOD_DELETE:
                $message->setOperation(VendorState::DELETE);
                break;

            default:
                return;
        }

        $this->producer->sendEvent('UploadImageTopic', JSON::encode($message));
    }
}
