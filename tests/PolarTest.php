<?php

use J0sh0nat0r\Oso\Exceptions\InlineQueryFailedException;
use J0sh0nat0r\Oso\Polar;
use J0sh0nat0r\Oso\Tests\PolarTestSupport\MyClass;
use J0sh0nat0r\Oso\Tests\PolarTestSupport\MySubClass;
use J0sh0nat0r\Oso\Variable;

beforeEach(function () {
    // Disable error reporting (loading policies without allow triggers warnings)
    error_reporting(E_USER_ERROR);

    $this->polar = new Polar();
    $this->polar->registerClass(MyClass::class, 'MyClass');
    $this->polar->registerClass(MySubClass::class, 'MySubClass');
});

test('load and query string', function () {
    $this->polar->loadStr('f(1);');
    $query = $this->polar->query('f(x)');
    $this->assertEquals([['x' => 1]], iterator_to_array($query));
});

test('inline queries', function () {
    $this->polar->loadStr('f(1); ?= f(1);');
    $this->polar->clearRules();

    $this->expectException(InlineQueryFailedException::class);
    $this->polar->loadStr('f(1); ?= f(2);');
});

test('basic query predicate', function () {
    $this->polar->loadStr('f(a, b) if a = b;');

    $this->assertTrue(
        $this->polar->queryRuleOnce('f', 1, 1),
        'Basic predicate query failed.'
    );
    $this->assertFalse(
        $this->polar->queryRuleOnce('f', 1, 2),
        "Basic predicate query expected to fail but didn't."
    );
});

test('query predicate with object', function () {
    $this->polar->loadStr('f(a, b) if a = b;');

    $this->assertEquals(
        [['result' => 1]],
        iterator_to_array($this->polar->queryRule('f', [], false,1, new Variable('result'))),
        'Predicate query with Variable failed.'
    );
});
