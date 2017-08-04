<?php

namespace Barman\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\AnnotationReader;
use Barman\Annotation;
use Barman\Exception\MutatorException;
use Barman\Step;
use Barman\Variable\ArgumentVariable;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Injection extends Step implements ArgumentVariable
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
     * @throws MutatorException
     */
    public function onCreate(): void
    {
        if ($this->isClassAnnotation()) {
            if (!is_string($this->name)) {
                throw new MutatorException('Define name of injection.');
            }

            if ($this->getClassKeeper()->hasInjection($this->name)) {
                throw new MutatorException(sprintf('"%s" injection is already defined.',
                    $this->name
                ));
            }

            $this->getClassKeeper()->addInjection($this->name, $this->type);
        }

        // Rest of code is executed when Injection is used as Step
        if (!$this->isMethodAnnotation()) {
            return;
        }

        if (!isset($this->getClassKeeper()->getInjections()[$this->name])) {
            throw new MutatorException(sprintf('"%s" injection is not registered',
                $this->name
            ));
        }

        $annotation_arguments = $this->arguments;

        $reflection_injection = new \ReflectionClass($this->getClassKeeper()->getInjections()[$this->name]);

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
                        $variable->setClassKeeper($this->getClassKeeper());
                        $variable->setMethodKeeper($this->getMethodKeeper());
                        $variable->setTestCaseKeeper($this->getTestCaseKeeper());
                        $variable->setStepKeeper($this->getStepKeeper());

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
                throw new MutatorException(sprintf('%s.%s has excessive arguments.',
                    $this->name,
                    $this->method
                ));
            }

            if ($tmp_arguments) {
                $this->arguments = $tmp_arguments;
            }
        }

        if ($method_found === false) {
            throw new MutatorException(sprintf('method "%s" is not found in "%s".',
                $this->method,
                $this->name
            ));
        }
    }

    public function onMutate(): void
    {
        if ($this->isMethodAnnotation()) {
            parent::onMutate();

            $name = str_replace('_', '', ucwords($this->name, '_.'));

            $this->getStepKeeper()->setCallExpression("\$this->get{$name}()->");
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
}
