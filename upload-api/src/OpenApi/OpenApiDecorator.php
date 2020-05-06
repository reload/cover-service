<?php

namespace App\OpenApi;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class OpenApiDecorator implements NormalizerInterface
{
    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        // Correct material write definition for cover
        $coverDefinition = [
            '"externalDocs' => [
                'url' => 'http://schema.org/image',
            ],
            'type' => 'string',
            'format' => 'iri-reference',
        ];
        $docs['definitions']['Material-Write']['properties']['cover'] = $coverDefinition;

        unset($docs['definitions']['Cover-Read']);

        return $docs;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
