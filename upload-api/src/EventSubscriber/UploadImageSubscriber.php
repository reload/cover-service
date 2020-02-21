<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Material;
use Enqueue\Client\ProducerInterface;
use App\Utils\Message\CoverUploadProcessMessage;
use Enqueue\Util\JSON;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class UploadImageSubscriber implements EventSubscriberInterface
{
    private $producer;

    public function __construct(ProducerInterface $producer)
    {
        $this->producer = $producer;
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

        if (!$material instanceof Material || Request::METHOD_POST !== $method) {
            return;
        }

        $message = new CoverUploadProcessMessage();

        /**
         * @TODO: fill in information about the job.
         */


        $this->producer->sendEvent('VendorImageTopic', JSON::encode($message));

    }
}