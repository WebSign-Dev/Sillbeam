<?php

declare(strict_types=1);

namespace Nexum\Api\Serializer;

use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Metadata\ResourceMetadata;
use Nexum\Api\Operation\Operation;
use Nexum\Entity\Contract\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ResourcePayloadMapper
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function map(
        Request $request,
        ResourceMetadata $metadata,
        Operation $operation,
        ?EntityInterface $resource = null,
    ): EntityInterface {
        if ('json' !== $request->getContentTypeFormat()) {
            throw new ApiProblemException(415, 'unsupported_media_type', 'Unsupported media type', 'Nexum resource writes require Content-Type: application/json.');
        }

        $payload = $this->decode($request->getContent());
        $fields = array_keys($payload);
        $unknown = array_values(array_diff($fields, $metadata->writableFields));

        if ([] !== $unknown) {
            throw ApiProblemException::badRequest(\sprintf('Unknown or non-writable fields: %s.', implode(', ', $unknown)), 'unknown_fields');
        }

        $required = Operation::REPLACE === $operation ? $metadata->writableFields : $metadata->requiredFields;
        $missing = array_values(array_diff($required, $fields));

        if ([] !== $missing) {
            throw ApiProblemException::badRequest(\sprintf('Missing required fields: %s.', implode(', ', $missing)), 'missing_fields');
        }

        if (Operation::UPDATE === $operation && [] === $payload) {
            throw ApiProblemException::badRequest('A PATCH request must contain at least one writable field.', 'empty_patch');
        }

        $context = [
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            AbstractNormalizer::ATTRIBUTES => $metadata->writableFields,
            DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
        ] + $metadata->denormalizationContext;

        if (null !== $resource) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $resource;
        }

        try {
            $mapped = $this->serializer->deserialize($request->getContent(), $metadata->entity, 'json', $context);
        } catch (ExceptionInterface $exception) {
            throw new ApiProblemException(422, 'invalid_payload', 'Invalid payload', $exception->getMessage());
        }

        if (!$mapped instanceof EntityInterface) {
            throw new \LogicException('The API serializer returned a non-Nexum entity.');
        }

        return $mapped;
    }

    /** @return array<string, mixed> */
    private function decode(string $content): array
    {
        if ('' === trim($content)) {
            throw ApiProblemException::badRequest('The JSON request body must not be empty.', 'empty_body');
        }

        try {
            $decoded = json_decode($content, false, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw ApiProblemException::badRequest('Malformed JSON: '.$exception->getMessage(), 'malformed_json');
        }

        if (!$decoded instanceof \stdClass) {
            throw ApiProblemException::badRequest('The JSON request body must be an object.', 'invalid_body');
        }

        return get_object_vars($decoded);
    }
}
