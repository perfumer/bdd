<?php

namespace Perfumer\Component\Bdd;

class RuntimeAction
{
    /**
     * @var string
     */
    protected $method_name;

    /**
     * @var array
     */
    protected $method_arguments = [];

    /**
     * @var array
     */
    protected $local_variables = [];

    /**
     * @var array
     */
    protected $steps = [];

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->method_name;
    }

    /**
     * @param string $method_name
     */
    public function setMethodName($method_name)
    {
        $this->method_name = $method_name;
    }

    /**
     * @return array
     */
    public function getMethodArguments()
    {
        return $this->method_arguments;
    }

    /**
     * @param array $method_arguments
     */
    public function setMethodArguments($method_arguments)
    {
        $this->method_arguments = $method_arguments;
    }

    /**
     * @param string $method_argument
     */
    public function addMethodArgument($method_argument)
    {
        $this->method_arguments[] = $method_argument;
    }

    /**
     * @return array
     */
    public function getLocalVariables()
    {
        return $this->local_variables;
    }

    /**
     * @param array $local_variables
     */
    public function setLocalVariables($local_variables)
    {
        $this->local_variables = $local_variables;
    }

    /**
     * @param string $local_variable
     * @param string $value
     */
    public function addLocalVariable($local_variable, $value)
    {
        $this->local_variables[$local_variable] = $value;
    }

    /**
     * @param string $local_variable
     * @return bool
     */
    public function hasLocalVariable($local_variable)
    {
        return array_key_exists($local_variable, $this->local_variables);
    }

    /**
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * @param RuntimeStep $step
     */
    public function addStep(RuntimeStep $step)
    {
        $this->steps[] = $step;
    }
}
