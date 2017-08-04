<?php

namespace Generated\Tests\Barman\Example;

abstract class Example2Test extends \PHPUnit\Framework\TestCase
{
    final public function testSyntax()
    {
        new \ReflectionClass(\Barman\Example\Example2::class);
    }

    final public function testSumThenProductLocalVariables()
    {
        $a = true;
        $b = true;
        $c = true;
        $this->assertNotEmpty($a);
        $this->assertNotEmpty($b);
        $sum = true;
        $this->assertNotEmpty($sum);
        $this->assertNotEmpty($c);
    }
}