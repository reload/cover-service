<?php
/**
 * @file
 * Make changes to API entities and add them to queue system.
 */

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Material;
use App\Security\User;
use App\Utils\Message\CoverUploadProcessMessage;
use App\Utils\Types\VendorState;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class UploadImageSubscriber
 */
final class MaterialEventSubscriber implements EventSubscriberInterface
{
    private $producer;
    private $storage;

    /** @var User */
    private $user;

    /**
     * UploadImageSubscriber constructor.
     *
     * @param ProducerInterface $producer
     * @param StorageInterface $storage
     * @param Security $security
     */
    public function __construct(ProducerInterface $producer, StorageInterface $storage, Security $security)
    {
        $this->producer = $producer;
        $this->storage = $storage;

        $this->user = $security->getUser();
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                'materialPreWrite', EventPriorities::PRE_WRITE,
                'materialPostWrite', EventPriorities::POST_WRITE,
            ],
        ];
    }

    /**
     * Set information on the Material entity from access token.
     *
     * @param ViewEvent $event
     *   The event
     */
    public function materialPreWrite(ViewEvent $event)
    {
        $material = $event->getControllerResult();
        if (!$material instanceof Material) {
            return;
        }

        $material->setAgencyId($this->user->getAgency());
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
