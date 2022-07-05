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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class MaterialPreWriteSubscriber.
 */
final class MaterialPreWriteSubscriber implements EventSubscriberInterface
{
    private readonly UserInterface $user;

    /**
     * MaterialPreWriteSubscriber constructor.
     *
     * @param MessageBusInterface $bus
     * @param Security $security
     */
    public function __construct(private readonly MessageBusInterface $bus, Security $security)
    {
        $this->user = $security->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                'materialPreWrite', EventPriorities::PRE_WRITE,
            ],
        ];
    }

    /**
     * Set information on the Material entity from access token.
     *
     * @param ViewEvent $event
     *   The event
     *
     * @return void
     */
    public function materialPreWrite(ViewEvent $event): void
    {
        /** @var Material $item */
        $item = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        switch ($method) {
            case Request::METHOD_DELETE:
                if (!$item instanceof Material) {
                    return;
                }

                $message = new CoverUserUploadMessage();
                $message->setIdentifierType($item->getIsType());
                $message->setIdentifier($item->getIsIdentifier());
                $message->setOperation(VendorState::DELETE);

                $this->bus->dispatch($message);
                break;

            case Request::METHOD_POST:
                $item->setAgencyId($this->user->getAgency());
                break;
        }
    }
}
