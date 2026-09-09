<?php

declare(strict_types=1);

namespace Nexum\Api\Scope;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Metadata\ResourceMetadata;
use Nexum\Api\Operation\Operation;
use Nexum\Core\Contract\SubjectInterface;
use Nexum\Entity\Contract\EntityInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DefaultResourceScopeProvider implements ResourceScopeProviderInterface
{
    public function __construct(private ?TokenStorageInterface $tokenStorage = null)
    {
    }

    public function restrict(QueryBuilder $builder, ClassMetadata $classMetadata, ResourceMetadata $metadata, Operation $operation): void
    {
        if (ResourceScope::GLOBAL === $metadata->scope) {
            return;
        }
        if (ResourceScope::DENY === $metadata->scope) {
            throw $this->denied();
        }

        $field = $this->scopeField($metadata, $classMetadata);
        $subject = $this->subject();
        if ($classMetadata->hasAssociation($field)) {
            $builder
                ->andWhere('IDENTITY(resource.'.$field.') = :nexum_resource_subject_id')
                ->setParameter('nexum_resource_subject_id', Uuid::fromString($subject->getSubjectIdentifier()), 'uuid');

            return;
        }

        $builder->andWhere('resource.'.$field.' = :nexum_resource_subject');
        $builder->setParameter(
            'nexum_resource_subject',
            $this->fieldValue($classMetadata, $field, $subject),
            $classMetadata->getTypeOfField($field),
        );
    }

    public function initialize(EntityInterface $resource, ClassMetadata $classMetadata, ResourceMetadata $metadata): void
    {
        if (ResourceScope::GLOBAL === $metadata->scope) {
            return;
        }
        if (ResourceScope::DENY === $metadata->scope) {
            throw $this->denied();
        }

        $field = $this->scopeField($metadata, $classMetadata);
        $subject = $this->subject();
        $classMetadata->setFieldValue(
            $resource,
            $field,
            $classMetadata->hasAssociation($field) ? $subject : $this->fieldValue($classMetadata, $field, $subject),
        );
    }

    /** @param ClassMetadata<object> $classMetadata */
    private function scopeField(ResourceMetadata $metadata, ClassMetadata $classMetadata): string
    {
        $field = $metadata->scopeField;
        if (null === $field || (!$classMetadata->hasField($field) && !$classMetadata->hasAssociation($field))) {
            throw new \LogicException(\sprintf('The subject scope field for API resource "%s" is not mapped by Doctrine.', $metadata->entity));
        }

        return $field;
    }

    private function subject(): SubjectInterface
    {
        $subject = $this->tokenStorage?->getToken()?->getUser();

        return $subject instanceof SubjectInterface ? $subject : throw $this->denied();
    }

    /** @param ClassMetadata<object> $classMetadata */
    private function fieldValue(ClassMetadata $classMetadata, string $field, SubjectInterface $subject): string|Uuid
    {
        return 'uuid' === $classMetadata->getTypeOfField($field)
            ? Uuid::fromString($subject->getSubjectIdentifier())
            : $subject->getSubjectIdentifier();
    }

    private function denied(): ApiProblemException
    {
        return new ApiProblemException(403, 'resource_scope_denied', 'Resource access denied', 'This API resource is not available in the current scope.');
    }
}
