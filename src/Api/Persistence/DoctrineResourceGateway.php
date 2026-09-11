<?php

declare(strict_types=1);

namespace Nexum\Api\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Nexum\Api\Error\ApiProblemException;
use Nexum\Api\Filter\FilterCriterion;
use Nexum\Api\Filter\FilterOperator;
use Nexum\Api\Metadata\ResourceMetadata;
use Nexum\Api\Operation\Operation;
use Nexum\Api\Pagination\ApiQuery;
use Nexum\Api\Pagination\CursorCodec;
use Nexum\Api\Pagination\PaginationMode;
use Nexum\Api\Pagination\ResourceCollection;
use Nexum\Api\Scope\ResourceScopeProviderInterface;
use Nexum\Entity\Contract\EntityInterface;

final readonly class DoctrineResourceGateway
{
    public function __construct(
        private ManagerRegistry $registry,
        private CursorCodec $cursorCodec,
        private ResourceScopeProviderInterface $scope,
    ) {
    }

    public function find(ResourceMetadata $metadata, string $identifier): ?EntityInterface
    {
        $manager = $this->manager($metadata);
        $classMetadata = $manager->getClassMetadata($metadata->entity);
        $identifierField = $classMetadata->getSingleIdentifierFieldName();
        $builder = $manager->createQueryBuilder()
            ->select('resource')
            ->from($metadata->entity, 'resource')
            ->where('resource.'.$identifierField.' = :nexum_resource_id')
            ->setParameter('nexum_resource_id', $identifier, $classMetadata->getTypeOfField($identifierField));
        $this->scope->restrict($builder, $classMetadata, $metadata, Operation::READ);
        $entity = $builder->getQuery()->getOneOrNullResult();

        return $entity instanceof EntityInterface ? $entity : null;
    }

    public function refresh(ResourceMetadata $metadata, EntityInterface $resource): void
    {
        $this->manager($metadata)->refresh($resource);
    }

    public function initializeScope(ResourceMetadata $metadata, EntityInterface $resource): void
    {
        $manager = $this->manager($metadata);
        $this->scope->initialize($resource, $manager->getClassMetadata($metadata->entity), $metadata);
    }

    public function collection(ResourceMetadata $metadata, ApiQuery $query): ResourceCollection
    {
        $manager = $this->manager($metadata);
        $classMetadata = $manager->getClassMetadata($metadata->entity);
        $builder = $manager->createQueryBuilder()
            ->select('resource')
            ->from($metadata->entity, 'resource');
        $this->scope->restrict($builder, $classMetadata, $metadata, Operation::INDEX);

        foreach ($query->filters as $index => $criterion) {
            if (!$classMetadata->hasField($criterion->field)) {
                throw ApiProblemException::badRequest(\sprintf('Unknown mapped field "%s".', $criterion->field));
            }

            $this->applyFilter($builder, $criterion, $index);
        }

        if (PaginationMode::CURSOR === $query->mode) {
            return $this->cursorCollection($builder, $manager, $metadata, $query);
        }

        foreach ($query->sort as $field => $direction) {
            if (!$classMetadata->hasField($field)) {
                throw ApiProblemException::badRequest(\sprintf('Unknown mapped sort field "%s".', $field));
            }

            $builder->addOrderBy('resource.'.$field, strtoupper($direction));
        }

        if (($query->page - 1) > intdiv(\PHP_INT_MAX, $query->size)) {
            throw ApiProblemException::badRequest('page[number] is too large.', 'page_out_of_range');
        }

        $builder
            ->setFirstResult(($query->page - 1) * $query->size)
            ->setMaxResults($query->size);

        $paginator = new Paginator($builder->getQuery(), false);
        $total = \count($paginator);
        $items = $this->entities(iterator_to_array($paginator->getIterator(), false));

        return new ResourceCollection($items, [
            'pagination' => [
                'mode' => 'page',
                'number' => $query->page,
                'size' => $query->size,
                'total_items' => $total,
                'total_pages' => 0 === $total ? 0 : (int) ceil($total / $query->size),
            ],
            'sort' => $query->sort,
        ]);
    }

    private function cursorCollection(
        QueryBuilder $builder,
        EntityManagerInterface $manager,
        ResourceMetadata $metadata,
        ApiQuery $query,
    ): ResourceCollection {
        $classMetadata = $manager->getClassMetadata($metadata->entity);
        $identifierField = $classMetadata->getSingleIdentifierFieldName();

        if ('id' !== $identifierField) {
            throw new \LogicException('Nexum cursor pagination currently requires a single "id" identifier.');
        }

        if (null !== $query->after) {
            $builder
                ->andWhere('resource.id > :nexum_cursor')
                ->setParameter(
                    'nexum_cursor',
                    $this->cursorCodec->decode($query->after),
                    $classMetadata->getTypeOfField($identifierField),
                );
        }

        $builder
            ->orderBy('resource.id', 'ASC')
            ->setMaxResults($query->size + 1);

        $items = $this->entities($builder->getQuery()->getResult());
        $hasMore = \count($items) > $query->size;

        if ($hasMore) {
            $items = \array_slice($items, 0, $query->size);
        }

        $next = null;

        if ($hasMore && [] !== $items) {
            $identifier = $classMetadata->getIdentifierValues($items[array_key_last($items)]);
            $value = $identifier[$identifierField] ?? null;

            if (!\is_scalar($value) && !$value instanceof \Stringable) {
                throw new \LogicException('Nexum cursor identifiers must be scalar or stringable.');
            }

            $next = $this->cursorCodec->encode((string) $value);
        }

        return new ResourceCollection($items, [
            'pagination' => [
                'mode' => 'cursor',
                'size' => $query->size,
                'has_more' => $hasMore,
                'next' => $next,
            ],
            'sort' => ['id' => 'asc'],
        ]);
    }

    private function applyFilter(QueryBuilder $builder, FilterCriterion $criterion, int $index): void
    {
        $field = 'resource.'.$criterion->field;
        $parameter = 'nexum_filter_'.$index;

        match ($criterion->operator) {
            FilterOperator::EQUALS => $builder->andWhere($builder->expr()->eq($field, ':'.$parameter))->setParameter($parameter, $criterion->value),
            FilterOperator::NOT_EQUALS => $builder->andWhere($builder->expr()->neq($field, ':'.$parameter))->setParameter($parameter, $criterion->value),
            FilterOperator::GREATER_THAN => $builder->andWhere($builder->expr()->gt($field, ':'.$parameter))->setParameter($parameter, $criterion->value),
            FilterOperator::GREATER_THAN_OR_EQUAL => $builder->andWhere($builder->expr()->gte($field, ':'.$parameter))->setParameter($parameter, $criterion->value),
            FilterOperator::LESS_THAN => $builder->andWhere($builder->expr()->lt($field, ':'.$parameter))->setParameter($parameter, $criterion->value),
            FilterOperator::LESS_THAN_OR_EQUAL => $builder->andWhere($builder->expr()->lte($field, ':'.$parameter))->setParameter($parameter, $criterion->value),
            FilterOperator::CONTAINS => $this->applyContains($builder, $field, $parameter, $criterion->value),
            FilterOperator::IN => $this->applyIn($builder, $field, $parameter, $criterion->value),
            FilterOperator::IS_NULL => $this->applyNull($builder, $field, $criterion->value),
        };
    }

    private function applyContains(QueryBuilder $builder, string $field, string $parameter, mixed $value): QueryBuilder
    {
        if (!\is_scalar($value)) {
            throw ApiProblemException::badRequest('The "contains" filter requires a scalar value.');
        }

        $escaped = addcslashes((string) $value, '%_');

        return $builder
            ->andWhere($builder->expr()->like('LOWER('.$field.')', ':'.$parameter))
            ->setParameter($parameter, '%'.strtolower($escaped).'%');
    }

    private function applyIn(QueryBuilder $builder, string $field, string $parameter, mixed $value): QueryBuilder
    {
        $values = \is_array($value) ? array_values($value) : explode(',', (string) $value);

        if ([] === $values) {
            throw ApiProblemException::badRequest('The "in" filter requires at least one value.');
        }

        return $builder
            ->andWhere($builder->expr()->in($field, ':'.$parameter))
            ->setParameter($parameter, $values);
    }

    private function applyNull(QueryBuilder $builder, string $field, mixed $value): QueryBuilder
    {
        $isNull = filter_var($value, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);

        if (null === $isNull) {
            throw ApiProblemException::badRequest('The "is_null" filter requires true or false.');
        }

        return $builder->andWhere($isNull ? $builder->expr()->isNull($field) : $builder->expr()->isNotNull($field));
    }

    private function manager(ResourceMetadata $metadata): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass($metadata->entity);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException(\sprintf('No Doctrine ORM manager found for API resource "%s".', $metadata->entity));
        }

        return $manager;
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return list<EntityInterface>
     */
    private function entities(array $values): array
    {
        $entities = [];

        foreach ($values as $value) {
            if (!$value instanceof EntityInterface) {
                throw new \LogicException('A Nexum API query returned a non-Nexum entity.');
            }

            $entities[] = $value;
        }

        return $entities;
    }
}
