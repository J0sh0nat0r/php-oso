<?php

namespace J0sh0nat0r\Oso\Tests;

use InvalidArgumentException;
use J0sh0nat0r\Oso\ClassType;
use J0sh0nat0r\Oso\TypeMap;
use J0sh0nat0r\Oso\UserType;
use stdClass;

it('stores named types', function () {
    $typeMap = new TypeMap();

    $userType = new UserType('string', ClassType::fromName('string'), 55, [
        'foo' => 'bar',
    ]);

    $typeMap['string'] = $userType;

    $typeMap['string2'] = $userType;
    unset($typeMap['string2']);

    expect(isset($typeMap['string']))
        ->toBeTrue()
        ->and($typeMap['string'])
        ->toBe($userType)
        ->and(isset($typeMap['string2']))
        ->toBeFalse();
});

it('stores class types', function () {
    $typeMap = new TypeMap();

    $classType = ClassType::fromName('string');
    $userType = new UserType('string', $classType, 74, [
        'foo' => 'bar',
    ]);

    $typeMap[$classType] = $userType;

    $classType2 = ClassType::fromName('boolean');
    $typeMap[$classType2] = $userType;
    unset($typeMap[$classType2]);

    expect(isset($typeMap[$classType]))
        ->toBeTrue()
        ->and($typeMap[$classType])
        ->toBe($userType)
        ->and(isset($typeMap[$classType2]))
        ->toBeFalse();
});

it('is iterable', function () {
    $typeMap = new TypeMap();

    $stringClassType = ClassType::fromName('string');
    $stringUserType = new UserType('string', $stringClassType, 74, [
        'foo' => 'bar',
    ]);
    $typeMap[$stringClassType] = $stringUserType;

    $booleanUserType = new UserType('boolean', ClassType::fromName('boolean'), 23, [
        'foo' => 'bar',
    ]);
    $typeMap['myBoolean'] = $booleanUserType;

    expect($typeMap)
        ->toBeIterable()
        ->and(iterator_to_array($typeMap, false))
        ->toContain($stringUserType, $booleanUserType);
});

it('throws for illegal offset types', function () {
    $typeMap = new TypeMap();

    $userType = new UserType('string', ClassType::fromName('string'), 55, [
        'foo' => 'bar',
    ]);

    expect(static fn () => isset($typeMap[22]))
        ->toThrow(InvalidArgumentException::class, 'Illegal offset type: int')
        ->and(static fn () => $typeMap[true])
        ->toThrow(InvalidArgumentException::class, 'Illegal offset type: bool')
        ->and(static fn () => $typeMap[1.1] = $userType)
        ->toThrow(InvalidArgumentException::class, 'Illegal offset type: float')
        ->and(static function () use ($typeMap) { unset($typeMap[['foo' => 'bar']]); })
        ->toThrow(InvalidArgumentException::class, 'Illegal offset type: array');
});

it('throws for illegal value types', function () {
    $typeMap = new TypeMap();

    expect(static fn () => $typeMap['foo'] = 2.2)
        ->toThrow(InvalidArgumentException::class, 'Illegal value type: float')
        ->and(static fn () => $typeMap['foo'] = new stdClass())
        ->toThrow(InvalidArgumentException::class, 'Illegal value type: stdClass');
});
