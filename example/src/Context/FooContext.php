<?php

namespace Perfumer\Component\Bdd\Example\Context;

use Perfumer\Component\Bdd\Annotations\Test;

class FooContext
{
    /**
     * @Test
     *
     * @param $value
     * @return null|string
     */
    public function intType($value)
    {
        return is_int($value) ? null : 'must be integer';
    }

    /**
     * @Test
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    public function sum(int $a, int $b)
    {
        return $a + $b;
    }

    /**
     * @param int $a
     * @param int $b
     * @return int
     * @return int
     */
    public function multiply(int $a, int $b)
    {
        return $a * $b;
    }
}
