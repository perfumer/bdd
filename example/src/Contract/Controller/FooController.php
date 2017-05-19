<?php

namespace Perfumer\Contracts\Example\Contract\Controller;

use Perfumer\Contracts\Annotations\Alias;
use Perfumer\Contracts\Annotations\Call;
use Perfumer\Contracts\Annotations\Context;
use Perfumer\Contracts\Annotations\Custom;
use Perfumer\Contracts\Annotations\Error;
use Perfumer\Contracts\Annotations\Extend;
use Perfumer\Contracts\Annotations\Inject;
use Perfumer\Contracts\Annotations\Output;
use Perfumer\Contracts\Annotations\Property;
use Perfumer\Contracts\Annotations\ServiceObject;
use Perfumer\Contracts\Annotations\ServiceParent;
use Perfumer\Contracts\Annotations\ServiceProperty;
use Perfumer\Contracts\Annotations\Test;
use Perfumer\Contracts\Example\Collection;

/**
 * @Extend(class="\Perfumer\Contracts\Example\ParentController")
 * @Inject(name="iterator", type="\Iterator")
 * @Inject(name="foo", type="\Perfumer\Contracts\Example\FooService")
 * @Inject(name="some_string", type="string")
 */
interface FooController
{
    /**
     * @Call (            method="intType", arguments={"a"}, return="a_valid")
     * @Call (            method="intType", arguments={"a"}, return="param2_valid", if="a_valid")
     * @Call (name="foo", method="bar",                                             if="a_valid")
     * @Collection(steps={
     *   @Call           (               method="sum",                                        return="a"),
     *   @Custom         (               method="sumDoubled",       arguments={"a"},          return="double_sum", if="a"),
     *   @ServiceParent  (               method="sandboxActionTwo", arguments={"a", "staff"}, return={"sand", "box"}),
     *   @ServiceProperty(name="foobar", method="baz",              arguments={"a", "box"},   return=@Output)
     * })
     * @Error(method="fooErrors", unless="a_valid")
     * @Error(method="fooErrors", unless="param2_valid")
     *
     * @Alias(name="a",     variable=@Property("a"))
     * @Alias(name="staff", variable=@Property("staff"))
     * @Alias(name="box",   variable=@Property("box"))
     *
     * @param Output $param2
     * @return string
     */
    public function barAction(Output $param2): string;

    /**
     * @Call           (                method="intType",          arguments={"param1"},                  return="param1_valid")
     * @Call           (                method="intType",          arguments={"param2"},                  return="param2_valid", if="param1_valid")
     * @Call           (                method="sum",              arguments={"param1"},                  return="sum")
     * @ServiceParent  (                method="sandboxActionTwo", arguments={"sum", @Property("staff")}, return="sandbox")
     * @ServiceProperty(name="foobar",  method="baz",              arguments={@Context("default")})
     * @ServiceObject  (name="sandbox", method="execute")
     *
     * @param int $param1
     * @param int $param2
     * @return \DateTime
     */
    public function bazAction(int $param1, int $param2);

    public function skipped();
}

class FooControllerContext
{
    /**
     * @Test
     *
     * @param $value
     * @return bool
     */
    public function intType($value): bool
    {
        return is_int($value);
    }

    /**
     * @Inject(name="staff", variable=@Property("staff"))
     * @Test
     *
     * @param int $a
     * @param int $staff
     * @return int
     */
    public function sum(int $a, int $staff)
    {
        return $a + $staff;
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

    /**
     * @Test
     *
     * @return string
     */
    public function fooErrors()
    {
        return 'Param1 is not valid';
    }
}
