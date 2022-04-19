<?php

namespace J0sh0nat0r\Oso;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

class Relation implements JsonSerializable
{
    public function __construct(
        public RelationKind $kind,
        public string $otherType,
        public string $myField,
        public string $otherField
    ) {
    }

    #[ArrayShape(['Relation' => 'array'])]
    public function jsonSerialize(): array
    {
        return [
            'Relation' => [
                'kind'            => $this->kind->value,
                'other_class_tag' => $this->otherType,
                'my_field'        => $this->myField,
                'other_field'     => $this->otherField,
            ],
        ];
    }

    public static function one(string $otherType, string $myField, string $otherField): self
    {
        return new self(RelationKind::One, $otherType, $myField, $otherField);
    }

    public static function many(string $otherType, string $myField, string $otherField): self
    {
        return new self(RelationKind::Many, $otherType, $myField, $otherField);
    }
}
