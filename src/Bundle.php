<?php

namespace Perfumerlabs\Perfumer;

use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\TestCaseData;

final class Bundle
{
    /**
     * @var array
     */
    private $class_keepers = [];

    /**
     * @var array
     */
    private $test_case_keepers = [];

    /**
     * @return ClassData[]
     */
    public function getClassKeepers(): array
    {
        return $this->class_keepers;
    }

    /**
     * @param array $class_keepers
     */
    public function setClassKeepers(array $class_keepers): void
    {
        $this->class_keepers = $class_keepers;
    }

    /**
     * @param ClassData $class_keeper
     */
    public function addClassKeeper(ClassData $class_keeper): void
    {
        $this->class_keepers[] = $class_keeper;
    }

    /**
     * @return TestCaseData[]
     */
    public function getTestCaseKeepers(): array
    {
        return $this->test_case_keepers;
    }

    /**
     * @param array $test_case_keepers
     */
    public function setTestCaseKeepers(array $test_case_keepers): void
    {
        $this->test_case_keepers = $test_case_keepers;
    }

    /**
     * @param TestCaseData $test_case_keeper
     */
    public function addTestCaseKeeper(TestCaseData $test_case_keeper): void
    {
        $this->test_case_keepers[] = $test_case_keeper;
    }
}
