<?php

namespace Barman\Example\Contract;

use Barman\Annotation\Alias;
use Barman\Annotation\Context;
use Barman\Annotation\Output;

/**
 * Variable aliases.
 *
 * @Context(name="math", class="\Barman\Example\Context\Math")
 */
interface Example7
{
    /**
     * Assume, we want to get the sum of $foo and $bar, also we want to use
     * auto-resolve arguments feature. We can not do it now, because $foo, $bar names are
     * different from $a and $b arguments of context method.
     *
     * Tell Barman, that $foo variable represents $a in the method scope:
     * @Alias(name="a", variable="foo")
     *
     * Tell Barman, that $bar variable represents $b in the method scope:
     * @Alias(name="b", variable="bar")
     *
     * Call context method without arguments provided.
     * @Context(name="math", method="sum", return=@Output())
     *
     * Look at the code, which will be generated by this Contract, in file example/generated/src/Example7.php
     *
     * Go to Example8.
     *
     * @param int $foo
     * @param int $bar
     * @return int
     */
    public function sum(int $foo, int $bar): int;
}
