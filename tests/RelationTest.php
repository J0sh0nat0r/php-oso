<?php

namespace J0sh0nat0r\Oso\Tests;

use J0sh0nat0r\Oso\Relation;

it('serializes "one" relationships correctly', function () {
    $relation = Relation::one(
        'Foo',
        'foo_id',
        'id'
    );

    expect($relation->jsonSerialize())->toBe([
        'Relation' => [
            'kind'            => 'one',
            'other_class_tag' => 'Foo',
            'my_field'        => 'foo_id',
            'other_field'     => 'id',
        ],
    ]);
});

it('serializes "many" relationships correctly', function () {
    $relation = Relation::many(
        'Bar',
        'id',
        'foo_id'
    );

    expect($relation->jsonSerialize())->toBe([
        'Relation' => [
            'kind'            => 'many',
            'other_class_tag' => 'Bar',
            'my_field'        => 'id',
            'other_field'     => 'foo_id',
        ],
    ]);
});
