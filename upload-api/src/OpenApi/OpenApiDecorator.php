<?php
/**
 * @file
 * Decorator to correct the generated OpenApi/Swagger documentation
 */

namespace App\OpenApi;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Class OpenApiDecorator.
 */
final class OpenApiDecorator implements NormalizerInterface
{
    /**
     * OpenApiDecorator constructor.
     *
     * @param NormalizerInterface $decorated
     */
    public function __construct(
        private readonly NormalizerInterface $decorated
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        $this->correctMaterialWriteDefinition($docs);
        $this->correctSecurityDefinitions($docs);

        return $docs;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * Correct documentation definition for "Material" write operation.
     *
     * @param array $docs
     */
    private function correctMaterialWriteDefinition(array &$docs): void
    {
        // Correct material write definition for cover
        $coverDefinition = [
            'externalDocs' => [
                'url' => 'https://schema.org/image',
            ],
            'type' => 'string',
            'format' => 'iri-reference',
            'example' => 'api/covers/1',
        ];
        $docs['components']['schemas']['Material-Write']['properties']['cover'] = $coverDefinition;
    }

    /**
     * Correct security definition for oauth scope.
     *
     * @param array $docs
     */
    private function correctSecurityDefinitions(array &$docs): void
    {
        // Remove "authorizationUrl". Not allowed for "password grant"
        unset($docs['components']['securitySchemes']['oauth']['flows']['password']['authorizationUrl']);

        // "scopes" should be object, not array
        $docs['components']['securitySchemes']['oauth']['flows']['password']['scopes'] = new \stdClass();
    }
}
