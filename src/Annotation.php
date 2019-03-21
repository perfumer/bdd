<?php

namespace Perfumerlabs\Perfumer;

use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\MethodData;
use Perfumerlabs\Perfumer\Data\StepData;
use Perfumerlabs\Perfumer\Data\TestCaseData;

class Annotation
{
    /**
     * @var \ReflectionClass
     */
    private $reflection_class;

    /**
     * @var \ReflectionMethod
     */
    private $reflection_method;

    /**
     * @var ClassData
     */
    private $class_keeper;

    /**
     * @var MethodData
     */
    private $method_keeper;

    /**
     * @var TestCaseData
     */
    private $test_case_keeper;

    /**
     * @var StepData
     */
    private $step_keeper;

    /**
     * @var StepData
     */
    private $step_data;

    /**
     * @var bool
     */
    private $is_class_annotation = false;

    /**
     * @var bool
     */
    private $is_method_annotation = false;

    public function onCreate(): void
    {
    }

    public function onMutate(): void
    {
    }

    /**
     * @return \ReflectionClass|null
     */
    public function getReflectionClass(): ?\ReflectionClass
    {
        return $this->reflection_class;
    }

    /**
     * @param \ReflectionClass $reflection_class
     */
    public function setReflectionClass(\ReflectionClass $reflection_class): void
    {
        $this->reflection_class = $reflection_class;
    }

    /**
     * @return \ReflectionMethod|null
     */
    public function getReflectionMethod(): ?\ReflectionMethod
    {
        return $this->reflection_method;
    }

    /**
     * @param \ReflectionMethod $reflection_method
     */
    public function setReflectionMethod(\ReflectionMethod $reflection_method): void
    {
        $this->reflection_method = $reflection_method;
    }

    /**
     * @return ClassData
     */
    public function getClassKeeper(): ?ClassData
    {
        return $this->class_keeper;
    }

    /**
     * @param ClassData $class_keeper
     */
    public function setClassKeeper(ClassData $class_keeper): void
    {
        $this->class_keeper = $class_keeper;
    }

    /**
     * @return MethodData
     */
    public function getMethodKeeper(): ?MethodData
    {
        return $this->method_keeper;
    }

    /**
     * @param MethodData $method_keeper
     */
    public function setMethodKeeper(MethodData $method_keeper): void
    {
        $this->method_keeper = $method_keeper;
    }

    /**
     * @return TestCaseData
     */
    public function getTestCaseKeeper(): ?TestCaseData
    {
        return $this->test_case_keeper;
    }

    /**
     * @param TestCaseData $test_case_keeper
     */
    public function setTestCaseKeeper(TestCaseData $test_case_keeper): void
    {
        $this->test_case_keeper = $test_case_keeper;
    }

    /**
     * @return StepData
     */
    public function getStepKeeper(): ?StepData
    {
        return $this->step_keeper;
    }

    /**
     * @param StepData $step_keeper
     */
    public function setStepKeeper(StepData $step_keeper): void
    {
        $this->step_keeper = $step_keeper;
    }

    /**
     * @return StepData
     */
    public function getStepData(): ?StepData
    {
        return $this->step_data;
    }

    /**
     * @param StepData $step_data
     */
    public function setStepData(StepData $step_data): void
    {
        $this->step_data = $step_data;
    }

    /**
     * @return bool
     */
    public function isClassAnnotation(): bool
    {
        return $this->is_class_annotation;
    }

    /**
     * @param bool $is_class_annotation
     */
    public function setIsClassAnnotation(bool $is_class_annotation): void
    {
        $this->is_class_annotation = $is_class_annotation;
    }

    /**
     * @return bool
     */
    public function isMethodAnnotation(): bool
    {
        return $this->is_method_annotation;
    }

    /**
     * @param bool $is_method_annotation
     */
    public function setIsMethodAnnotation(bool $is_method_annotation): void
    {
        $this->is_method_annotation = $is_method_annotation;
    }

    /**
     * @return bool
     */
    public function isInnerAnnotation(): bool
    {
        return !$this->is_class_annotation && !$this->is_method_annotation;
    }
}
