<?php

namespace Perfumer\Contracts;

use Zend\Code\Generator\MethodGenerator;

final class MethodBuilder extends MethodGenerator
{
    /**
     * @var array
     */
    private $initial_variables = [];

    /**
     * @var array
     */
    private $test_variables = [];

    /**
     * @var array
     */
    private $prepended_code = [];

    /**
     * @var array
     */
    private $appended_code = [];

    /**
     * @var array
     */
    private $steps = [];

    /**
     * @var bool
     */
    private $validation = false;

    /**
     * @return array
     */
    public function getInitialVariables(): array
    {
        return $this->initial_variables;
    }

    /**
     * @param array $initial_variables
     */
    public function setInitialVariables(array $initial_variables): void
    {
        $this->initial_variables = $initial_variables;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addInitialVariable(string $name, string $value): void
    {
        $this->initial_variables[$name] = $value;
    }

    /**
     * @return array
     */
    public function getTestVariables(): array
    {
        return $this->test_variables;
    }

    /**
     * @param array $test_variables
     */
    public function setTestVariables(array $test_variables): void
    {
        $this->test_variables = $test_variables;
    }

    /**
     * @param string $name
     * @param bool $assert
     */
    public function addTestVariable(string $name, bool $assert): void
    {
        $this->test_variables[] = [$name, $assert];
    }

    /**
     * @return array
     */
    public function getPrependedCode(): array
    {
        return $this->prepended_code;
    }

    /**
     * @param array $prepended_code
     */
    public function setPrependedCode(array $prepended_code): void
    {
        $this->prepended_code = $prepended_code;
    }

    /**
     * @param string $key
     * @param string $code
     */
    public function addPrependedCode(string $key, string $code): void
    {
        $this->prepended_code[$key] = $code;
    }

    /**
     * @return array
     */
    public function getAppendedCode(): array
    {
        return $this->appended_code;
    }

    /**
     * @param array $appended_code
     */
    public function setAppendedCode(array $appended_code): void
    {
        $this->appended_code = $appended_code;
    }

    /**
     * @param string $key
     * @param string $code
     */
    public function addAppendedCode(string $key, string $code): void
    {
        $this->appended_code[$key] = $code;
    }

    /**
     * @return array
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * @param array $steps
     */
    public function setSteps(array $steps): void
    {
        $this->steps = $steps;
    }

    /**
     * @param StepBuilder $step
     */
    public function addStep(StepBuilder $step): void
    {
        $this->steps[] = $step;
    }

    /**
     * @return bool
     */
    public function hasValidation(): bool
    {
        return $this->validation;
    }

    /**
     * @param bool $validation
     */
    public function setValidation(bool $validation): void
    {
        $this->validation = $validation;
    }
}
