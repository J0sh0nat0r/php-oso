<?php

namespace J0sh0nat0r\Oso\DataFiltering;

use J0sh0nat0r\Oso\Exceptions\InternalErrorException;
use J0sh0nat0r\Oso\Host;
use J0sh0nat0r\Oso\PolarComparisonOperator;

class Condition
{
    public function __construct(
        public Immediate|Projection $lhs,
        public PolarComparisonOperator $cmp,
        public Immediate|Projection $rhs
    ) {
    }

    public static function parse(Host $host, array $data): self
    {
        $lhs = self::parseDatum($host, $data[0]);
        $cmp = PolarComparisonOperator::from($data[1]);
        $rhs = self::parseDatum($host, $data[2]);

        return new self($lhs, $cmp, $rhs);
    }

    protected static function parseDatum(Host $host, array $data): Immediate|Projection
    {
        $type = array_keys($data)[0];

        return match ($type) {
            'Immediate' => Immediate::parse($host, $data['Immediate']),
            'Field'     => Projection::parse($data['Field']),
            default     => throw new InternalErrorException("Unknown datum type: $type")
        };
    }
}
