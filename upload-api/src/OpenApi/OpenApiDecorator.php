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
    private $decorated;

    /**
     * OpenApiDecorator constructor.
     *
     * @param NormalizerInterface $decorated
     */
    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

//        $this->correctMaterialWriteDefinition($docs);
//        $this->removeCoverWriteDefinition($docs);
        $this->correctSecurityDefinitions($docs);

        return $docs;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * Correct documentation definition for "Materiel" write operation.
     *
     * @param array $docs
     */
    private function correctMaterialWriteDefinition(array &$docs): void
    {
        // Correct material write definition for cover
        $coverDefinition = [
            '"externalDocs' => [
                'url' => 'http://schema.org/image',
            ],
            'type' => 'string',
            'format' => 'iri-reference',
        ];
        $docs['definitions']['Material-Write']['properties']['cover'] = $coverDefinition;
    }

    /**
     * Remove unused write definition for "Cover".
     *
     * @param array $docs
     */
    private function removeCoverWriteDefinition(array &$docs): void
    {
        // Unset unused definition
        unset($docs['definitions']['Cover-Write']);
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
