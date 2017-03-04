<?php

namespace Generated\Tests\Perfumer\Component\Bdd\Example\Context;

abstract class FooContextTest extends \PHPUnit_Framework_TestCase
{
    abstract public function intTypeDataProvider();

    abstract public function sumDataProvider();

    abstract public function fooErrorsDataProvider();

    /**
     * @dataProvider intTypeDataProvider
     */
    final public function test_intType($value, $result)
    {
        $_class_instance = new \Perfumer\Component\Bdd\Example\Context\FooContext();

        $this->assertEquals($_class_instance->intType($value), $result);
    }

    /**
     * @dataProvider sumDataProvider
     */
    final public function test_sum($a, $b, $result)
    {
        $_class_instance = new \Perfumer\Component\Bdd\Example\Context\FooContext();

        $this->assertEquals($_class_instance->sum($a, $b), $result);
    }

    /**
     * @dataProvider fooErrorsDataProvider
     */
    final public function test_fooErrors($param1_valid, $param2_valid, $result)
    {
        $_class_instance = new \Perfumer\Component\Bdd\Example\Context\FooContext();

        $this->assertEquals($_class_instance->fooErrors($param1_valid, $param2_valid), $result);
    }

}
