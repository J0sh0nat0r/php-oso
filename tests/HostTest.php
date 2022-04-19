<?php

namespace J0sh0nat0r\Oso\Tests;

use J0sh0nat0r\Oso\ClassType;
use J0sh0nat0r\Oso\FFI\Ffi;
use J0sh0nat0r\Oso\Host;
use J0sh0nat0r\Oso\Tests\HostTestSupport\NotSubclass;
use J0sh0nat0r\Oso\Tests\HostTestSupport\User;
use J0sh0nat0r\Oso\Tests\HostTestSupport\UserSubclass;

beforeEach(function () {
    $this->host = new Host(Ffi::get()->polarNew());
});

it('makes an instance', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);

    $instance = $this->host->makeInstance(
        'User',
        ['Alice'],
        0
    );

    $this->assertEqualsCanonicalizing(new User('Alice'), $instance);
});

test('has instance', function () {
    $this->host->cacheInstance('foo', 1);

    $this->assertTrue($this->host->hasInstance(1));
    $this->assertFalse($this->host->hasInstance(2));
});

test('is subclass', function () {
    $this->host->cacheClass(ClassType::fromName(User::class), 'User', []);
    $this->host->cacheClass(ClassType::fromName(UserSubclass::class), 'UserSubclass', []);
    $this->host->cacheClass(ClassType::fromName(NotSubclass::class), 'NotSubclass', []);

    $this->assertTrue($this->host->isSubclass('UserSubclass', 'User'));
    $this->assertTrue($this->host->isSubclass('UserSubclass', 'UserSubclass'));
    $this->assertTrue($this->host->isSubclass('User', 'User'));
    $this->assertFalse($this->host->isSubclass('User', 'NotSubclass'));
    $this->assertFalse($this->host->isSubclass('User', 'UserSubclass'));
});
