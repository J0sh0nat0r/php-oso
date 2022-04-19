<?php

namespace J0sh0nat0r\Oso\Tests\PolarTestSupport;

class MyClass
{
    public function __construct(public int $id, public string $name) {}

    public function myMethod(string $arg): string
    {
        return $arg;
    }

    public function myList(): array
    {
        return ['hello', 'world'];
    }

    public function mySubClass(int $id, string $name): MySubClass
    {
        return new MySubClass($id, $name);
    }

    public function myIterable(): iterable
    {
        yield 'hello';
        yield 'world';
    }

    public static function myStaticMethod(): string
    {
        return 'hello world';
    }

    public function myReturnNull(): void
    {
    }

}