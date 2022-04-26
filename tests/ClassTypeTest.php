<?php

use J0sh0nat0r\Oso\ClassType;
use J0sh0nat0r\Oso\Tests\ClassTypeTestSupport\Animal;
use J0sh0nat0r\Oso\Tests\ClassTypeTestSupport\Cat;
use J0sh0nat0r\Oso\Tests\ClassTypeTestSupport\Dog;

it('can be constructed from primitive type name', function () {
    $classType = ClassType::fromName('array');

    expect($classType->getName())->toBe('array');
});

it('can be constructed from a class name', function () {
    $classType = ClassType::fromName(Animal::class);

    expect($classType->getName())->toBe(Animal::class);
});

it('can be constructed from a class instance', function () {
    $classType = ClassType::fromInstance(new Cat());

    expect($classType->getName())->toBe(Cat::class);
});

it('detects primitive types', function () {
    $classType = ClassType::fromName('boolean');

    expect($classType->isPrimitive())->toBeTrue();
});

it('detects non-primitive types', function () {
    $classType = ClassType::fromName(Dog::class);

    expect($classType->isPrimitive())->toBeFalse();
});

it('detects primitive instances', function () {
    $classType = ClassType::fromInstance(true);

    expect($classType->isInstance(false))->toBeTrue()
        ->and($classType->isInstance(23))->toBeFalse();
});

it('detects non-primitive instances', function () {
    $classType = ClassType::fromName(Dog::class);

    expect($classType->isInstance(new Dog()))->toBeTrue()
        ->and($classType->isInstance(new Cat()))->toBeFalse();
});

it('detects sub-classes', function () {
    $childType = ClassType::fromName(Dog::class);
    $parentType = ClassType::fromName(Animal::class);

    expect($childType->isSubClassOf($parentType))->toBeTrue()
        ->and($parentType->isSubClassOf($childType))->toBeFalse();
});

it('reports no parent type for primitives', function () {
    $classType = ClassType::fromName('float');

    expect($classType->getParentType())->toBeFalse();
});

it('reports no parent type for top-level classes', function () {
    $classType = ClassType::fromName(Animal::class);

    expect($classType->getParentType())->toBeFalse();
});

it('reports correct parent type for sub-classes', function () {
    $childType = ClassType::fromName(Dog::class);
    $parentType = ClassType::fromName(Animal::class);

    expect($childType->getParentType())->toEqual($parentType);
});

it('normalizes double to float', function () {
    $floatType = ClassType::fromName('float');
    $doubleType = ClassType::fromName('double');

    expect($floatType)->toEqual($doubleType);
});

it('normalizes closed resource type to resource', function () {
    $resourceType = ClassType::fromName('resource');
    $closedResourceType = ClassType::fromName('resource (closed)');

    expect($resourceType)->toEqual($closedResourceType);
});
