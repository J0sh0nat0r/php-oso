<?php

namespace J0sh0nat0r\Oso\Tests;

use J0sh0nat0r\Oso\ClassType;
use J0sh0nat0r\Oso\DataFiltering\Adapter;
use J0sh0nat0r\Oso\DataFiltering\Filter;
use J0sh0nat0r\Oso\Exceptions\DataFilteringConfigurationException;
use J0sh0nat0r\Oso\Exceptions\DuplicateClassAliasException;
use J0sh0nat0r\Oso\Exceptions\InstantiationException;
use J0sh0nat0r\Oso\Exceptions\InvalidFieldNameException;
use J0sh0nat0r\Oso\Exceptions\UnregisteredClassException;
use J0sh0nat0r\Oso\Exceptions\UnregisteredInstanceException;
use J0sh0nat0r\Oso\Expression;
use J0sh0nat0r\Oso\FFI\Polar;
use J0sh0nat0r\Oso\Host;
use J0sh0nat0r\Oso\Pattern;
use J0sh0nat0r\Oso\PolarComparisonOperator;
use J0sh0nat0r\Oso\PolarOperator;
use J0sh0nat0r\Oso\Predicate;
use J0sh0nat0r\Oso\Relation;
use J0sh0nat0r\Oso\Tests\HostTestSupport\NotSubclass;
use J0sh0nat0r\Oso\Tests\HostTestSupport\Role;
use J0sh0nat0r\Oso\Tests\HostTestSupport\User;
use J0sh0nat0r\Oso\Tests\HostTestSupport\UserProfile;
use J0sh0nat0r\Oso\Tests\HostTestSupport\UserSubclass;
use J0sh0nat0r\Oso\TypeMap;
use J0sh0nat0r\Oso\UserType;
use J0sh0nat0r\Oso\Variable;
use JetBrains\PhpStorm\ArrayShape;
use stdClass;

beforeEach(function () {
    $id = 0;
    $this->polar = mock(Polar::class)
        ->shouldReceive('newId')
        ->andReturnUsing(function () use (&$id) {
            return ++$id;
        })
        ->zeroOrMoreTimes()
        ->getMock();

    $this->host = new Host($this->polar);
});

it('supports updating acceptExpression', function () {
    $this->host->setAcceptExpression(true);

    // Asset that this is reached
    $this->assertTrue(true);
});

it('returns generated name from cacheClass', function () {
    $name = $this->host->cacheClass(ClassType::fromName(User::class), null, []);

    expect($name)->toBe('User');
});

it('returns user provided name from cacheClass', function ($className, $customName) {
    $name = $this->host->cacheClass(ClassType::fromName($className), $customName, []);

    expect($name)->toBe($customName);
})->with([
    Role::class,
    User::class,
    UserSubclass::class,
])->with([
    'Foo',
    'Bar',
    'Baz',
]);

it('throws when caching duplicate classes', function ($firstClass, $firstAlias, $secondAlias) {
    $classType = ClassType::fromName($firstClass);

    $this->host->cacheClass($classType, $firstAlias, []);

    expect(fn () => $this->host->cacheClass($classType, $secondAlias, []))
        ->toThrow(DuplicateClassAliasException::class);
})->with([
    [Role::class, 'Role', 'Role'],
    [User::class, null, null],
    [UserSubclass::class, 'UserSubclass', null],
]);

it('returns the TypeMap', function () {
    $classType = ClassType::fromName('string');

    $this->host->cacheClass($classType, 'myString', [
        'foo' => 'bar',
    ]);

    expect($this->host->getTypes()[$classType])
        ->toBeInstanceOf(UserType::class)
        ->toHaveProperty('name', 'myString')
        ->toHaveProperty('classType', $classType)
        ->toHaveProperty('fields', [
            'foo' => 'bar',
        ]);
});

it('returns cached types', function () {
    $classType = ClassType::fromName('boolean');

    $this->host->cacheClass($classType, 'myBoolean', [
        'foo' => 'bar',
    ]);

    expect($this->host->getType($classType))
        ->toBeInstanceOf(UserType::class)
        ->toHaveProperty('name', 'myBoolean')
        ->toHaveProperty('classType', $classType)
        ->toHaveProperty('fields', [
            'foo' => 'bar',
        ]);
});

it('registers MROs with Polar', function () {
    $this->host->cacheClass(ClassType::fromName('boolean'), null, []);
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);
    $this->host->cacheClass(ClassType::fromName(UserSubclass::class), 'UserSubclass', []);

    $this->polar->shouldReceive('registerMro')
        ->withArgs(['User', []]);
    $this->polar->shouldReceive('registerMro')
        ->withArgs(['UserSubclass', [2]]);

    $this->host->registerMros();
});

it('returns cached instances', function () {
    $instance = new stdClass();

    $this->host->cacheInstance($instance, 3);

    expect($this->host->getInstance(3))->toBe($instance);
});

it('throws when attempting to retrieve unregistered instances', function () {
    expect(fn () => $this->host->getInstance(42))
        ->toThrow(UnregisteredInstanceException::class, 'Unregistered instance: 42');
});

it('remembers cached instance ids', function () {
    $this->host->cacheInstance('foo', 1);

    expect($this->host->hasInstance(1))->toBeTrue()
        ->and($this->host->hasInstance(2))->toBeFalse();
});

it('makes an instance', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    $instance = $this->host->makeInstance(
        'User',
        ['Alice'],
        0
    );

    expect($instance)->toEqual(new User('Alice'));
});

it('throws when attempting to make instances of unregistered types', function () {
    expect(fn () => $this->host->makeInstance('FooBar', [], 0))
        ->toThrow(UnregisteredClassException::class, 'Unregistered class: FooBar.');
});

it('throws when attempting to make instances of primitive types', function () {
    $this->host->cacheClass(ClassType::fromName('boolean'), null, []);

    expect(fn () => $this->host->makeInstance('boolean', [], 0))
        ->toThrow(InstantiationException::class, 'The type boolean is not instantiable');
});

test('isa immediate', function () {
    $this->host->cacheClass(ClassType::fromName('float'), null, []);
    $this->host->cacheClass(ClassType::fromName('boolean'), null, []);

    expect($this->host->isa(float_term('NaN'), 'float'))->toBeTrue()
        ->and($this->host->isa(float_term(1.1), 'boolean'))->toBeFalse();
});

test('isa with cached instances', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);
    $this->host->cacheClass(ClassType::fromName(NotSubclass::class), 'NotSubclass', []);

    $this->host->cacheInstance(new User('Alice'), 1);

    expect($this->host->isa(instance_term(1), 'User'))->toBeTrue()
        ->and($this->host->isa(instance_term(1), 'NotSubclass'))->toBeFalse();
});

test('isaWithPath primitive', function () {
    $this->host->cacheClass(ClassType::fromName('string'), null, []);
    $this->host->cacheClass(ClassType::fromName(UserProfile::class), 'UserProfile', [
        'avatarUrl' => 'string',
    ]);

    expect($this->host->isaWithPath('UserProfile', [string_term('avatarUrl')], 'string'))
        ->toBeTrue()
        ->and($this->host->isaWithPath('UserProfile', [string_term('avatarUrl')], 'UserProfile'))
        ->toBeFalse();
});

test('isaWithPath class', function () {
    $this->host->cacheClass(ClassType::fromName(UserProfile::class), 'UserProfile', []);
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', [
        'profile' => 'UserProfile',
    ]);

    expect($this->host->isaWithPath('User', [string_term('profile')], 'UserProfile'))
        ->toBeTrue()
        ->and($this->host->isaWithPath('User', [string_term('profile')], 'User'))
        ->toBeFalse();
});

test('isaWithPath nested', function () {
    $this->host->cacheClass(ClassType::fromName('string'), null, []);
    $this->host->cacheClass(ClassType::fromName(UserProfile::class), 'UserProfile', [
        'avatarUrl' => 'string',
    ]);
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', [
        'profile' => 'UserProfile',
    ]);

    expect($this->host->isaWithPath('User', [string_term('profile'), string_term('avatarUrl')], 'string'))->toBeTrue()
        ->and($this->host->isaWithPath('User', [string_term('profile'), string_term('avatarUrl')], 'UserProfile'))->toBeFalse();
});

test('isaWithPath one relationship', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', [
        'father' => Relation::one(
            'User',
            'father_name',
            'name'
        ),
    ]);

    expect($this->host->isaWithPath('User', [string_term('father')], 'User'))->toBeTrue();
});

test('isaWithPath many relationship', function () {
    $this->host->cacheClass(ClassType::fromName('array'), null, []);
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', [
        'friends' => Relation::many(
            'User',
            'friend_names',
            'name'
        ),
    ]);

    expect($this->host->isaWithPath('User', [string_term('friends')], 'array'))->toBeTrue();
});

test('isaWithPath throws on invalid field names', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    expect(fn () => $this->host->isaWithPath('User', [int_term(163)], 'User'))
        ->toThrow(InvalidFieldNameException::class, 'int is not a valid field name');
});

test('isaWithPath throws on references to unregistered types', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', [
        'profile' => Relation::one(
            'UserProfile',
            'name',
            'user_name'
        ),
    ]);

    expect(fn () => $this->host->isaWithPath('User', [string_term('profile')], 'UserProfile'))
        ->toThrow(UnregisteredClassException::class, 'Unregistered class: UserProfile.');
});

it('reports subclasses correctly', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);
    $this->host->cacheClass(ClassType::fromName(UserSubclass::class), 'UserSubclass', []);
    $this->host->cacheClass(ClassType::fromName(NotSubclass::class), 'NotSubclass', []);

    expect($this->host->isSubclass('UserSubclass', 'User'))->toBeTrue()
        ->and($this->host->isSubclass('UserSubclass', 'UserSubclass'))->toBeTrue()
        ->and($this->host->isSubclass('User', 'User'))->toBeTrue()
        ->and($this->host->isSubclass('User', 'NotSubclass'))->toBeFalse()
        ->and($this->host->isSubclass('User', 'UserSubclass'))->toBeFalse();
});

it('computes object equality', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    $this->host->cacheInstance(new User('Alice'), 1);
    $this->host->cacheInstance(new User('Bob'), 2);

    expect($this->host->externalOp(PolarComparisonOperator::Eq, [instance_term(1), instance_term(1)]))->toBeTrue()
        ->and($this->host->externalOp(PolarComparisonOperator::Eq, [instance_term(1), instance_term(2)]))->toBeFalse();
});

it('uses weak comparisons object equality', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    $this->host->cacheInstance(new User('Alice'), 1);
    $this->host->cacheInstance(new User('Alice'), 2);

    expect($this->host->externalOp(PolarComparisonOperator::Eq, [instance_term(1), instance_term(2)]))->toBeTrue();
});

it('computes primitive equality', function () {
    expect($this->host->externalOp(PolarComparisonOperator::Eq, [float_term(1.1), float_term(1.1)]))->toBeTrue()
        ->and($this->host->externalOp(PolarComparisonOperator::Eq, [float_term(1.1), float_term(1.2)]))->toBeFalse();
});

it('uses strict comparisons for primitive equality', function () {
    expect($this->host->externalOp(PolarComparisonOperator::Eq, [float_term(1.1), string_term('1.1')]))->toBeFalse();
});

it('computes object inequality', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    $this->host->cacheInstance(new User('Alice'), 1);
    $this->host->cacheInstance(new User('Bob'), 2);

    expect($this->host->externalOp(PolarComparisonOperator::Neq, [instance_term(1), instance_term(2)]))->toBeTrue()
        ->and($this->host->externalOp(PolarComparisonOperator::Neq, [instance_term(1), instance_term(1)]))->toBeFalse();
});

it('uses weak comparisons object inequality', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    $this->host->cacheInstance(new User('Alice'), 1);
    $this->host->cacheInstance(new User('Alice'), 2);

    expect($this->host->externalOp(PolarComparisonOperator::Neq, [instance_term(1), instance_term(2)]))->toBeFalse();
});

it('handles externalOp', function (string $op, int|float $lhs, int|float $rhs) {
    $expected = match ($op) {
        'Geq' => $lhs >= $rhs,
        'Gt'  => $lhs > $rhs,
        'Leq' => $lhs <= $rhs,
        'Lt'  => $lhs < $rhs
    };

    $lhsTerm = is_int($lhs) ? int_term($lhs) : float_term($lhs);
    $rhsTerm = is_int($rhs) ? int_term($rhs) : float_term($rhs);

    expect($this->host->externalOp(PolarComparisonOperator::from($op), [$lhsTerm, $rhsTerm]))->toBe($expected);
})->with(['Geq', 'Gt', 'Leq', 'Lt'])
    ->with([3.141, NAN, INF, -INF, 42.0, 2.718, 0])
    ->with([NAN, INF, 3.141, 0.577, 42, 0.0]);

it('provides a default adapter', function () {
    expect($this->host->adapter)->toBeInstanceOf(Adapter::class);
});

test('the default adapter throws on buildQuery', function () {
    expect(fn () => $this->host->adapter->buildQuery(new Filter('', [], [], new TypeMap())))
        ->toThrow(DataFilteringConfigurationException::class);
});

test('the default adapter throws on executeQuery', function () {
    expect(fn () => $this->host->adapter->executeQuery(null))
        ->toThrow(DataFilteringConfigurationException::class);
});

it('serializes types', function () {
    $this->host->cacheClass(ClassType::fromName('string'), 'string', []);
    $this->host->cacheClass(ClassType::fromName(Role::class), 'MyRole', [
        'userName' => 'string',
    ]);
    $this->host->cacheClass(ClassType::fromName(User::class), 'MyUser', [
        'name' => 'string',
        'role' => Relation::one(
            'MyRole',
            'name',
            'userName'
        ),
    ]);

    $serializedTypes = $this->host->serializeTypes();

    expect($serializedTypes)
        ->toHaveKeys(['string', 'MyRole', 'MyUser'])
        ->and($serializedTypes['MyRole'])
        ->toHaveProperty('userName', [
            'Base' => [
                'class_tag' => 'string',
            ],
        ])
        ->and($serializedTypes['MyUser'])
        ->toHaveProperty('name', [
            'Base' => [
                'class_tag' => 'string',
            ],
        ])
        ->toHaveProperty('role', Relation::one(
            'MyRole',
            'name',
            'userName'
        ));
});

it('fails to serialize types referencing unregistered classes', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), null, [
        'role' => 'string',
    ]);

    expect(fn () => $this->host->serializeTypes())
        ->toThrow(UnregisteredClassException::class, 'Unregistered class: string.');
});

it('converts primitive values to Polar terms', function ($value, $expected) {
    $term = $this->host->toPolarTerm($value);

    expect($term)->toEqual($expected);
})->with(static function () {
    yield [true, term('Boolean', true)];
    yield [24, int_term(24)];
    yield [6.626, float_term(6.626)];
    yield [NAN, float_term('NaN')];
    yield [INF, float_term('Infinity')];
    yield [-INF, float_term('-Infinity')];
    yield ['Pivity', string_term('Pivity')];
    yield [['foo', 'bar'], term('List', [string_term('foo'), string_term('bar')])];
    yield [['foo' => 'bar'], term('Dictionary', ['fields' => ['foo' => string_term('bar')]])];
});

it('throws when converting arrays with non-string keys', function () {
    $value = [
        'foo' => 'bar',
        2     => 'baz',
    ];

    expect(fn () => $this->host->toPolarTerm($value))
        ->toThrow(\InvalidArgumentException::class, 'Cannot convert array with non-string keys to Polar');
});

it('converts variables to Polar terms', function () {
    $term = $this->host->toPolarTerm(new Variable('test'));

    expect($term)->toEqual(term('Variable', 'test'));
});

it('converts predicates to Polar terms', function () {
    $term = $this->host->toPolarTerm(new Predicate('printf', 'Hello, %s!', 'World'));

    expect($term)->toEqual(term('Call', [
        'name' => 'printf',
        'args' => [
            string_term('Hello, %s!'),
            string_term('World'),
        ],
    ]));
});

it('converts expressions to Polar terms', function (PolarOperator $operator, $lhs, $lhsTerm, $rhs, $rhsTerm) {
    $term = $this->host->toPolarTerm(new Expression(
        $operator,
        $lhs,
        $rhs
    ));

    expect($term)->toEqual(term('Expression', [
        'operator' => $operator->value,
        'args'     => [
            $lhsTerm,
            $rhsTerm,
        ],
    ]));
})->with([
    PolarOperator::Geq,
    PolarOperator::Mul,
    PolarOperator::Sub,
])->with(function () {
    yield [false, term('Boolean', false)];
    yield [24, int_term(24)];
    yield ['Bear', string_term('Bear')];
})->with(function () {
    yield [true, term('Boolean', true)];
    yield [-24, int_term(-24)];
    yield ['Polar', string_term('Polar')];
});

it('converts tag only patterns to Polar terms', function () {
    $term = $this->host->toPolarTerm(new Pattern('MyTag'));

    expect($term)->toEqual(term('Pattern', [
        'Instance' => [
            'tag'    => 'MyTag',
            'fields' => [
                'fields' => new stdClass(),
            ],
        ],
    ]));
});

it('converts field only patterns to Polar terms', function () {
    $term = $this->host->toPolarTerm(new Pattern(fields: [
        'foo' => 'bar',
    ]));

    expect($term)->toEqual(term('Pattern', [
        'Dictionary' => [
            'fields' => [
                'foo' => string_term('bar'),
            ],
        ],
    ]));
});

it('converts full patterns to Polar terms', function () {
    $term = $this->host->toPolarTerm(new Pattern('TheTag', [
        'bar' => 181,
    ]));

    expect($term)->toEqual(term('Pattern', [
        'Instance' => [
            'tag'    => 'TheTag',
            'fields' => [
                'fields' => [
                    'bar' => int_term(181),
                ],
            ],
        ],
    ]));
});

it('converts registered classes to Polar terms', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), null, []);

    $term = $this->host->toPolarTerm(ClassType::fromName(User::class));

    expect($term['value']['ExternalInstance'])
        ->toHaveKey('instance_id')
        ->toHaveKey('repr')
        ->toHaveKey('class_repr', null)
        ->toHaveKey('class_id', 1);
});

it('converts new instances with unregistered types to Polar terms', function () {
    $term = $this->host->toPolarTerm(new User('Bob'));

    expect($term['value']['ExternalInstance'])
        ->toHaveKey('instance_id', 1)
        ->toHaveKey('repr')
        ->toHaveKey('class_repr', null)
        ->toHaveKey('class_id', null);
});

it('converts new instances with registered types to Polar terms', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'MyUser', []);

    $term = $this->host->toPolarTerm(new User('Alice'));

    expect($term['value']['ExternalInstance'])
        ->toHaveKey('instance_id', 2)
        ->toHaveKey('repr')
        ->toHaveKey('class_repr', 'MyUser')
        ->toHaveKey('class_id', 1);
});

it('converts values to Polar terms and back again', function ($value) {
    $this->host->setAcceptExpression(true);

    $term = $this->host->toPolarTerm($value);

    if (is_float($value) && is_nan($value)) {
        expect($this->host->toPhp($term))->toBeNan();
    } else {
        expect($this->host->toPhp($term))->toEqual($value);
    }
})->with([
    'foo',
    true,
    false,
    42,
    3.141,
    INF,
    -INF,
    NAN,
    [['foo', 'bar']],
    [['foo' => 'bar', 'baz' => 'qux']],
    new Predicate('print', 'Hello, World!'),
    new Variable('foo'),
    new Expression(PolarOperator::Geq, 5, 10),
    new Pattern('MyTag'),
    new Pattern(fields: [
        'qux' => 22,
    ]),
    new Pattern('TheTag', [
        'test' => 'foo',
    ]),
    ClassType::fromName(User::class),
    new User('Alice'),
]);

#[ArrayShape(['value' => 'array'])]
function instance_term(int $id): array
{
    return term('ExternalInstance', [
        'instance_id' => $id,
    ]);
}

#[ArrayShape(['value' => 'array'])]
function int_term(int $value): array
{
    return term('Number', [
        'Integer' => $value,
    ]);
}

#[ArrayShape(['value' => 'array'])]
function float_term(float|string $value): array
{
    return term('Number', [
        'Float' => $value,
    ]);
}

#[ArrayShape(['value' => 'array'])]
function string_term(string $value): array
{
    return term('String', $value);
}

#[ArrayShape(['value' => 'array'])]
function term(string $type, mixed $data): array
{
    return ['value' => [$type => $data]];
}
