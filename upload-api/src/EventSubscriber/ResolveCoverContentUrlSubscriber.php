<?php
/**
 * @file
 * Returning the plain file path on the filesystem where the file is stored is not useful for the client,
 * which needs a URL to work with.
 *
 * So this event subscriber sets that URL on the cover object.
 */

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use App\Entity\Cover;
use App\Entity\Material;
use App\Service\CoverStoreService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Class ResolveCoverContentUrlSubscriber.
 */
final class ResolveCoverContentUrlSubscriber implements EventSubscriberInterface
{
    private $storage;
    private $coverStoreService;

    public function __construct(StorageInterface $storage, CoverStoreService $coverStoreService)
    {
        $this->storage = $storage;
        $this->coverStoreService = $coverStoreService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onPreSerialize', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function onPreSerialize(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) ||
            (!\is_a($attributes['resource_class'], Cover::class, true) && !\is_a($attributes['resource_class'], Material::class, true))) {
            return;
        }

        $entities = $controllerResult;

        if (!is_iterable($entities)) {
            $entities = [$entities];
        }

        foreach ($entities as $entity) {
            if (!$entity instanceof Cover && !$entity instanceof Material) {
                continue;
            }

            /** @var Cover $cover */
            $cover = $entity instanceof Cover ? $entity : $entity->cover;

            if ($cover->isUploaded()) {
                // If the cover has been marked as uploaded use the cover store URL.
                $cover->setImageUrl($this->coverStoreService->generateUrl($cover));
            } else {
                $host = $request->getSchemeAndHttpHost();
                $uri = $this->storage->resolveUri($cover, 'file');
                $cover->setImageUrl($host.$uri);
            }
        }
    }
}
