<?php

namespace J0sh0nat0r\Oso\Tests;

use J0sh0nat0r\Oso\Exceptions\InlineQueryFailedException;
use J0sh0nat0r\Oso\Polar;
use J0sh0nat0r\Oso\Tests\PolarTestSupport\A;
use J0sh0nat0r\Oso\Tests\PolarTestSupport\BC;
use J0sh0nat0r\Oso\Tests\PolarTestSupport\E;
use J0sh0nat0r\Oso\Variable;

beforeEach(function () {
    // Suppress messages (loading policies without allow triggers warnings)
    \J0sh0nat0r\Oso\FFI\Polar::setMessageHandler(static fn () => null);

    $this->polar = new Polar();
});

test('load and query string', function () {
    $this->polar->loadStr('f(1);');

    $query = $this->polar->query('f(x)');

    expect($query)->toContain(['x' => 1])->toHaveCount(0);
});

it('ignores successful inline queries', function () {
    expect(fn () => $this->polar->loadStr('f(1); ?= f(1);'));

    // Assert that this is reached
    $this->assertTrue(true);
});

it('throws when inline queries fail', function () {
    expect(fn () => $this->polar->loadStr('f(1); ?= f(2);'))
        ->toThrow(InlineQueryFailedException::class);
});

test('basic query predicate', function () {
    $this->polar->loadStr('f(a, b) if a = b;');

    expect($this->polar->queryRuleOnce('f', 1, 1))->toBeTrue()
        ->and($this->polar->queryRuleOnce('f', 1, 2))->toBeFalse();
});

test('query predicate with object', function () {
    $this->polar->loadStr('f(a, b) if a = b;');

    $query = $this->polar->queryRule('f', null, 1, new Variable('result'));

    expect($query)->toContain(['result' => 1])->toHaveCount(0);
});

test('builtin specializers', function (string $literal, string $type, bool $expected) {
    $this->polar->registerClass(A::class, 'A');
    $this->polar->registerClass(BC::class, 'C');
    $this->polar->registerClass(E::class, 'E');

    $this->polar->loadFiles([__DIR__.'/PolarTestSupport/test.polar']);

    $query = $this->polar->query("builtinSpecializers($literal, \"$type\")");

    expect($query->getIterator()->valid())->toBe($expected);
})->with([
    ['true', 'Boolean', true],
    ['false', 'Boolean', false],
    ['2', 'Integer', true],
    ['1', 'Integer', true],
    ['0', 'Integer', false],
    ['-1', 'Integer', false],
    ['1.0', 'Float', true],
    ['0.0', 'Float', false],
    ['-1.0', 'Float', false],
    ['["foo", "bar", "baz"]', 'List', true],
    ['["bar", "foo", "baz"]', 'List', false],
    ['{foo: "foo"}', 'Dictionary', true],
    ['{foo: "bar"}', 'Dictionary', false],
    ['1', 'IntegerWithFields', false],
    ['2', 'IntegerWithGarbageFields', false],
]);
