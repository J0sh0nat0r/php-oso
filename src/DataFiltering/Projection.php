<?php

namespace J0sh0nat0r\Oso\DataFiltering;

/**
 * A projection is a type and an optional field, like "User.name", "Post.user_id", or "Tag".
 * If the field name is absent, the adapter should substitute the primary key (eg. "Tag"
 * becomes "Tag.id").
 */
class Projection
{
    public function __construct(public string $typeName, public ?string $fieldName)
    {
    }

    public static function parse(array $data): self
    {
        return new self(...$data);
    }
}
