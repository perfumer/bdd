<?php

namespace Generated\Tests\Perfumer\Contracts\Example\Controller;

use PHPUnit\Framework\TestCase;

abstract class FooControllerTest extends TestCase
{
    final public function testSyntax()
    {
        new \ReflectionClass(\Perfumer\Contracts\Example\Controller\FooController::class);
    }

    final public function testBarActionLocalVariables()
    {
        $param2 = true;
        $a_valid = true;
        $this->assertNotEmpty($a_valid);
        $param2_valid = true;
        $this->assertNotEmpty($a_valid);
        $double_sum = true;
        $sand = true;
        $this->assertNotEmpty($a_valid);
        $this->assertNotEmpty($param2_valid);
    }

    final public function testBazActionLocalVariables()
    {
        $param1 = true;
        $param2 = true;
        $this->assertNotEmpty($param1);
        $param1_valid = true;
        $this->assertNotEmpty($param1_valid);
        $this->assertNotEmpty($param2);
        $param2_valid = true;
        $this->assertNotEmpty($param1);
        $sum = true;
        $this->assertNotEmpty($sum);
        $sandbox = true;
    }
}
