<?php

namespace Nelmio\ApiDocBundle\SpecDescribers;

use OpenApi\Contracts\AttributeInterface;
use OpenApi\Merge;
use OpenApi\Spec as OAS;
use OpenApi\Undefined;

/**
 * Merge\Operations, with requestBody folded the way nelmio's Util::merge folds it: media types by
 * key, schemas keyword by keyword, properties by name, all the way down. The earlier wins where
 * both set a value (Mode::First semantics), the later fills what the earlier leaves unset.
 *
 * Probe for finding 8: can the shipped merger API be extended to a deep fold?
 */
final class DeepOperations extends Merge\Operations
{
    public function __construct()
    {
        parent::__construct(Merge\Mode::First);
    }

    public function merge(AttributeInterface $earlier, AttributeInterface $later): AttributeInterface
    {
        $body = $earlier instanceof OAS\Operation && $later instanceof OAS\Operation
            && $earlier->requestBody instanceof OAS\RequestBody && $later->requestBody instanceof OAS\RequestBody
            ? $this->foldBody($earlier->requestBody, $later->requestBody)
            : null;

        $survivor = parent::merge($earlier, $later);
        if (null !== $body && $survivor instanceof OAS\Operation) {
            $survivor->requestBody = $body;
        }

        return $survivor;
    }

    private function foldBody(OAS\RequestBody $earlier, OAS\RequestBody $later): OAS\RequestBody
    {
        $body = clone $earlier;
        foreach (['description', 'required'] as $field) {
            if (self::isUnset($body->{$field})) {
                $body->{$field} = $later->{$field};
            }
        }
        $body->content = $this->keyedFold($earlier->content, $later->content, static fn (OAS\MediaType $m) => $m->mediaType, function (OAS\MediaType $a, OAS\MediaType $b): OAS\MediaType {
            $media = clone $a;
            $media->schema = $a->schema && $b->schema ? $this->foldSchema($a->schema, $b->schema) : ($a->schema ?? $b->schema);

            return $media;
        });

        return $body;
    }

    private function foldSchema(OAS\Schema $earlier, OAS\Schema $later): OAS\Schema
    {
        $schema = clone $earlier;
        foreach (get_object_vars($later) as $field => $value) {
            if ('properties' === $field || self::isUnset($value)) {
                continue;
            }
            $current = $schema->{$field};
            if (self::isUnset($current)) {
                $schema->{$field} = $value;
            } elseif ($current instanceof OAS\Schema && $value instanceof OAS\Schema) {
                $schema->{$field} = $this->foldSchema($current, $value);
            }
        }
        $schema->properties = $this->keyedFold($earlier->properties, $later->properties, static fn (OAS\Property $p) => $p->property, function (OAS\Property $a, OAS\Property $b): OAS\Property {
            $property = clone $a;
            $property->schema = $a->schema && $b->schema ? $this->foldSchema($a->schema, $b->schema) : ($a->schema ?? $b->schema);

            return $property;
        });

        return $schema;
    }

    /**
     * @template T of object
     *
     * @param list<T>|null      $earlier
     * @param list<T>|null      $later
     * @param callable(T): ?string $key
     * @param callable(T, T): T $fold
     *
     * @return list<T>|null
     */
    private function keyedFold(?array $earlier, ?array $later, callable $key, callable $fold): ?array
    {
        if (null === $earlier || null === $later) {
            return $earlier ?? $later;
        }
        $result = [];
        foreach ([...$earlier, ...$later] as $item) {
            $k = $key($item);
            if (null !== $k && isset($result[$k])) {
                $result[$k] = $fold($result[$k], $item);
            } elseif (null !== $k) {
                $result[$k] = $item;
            } else {
                $result[] = $item;
            }
        }

        return array_values($result);
    }
}
