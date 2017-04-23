<?php

namespace Perfumer\Component\Contracts;

class RuntimeAction
{
    /**
     * @var string
     */
    protected $method_name;

    /**
     * @var array
     */
    protected $header_arguments = [];

    /**
     * @var string
     */
    protected $return_type;

    /**
     * @var array
     */
    protected $local_variables = [];

    /**
     * @var array
     */
    protected $steps = [];

    /**
     * @var bool
     */
    protected $has_validation = false;

    /**
     * @var bool
     */
    protected $has_return = false;

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
    public function getHeaderArguments(): array
    {
        return $this->header_arguments;
    }

    /**
     * @param array $header_arguments
     */
    public function setHeaderArguments(array $header_arguments)
    {
        $this->header_arguments = $header_arguments;
    }

    /**
     * @param string $header_argument
     * @param string $typehint
     */
    public function addHeaderArgument($header_argument, $typehint = '')
    {
        $this->header_arguments[$header_argument] = $typehint;
    }

    /**
     * @return string
     */
    public function getReturnType()
    {
        return $this->return_type;
    }

    /**
     * @param string $return_type
     */
    public function setReturnType($return_type)
    {
        $this->return_type = $return_type;
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

    /**
     * @return bool
     */
    public function hasValidation(): bool
    {
        return $this->has_validation;
    }

    /**
     * @param bool $has_validation
     */
    public function setHasValidation(bool $has_validation)
    {
        $this->has_validation = $has_validation;
    }

    /**
     * @return bool
     */
    public function hasReturn(): bool
    {
        return $this->has_return;
    }

    /**
     * @param bool $has_return
     */
    public function setHasReturn(bool $has_return)
    {
        $this->has_return = $has_return;
    }
}
