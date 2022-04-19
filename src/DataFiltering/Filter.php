<?php

namespace J0sh0nat0r\Oso\DataFiltering;

use J0sh0nat0r\Oso\Host;
use J0sh0nat0r\Oso\TypeMap;

class Filter
{
    /**
     * @param array<Relation> $relations
     * @param array<array<Condition>> $conditions
     */
    public function __construct(
        public string  $root,
        public array   $relations,
        public array   $conditions,
        public TypeMap $types
    )
    {
    }

    public static function parse(Host $host, array $data): self
    {
        $relations = array_map(
            Relation::parse(...),
            $data['relations']
        );

        $conditions = array_map(
            static fn(array $andGroup) => array_map(
                static fn(array $conditionData) => Condition::parse($host, $conditionData),
                $andGroup
            ),
            $data['conditions']
        );

        return new self(
            $data['root'],
            $relations,
            $conditions,
            $host->getTypes()
        );
    }
}
