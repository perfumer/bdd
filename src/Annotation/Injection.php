<?php

namespace Perfumer\Contracts\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\AnnotationReader;
use Perfumer\Contracts\Annotation;
use Perfumer\Contracts\Decorator\ClassGeneratorDecorator;
use Perfumer\Contracts\Exception\DecoratorException;
use Perfumer\Contracts\Generator\ClassGenerator;
use Perfumer\Contracts\Generator\MethodGenerator;
use Perfumer\Contracts\Generator\StepGenerator;
use Perfumer\Contracts\Generator\TestCaseGenerator;
use Perfumer\Contracts\Step;
use Perfumer\Contracts\Variable\ArgumentVariable;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Injection extends Step implements ArgumentVariable, ClassGeneratorDecorator
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $aliases = [];

    /**
     * @param ClassGenerator $generator
     * @throws DecoratorException
     */
    public function decorateClassGenerator(ClassGenerator $generator): void
    {
        if ($this->type !== null) {
            if (isset($generator->getInjections()[$this->name])) {
                throw new DecoratorException(sprintf('"%s" injection is already defined.',
                    $this->name
                ));
            }

            $generator->addInjection($this->name, $this->type);
        }

        // Rest of code is executed when Injection is used as Step
        if ($this->method === null) {
            return;
        }

        if (!isset($generator->getInjections()[$this->name])) {
            throw new DecoratorException(sprintf('"%s" injection is not registered',
                $this->name
            ));
        }

        $annotation_arguments = $this->arguments;

        $reflection_injection = new \ReflectionClass($generator->getInjections()[$this->name]);

        $method_found = false;

        foreach ($reflection_injection->getMethods() as $method) {
            if ($method->getName() !== $this->method) {
                continue;
            }

            $method_found = true;

            $reader = new AnnotationReader();
            $method_annotations = $reader->getMethodAnnotations($method);
            $tmp_arguments = [];

            foreach ($method->getParameters() as $parameter) {
                $found = false;

                foreach ($method_annotations as $method_annotation) {
                    if ($method_annotation instanceof Inject && $parameter->getName() == $method_annotation->name) {
                        /** @var Annotation $variable */
                        $variable = $method_annotation->variable;
                        $variable->setReflectionClass($this->getReflectionClass());
                        $variable->setReflectionMethod($this->getReflectionMethod());

                        $tmp_arguments[] = $variable;
                        $found = true;
                    }
                }

                if (!$found) {
                    if ($this->arguments) {
                        if ($annotation_arguments) {
                            $tmp_arguments[] = array_shift($annotation_arguments);
                        }
                    } elseif (!$parameter->isOptional()) {
                        $tmp_arguments[] = $parameter->getName();
                    }
                }
            }

            if (count($annotation_arguments) > 0) {
                throw new DecoratorException(sprintf('%s.%s has excessive arguments.',
                    $this->name,
                    $this->method
                ));
            }

            if ($tmp_arguments) {
                $this->arguments = $tmp_arguments;
            }
        }

        if ($method_found === false) {
            throw new DecoratorException(sprintf('method "%s" is not found in "%s".',
                $this->method,
                $this->name
            ));
        }

        foreach ($this->arguments as $i => $argument) {
            if (is_string($argument) && isset($this->aliases[$argument])) {
                $this->arguments[$i] = $this->aliases[$argument];
            }
        }

        parent::decorateClassGenerator($generator);
    }

    /**
     * @param MethodGenerator $generator
     */
    public function decorateMethodGenerator(MethodGenerator $generator): void
    {
        if ($this->method) {
            parent::decorateMethodGenerator($generator);
        }
    }

    /**
     * @param TestCaseGenerator $generator
     */
    public function decorateTestCaseGenerator(TestCaseGenerator $generator): void
    {
        if ($this->method) {
            parent::decorateTestCaseGenerator($generator);
        }
    }

    /**
     * @return string
     */
    public function getArgumentVariableName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getArgumentVariableExpression(): string
    {
        return '$this->_injection_' . $this->name;
    }

    /**
     * @return null|StepGenerator|StepGenerator[]
     * @throws DecoratorException
     */
    public function getGenerator()
    {
        $generator = parent::getGenerator();

        $name = str_replace('_', '', ucwords($this->name, '_.'));

        $generator->setCallExpression("\$this->get{$name}()->");

        return $generator;
    }
}
