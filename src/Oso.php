<?php

namespace J0sh0nat0r\Oso;

use J0sh0nat0r\Oso\DataFiltering\Adapter;
use J0sh0nat0r\Oso\DataFiltering\Filter;
use J0sh0nat0r\Oso\Exceptions\OsoException;
use J0sh0nat0r\Oso\Exceptions\UnregisteredClassException;

/**
 * @template TQuery
 * @template TResource
 *
 * @extends Polar<TQuery, TResource>
 */
class Oso extends Polar
{
    protected string $readAction = 'read';

    public function setReadAction(string $readAction): void
    {
        $this->readAction = $readAction;
    }

    public function isAllowed(mixed $actor, mixed $action, mixed $resource): bool
    {
        return $this->queryRuleOnce('allow', $actor, $action, $resource);
    }

    /**
     * Determine the actions `actor` is allowed to take on `resource`.
     *
     * Collects all actions allowed by allow rules in the Polar policy for the given combination of
     * actor and resource.
     */
    public function authorizedActions(mixed $actor, mixed $resource, bool $allowWildcard = false): array
    {
        $query = $this->queryRule('allow', [], false, $actor, new Variable('action'), $resource);

        $actions = [];

        foreach ($query as $action) {
            if ($action['action'] instanceof Variable) {
                if (!$allowWildcard) {
                    throw new OsoException(
                        '"The result of authorizedActions contained an "unconstrained" action that'
                        .' could represent any'.PHP_EOL
                        .' action, but allowWildcard was set to false. To fix,'.PHP_EOL
                        .' set allowWildcard to true and compare with the "*'.PHP_EOL
                        .' string."'
                    );
                }

                $actions['*'] = true;
            } else {
                $actions[$action['action']] = true;
            }
        }

        return array_keys($actions);
    }

    /**
     * Determine the fields of `resource` on which `actor` is allowed to perform `action`.
     *
     * Uses `allow_field` rules in the policy to find all allowed fields.
     */
    public function authorizedFields(mixed $actor, mixed $action, mixed $resource, bool $allowWildcard = false): array
    {
        $query = $this->queryRule('allow_field', [], $actor, $action, $resource, new Variable('field'));

        $fields = [];

        foreach ($query as $field) {
            if ($field['field'] instanceof Variable) {
                if (!$allowWildcard) {
                    throw new OsoException(
                        '"The result of authorizedFields contained an "unconstrained" field that'
                        .' could represent any'.PHP_EOL
                        .' field, but allowWildcard was set to false. To fix,'.PHP_EOL
                        .' set allowWildcard to true and compare with the "*'.PHP_EOL
                        .' string."'
                    );
                }

                $fields['*'] = true;
            } else {
                $fields[$field['field']] = true;
            }
        }

        return array_keys($fields);
    }

    /**
     * @param class-string<TResource> $resourceClass
     *
     * @return TQuery
     */
    public function authorizedQuery(mixed $actor, mixed $action, string $resourceClass)
    {
        $resource = new Variable('resource');

        $resourceClassType = ClassType::fromName($resourceClass);

        $className = $this->host->getType($resourceClassType)?->name;

        if ($className === null) {
            throw new UnregisteredClassException($resourceClassType->getName());
        }

        $constraint = new Expression(
            PolarOperator::And,
            new Expression(
                PolarOperator::Isa,
                $resource,
                new Pattern($className)
            )
        );

        $bindings = [
            'resource' => $constraint,
        ];

        $results = $this->queryRule(
            'allow',
            new QueryOpts(true, $bindings),
            $actor,
            $action,
            $resource,
        );

        $queryResults = [];

        foreach ($results as $result) {
            $queryResults[] = [
                'bindings' => array_map(
                    $this->host->toPolar(...),
                    $result
                ),
            ];
        }

        $dataFilter = $this->ffiPolar->buildDataFilter(
            $this->host->serializeTypes(),
            $queryResults,
            'resource',
            $className
        );

        $filter = Filter::parse($this->host, $dataFilter);

        return $this->host->adapter->buildQuery($filter);
    }

    /**
     * @param Adapter<TQuery, TResource> $adapter
     */
    public function setDataFilteringAdapter(Adapter $adapter): void
    {
        $this->host->adapter = $adapter;
    }
}
