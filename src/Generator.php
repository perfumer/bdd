<?php

namespace Perfumerlabs\Perfumer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Barman\Annotation\Test;
use Barman\Exception\ContractsException;
use Barman\Exception\MutatorException;
use Barman\Keeper\ClassKeeper;
use Barman\Keeper\MethodKeeper;
use Barman\Keeper\TestCaseKeeper;
use Perfumerlabs\Perfumer\Annotation\Error;
use Perfumerlabs\Perfumer\Data\ClassData;
use Perfumerlabs\Perfumer\Data\MethodData;
use Perfumerlabs\Perfumer\Data\StepData;
use Perfumerlabs\Perfumer\Data\TestCaseData;
use Perfumerlabs\Perfumer\Step\ContextStep;
use Perfumerlabs\Perfumer\Step\ExpressionStep;
use Perfumerlabs\Perfumer\Step\PlainStep;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;

class Generator
{
    /**
     * @var string
     */
    private $contract_prefix;

    /**
     * @var string
     */
    private $class_prefix;

    /**
     * @var string
     */
    private $context_prefix;

    /**
     * @var string
     */
    private $root_dir;

    /**
     * @var string
     */
    private $base_annotations_path = 'generated/annotation';

    /**
     * @var string
     */
    private $base_src_path = 'generated/src';

    /**
     * @var string
     */
    private $base_test_path = 'generated/tests';

    /**
     * @var string
     */
    private $src_path = 'src';

    /**
     * @var string
     */
    private $test_path = 'tests';

    /**
     * @var array
     */
    private $contracts = [];

    /**
     * @var array
     */
    private $contexts = [];

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @param string $root_dir
     * @param array $options
     */
    public function __construct($root_dir, $options = [])
    {
        $this->reader = new AnnotationReader();

        AnnotationRegistry::registerLoader('class_exists');

        $this->root_dir = $root_dir;

        if (isset($options['contract_prefix'])) {
            $this->contract_prefix = (string) $options['contract_prefix'];
        }

        if (isset($options['class_prefix'])) {
            $this->class_prefix = (string) $options['class_prefix'];
        }

        if (isset($options['context_prefix'])) {
            $this->context_prefix = (string) $options['context_prefix'];
        }

        if (isset($options['base_annotations_path'])) {
            $this->base_annotations_path = (string) $options['base_annotations_path'];
        }

        if (isset($options['base_src_path'])) {
            $this->base_src_path = (string) $options['base_src_path'];
        }

        if (isset($options['base_test_path'])) {
            $this->base_test_path = (string) $options['base_test_path'];
        }

        if (isset($options['src_path'])) {
            $this->src_path = (string) $options['src_path'];
        }

        if (isset($options['test_path'])) {
            $this->test_path = (string) $options['test_path'];
        }
    }

    /**
     * @param string $class
     * @return $this
     */
    public function addContract(string $class)
    {
        $this->contracts[] = $class;

        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function addContext(string $class)
    {
        $this->contexts[] = $class;

        return $this;
    }

    public function generateAll()
    {
        try {
            $bundle = new Bundle();

            foreach ($this->contracts as $class) {
                $reflection = new \ReflectionClass($class);

                $namespace = str_replace($this->contract_prefix, $this->class_prefix, $reflection->getNamespaceName());

                $test_case_keeper = new TestCaseData();

                $test_case_generator = $test_case_keeper->getGenerator();
                $test_case_generator->setNamespaceName('Generated\\Tests\\' . $namespace);
                $test_case_generator->setAbstract(true);
                $test_case_generator->setName($reflection->getShortName() . 'Test');
                $test_case_generator->setExtendedClass('PHPUnit\\Framework\\TestCase');

                $class_keeper = new ClassData();

                $class_generator = $class_keeper->getGenerator();
                $class_generator->setAbstract(true);
                $class_generator->setNamespaceName($namespace);
                $class_generator->setName($reflection->getShortName());

                if ($reflection->isInterface()) {
                    $class_generator->setImplementedInterfaces(array_merge($class_generator->getImplementedInterfaces(), ['\\' . $class]));
                } else {
                    $class_generator->setExtendedClass('\\' . $class);
                }

                $class_annotations = $this->reader->getClassAnnotations($reflection);

                try {
                    foreach ($class_annotations as $annotation) {
                        if (!$annotation instanceof Annotation) {
                            continue;
                        }

                        $annotation->setReflectionClass($reflection);
                        $annotation->setClassKeeper($class_keeper);
                        $annotation->setTestCaseKeeper($test_case_keeper);
                        $annotation->setIsClassAnnotation(true);

                        $annotation->onCreate();
                    }
                } catch (MutatorException $e) {
                    throw new ContractsException(sprintf('%s\\%s: ' . $e->getMessage(),
                        $class_generator->getNamespaceName(),
                        $class_generator->getName()
                    ));
                }

                foreach ($reflection->getMethods() as $method) {
                    $method_keeper = new MethodData();

                    $method_generator = $method_keeper->getGenerator();
                    $method_generator->setFinal(true);
                    $method_generator->setName($method->name);
                    $method_generator->setVisibility('public');

                    if ($method->getReturnType() !== null) {
                        $type = (string) $method->getReturnType();

                        if ($type && !$method->getReturnType()->isBuiltin()) {
                            $type = '\\' . $type;
                        }

                        $method_generator->setReturnType($type);
                    }

                    foreach ($method->getParameters() as $parameter) {
                        $argument = new ParameterGenerator();
                        $argument->setName($parameter->getName());
                        $argument->setPosition($parameter->getPosition());

                        if ($parameter->getType() !== null) {
                            $argument->setType($parameter->getType());
                        }

                        if ($parameter->isDefaultValueAvailable()) {
                            $argument->setDefaultValue($parameter->getDefaultValue());
                        }

                        $method_generator->setParameter($argument);
                    }

                    $method_annotations = $this->reader->getMethodAnnotations($method);

                    try {
                        $validated_returns = [];

                        foreach ($method_annotations as $annotation) {
                            if ($annotation instanceof Error && $annotation->unless) {
                                $validated_returns[] = $annotation->unless;
                            }
                        }

                        foreach ($method_annotations as $annotation) {
                            if ($annotation instanceof ExpressionStep && is_string($annotation->return) && in_array($annotation->return, $validated_returns)) {
                                $annotation->validate = true;
                            }

                            if ($annotation instanceof ContextStep && isset($annotation->out) && in_array($annotation->out, $validated_returns)) {
                                $annotation->validate = true;
                            }

                            $this->onCreateMethodAnnotation($annotation, $reflection, $method, $class_keeper, $test_case_keeper, $method_keeper);

                            if ($annotation instanceof PlainStep) {
                                $method_keeper->addStep($annotation->getStepData());
                            }

//                            if ($annotation instanceof Collection) {
//                                foreach ($annotation->getStepKeepers() as $step_keeper) {
//                                    $method_keeper->addStep($step_keeper);
//                                }
//                            }
                        }

                        foreach ($method_annotations as $annotation) {
                            if ($annotation instanceof ExpressionStep && $annotation->validate === true) {
                                $method_keeper->setValidation(true);
                            }
                        }
                    } catch (MutatorException $e) {
                        throw new ContractsException(sprintf('%s\\%s -> %s: ' . $e->getMessage(),
                            $class_generator->getNamespaceName(),
                            $class_generator->getName(),
                            $method_generator->getName()
                        ));
                    }

                    if (count($method_keeper->getSteps()) > 0) {
                        $method_keeper->generate();

                        $class_generator->addMethodFromGenerator($method_generator);
                    }
                }

                $bundle->addClassKeeper($class_keeper);
                $bundle->addTestCaseKeeper($test_case_keeper);
            }

            foreach ($bundle->getClassKeepers() as $keeper) {
                $this->generateBaseClass($keeper);
                $this->generateClass($keeper);

                $this->contexts = array_merge($this->contexts, array_values($keeper->getContexts()));
            }

            $this->generateContexts();

            foreach ($bundle->getTestCaseKeepers() as $keeper) {
                $this->generateBaseClassTest($keeper);
                $this->generateClassTest($keeper);
            }

            shell_exec("vendor/bin/php-cs-fixer fix {$this->base_annotations_path} --rules=@Symfony");
            shell_exec("vendor/bin/php-cs-fixer fix {$this->base_src_path} --rules=@Symfony");
            shell_exec("vendor/bin/php-cs-fixer fix {$this->base_test_path} --rules=@Symfony");
        } catch (ContractsException $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }

    private function generateContexts()
    {
        try {
            foreach ($this->contexts as $class) {
                $reflection = new \ReflectionClass($class);
                $tests = false;

                $class_generator = new ClassGenerator();

                $namespace = $reflection->getNamespaceName();

                $class_generator->setNamespaceName('Generated\\Tests\\' . $namespace);
                $class_generator->setAbstract(true);
                $class_generator->setName($reflection->getShortName() . 'Test');
                $class_generator->setExtendedClass('PHPUnit\\Framework\\TestCase');

                $data_providers = [];
                $test_methods = [];
                $assertions = [];

                foreach ($reflection->getMethods() as $method) {
                    $method_annotations = $this->reader->getMethodAnnotations($method);

                    foreach ($method_annotations as $annotation) {
                        if ($annotation instanceof Test) {
                            $tests = true;

                            $data_provider = new MethodGenerator();
                            $data_provider->setAbstract(true);
                            $data_provider->setVisibility('public');
                            $data_provider->setName($method->name . 'DataProvider');

                            $data_providers[] = $data_provider;

                            $doc_block = DocBlockGenerator::fromArray([
                                'tags' => [
                                    [
                                        'name'        => 'dataProvider',
                                        'description' => $method->name . 'DataProvider',
                                    ]
                                ],
                            ]);

                            $test = new MethodGenerator();
                            $test->setDocBlock($doc_block);
                            $test->setFinal(true);
                            $test->setVisibility('public');
                            $test->setName('test' . ucfirst($method->name));

                            foreach ($method->getParameters() as $parameter) {
                                $argument = new ParameterGenerator();
                                $argument->setName($parameter->getName());
                                $argument->setPosition($parameter->getPosition());

                                if ($parameter->getType() !== null) {
                                    $argument->setType($parameter->getType());
                                }

                                if ($parameter->isDefaultValueAvailable()) {
                                    $argument->setDefaultValue($parameter->getDefaultValue());
                                }

                                $test->setParameter($argument);
                            }

                            $test->setParameter('expected');

                            $arguments = array_map(function($value) {
                                /** @var \ReflectionParameter $value */
                                return '$' . $value->getName();
                            }, $method->getParameters());

                            $body = '$_class_instance = new \\' . ltrim($class, '\\') . '();' . PHP_EOL . PHP_EOL;
                            $body .= '$this->assertTest' . ucfirst($method->name) . '($expected, $_class_instance->' . $method->name . '(' . implode(', ', $arguments) . '));';

                            $test->setBody($body);

                            $test_methods[] = $test;

                            $assertion = new MethodGenerator();
                            $assertion->setVisibility('protected');
                            $assertion->setName('assertTest' . ucfirst($method->name));
                            $assertion->setParameter('expected');
                            $assertion->setParameter('result');
                            $assertion->setBody('$this->assertEquals($expected, $result);');

                            $assertions[] = $assertion;
                        }
                    }

                    $this->generateAnnotation($reflection, $method);
                }

                foreach ($data_providers as $data_provider) {
                    $class_generator->addMethodFromGenerator($data_provider);
                }

                foreach ($test_methods as $test_method) {
                    $class_generator->addMethodFromGenerator($test_method);
                }

                foreach ($assertions as $assertion) {
                    $class_generator->addMethodFromGenerator($assertion);
                }

                if ($tests) {
                    $this->generateBaseContextTest($class_generator);
                    $this->generateContextTest($class_generator);
                }
            }
        } catch (ContractsException $e) {
            exit($e->getMessage() . PHP_EOL);
        }
    }

    /**
     * @param ClassData $keeper
     */
    private function generateBaseClass(ClassData $keeper)
    {
        $class_generator = $keeper->getGenerator();

        $output_name = str_replace('\\', '/', trim(str_replace($this->class_prefix, '', $class_generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->base_src_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->base_src_path . '/' . $output_name . $class_generator->getName() . '.php';

        $namespace = $class_generator->getNamespaceName();

        $class_generator->setNamespaceName('Generated\\' . $namespace);

        $code = '<?php' . PHP_EOL . PHP_EOL . $keeper->generate();

        file_put_contents($output_name, $code);

        $class_generator->setNamespaceName($namespace);
    }

    private function generateAnnotation(\ReflectionClass $class, \ReflectionMethod $method)
    {
        $namespace = str_replace('\\', '/', $class->getNamespaceName()) . '/' . $class->getShortName();

        $class_name = ucfirst($method->getName());

        @mkdir($this->root_dir . '/' . $this->base_annotations_path . '/' . $namespace, 0777, true);

        $output_name = $this->root_dir . '/' . $this->base_annotations_path . '/' . $namespace . '/' .$class_name . '.php';

        $doc_block = DocBlockGenerator::fromArray([
            'tags' => [
                [
                    'name' => 'Annotation',
                ],
                [
                    'name' => 'Target({"METHOD", "ANNOTATION"})'
                ]
            ],
        ]);

        $class_generator = new ClassGenerator();
        $class_generator->setDocBlock($doc_block);
        $class_generator->setNamespaceName('Generated\\Annotation\\' . $class->getName());
        $class_generator->setName($class_name);
        $class_generator->setExtendedClass('\\Perfumerlabs\\Perfumer\\Step\\ContextStep');

        foreach ($method->getParameters() as $parameter) {
            $doc_block = DocBlockGenerator::fromArray([
                'tags' => [
                    [
                        'name'        => 'var',
                        'description' => 'string',
                    ]
                ],
            ]);

            $property = new PropertyGenerator();
            $property->setDocBlock($doc_block);
            $property->setVisibility('public');
            $property->setName($parameter->getName());
            $property->setDefaultValue($parameter->getName());

            $class_generator->addPropertyFromGenerator($property);
        }

        $doc_block = DocBlockGenerator::fromArray([
            'tags' => [
                [
                    'name'        => 'var',
                    'description' => 'string',
                ]
            ],
        ]);

        $property = new PropertyGenerator();
        $property->setDocBlock($doc_block);
        $property->setVisibility('public');
        $property->setName('out');

        $class_generator->addPropertyFromGenerator($property);

        $method_generator = new MethodGenerator();
        $method_generator->setName('onCreate');
        $method_generator->setVisibility('public');
        $method_generator->setReturnType('void');

        $in = [];

        foreach ($method->getParameters() as $parameter) {
            $in[] = $parameter->getName();
        }

        $in = array_map(function ($v) {
            return '$this->' . $v;
        }, $in);

        $body = '$this->class = \'' . str_replace('\\', '\\\\', $class->getNamespaceName()) . '\\\\' . $class->getShortName() . '\';
        $this->method = \'' . $method->getName() . '\';
        $this->arguments = [' . implode(', ', $in) . '];
        $this->return = $this->out;

        parent::onCreate();';

        $method_generator->setBody($body);

        $class_generator->addMethodFromGenerator($method_generator);

        $code = '<?php' . PHP_EOL . PHP_EOL . $class_generator->generate();

        file_put_contents($output_name, $code);
    }

    /**
     * @param ClassData $keeper
     */
    private function generateClass(ClassData $keeper)
    {
        $class_generator = $keeper->getGenerator();

        $output_name = str_replace('\\', '/', trim(str_replace($this->class_prefix, '', $class_generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->src_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->src_path . '/' . $output_name . $class_generator->getName() . '.php';

        if (is_file($output_name)) {
            return;
        }

        $class = new ClassGenerator();
        $class->setNamespaceName($class_generator->getNamespaceName());
        $class->setName($class_generator->getName());
        $class->setExtendedClass('\\Generated\\' . $class_generator->getNamespaceName() . '\\' . $class_generator->getName());

        foreach ($class_generator->getMethods() as $method_generator) {
            if ($method_generator->isAbstract()) {
                $method = new MethodGenerator();
                $method->setName($method_generator->getName());
                $method->setParameters($method_generator->getParameters());
                $method->setVisibility($method_generator->getVisibility());
                $method->setReturnType($method_generator->getReturnType());
                $method->setBody('throw new \Exception(\'Method "' . $method->getName() . '" is not implemented yet.\');');

                $class->addMethodFromGenerator($method);
            }
        }

        $code = '<?php' . PHP_EOL . PHP_EOL . $class->generate();

        file_put_contents($output_name, $code);
    }

    /**
     * @param TestCaseData $keeper
     */
    private function generateBaseClassTest(TestCaseData $keeper)
    {
        $generator = $keeper->getGenerator();

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->class_prefix, '', $generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->base_test_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->base_test_path . '/' . $output_name . $generator->getName() . '.php';

        $code = '<?php' . PHP_EOL . PHP_EOL . $generator->generate();

        file_put_contents($output_name, $code);
    }

    /**
     * @param TestCaseData $keeper
     */
    private function generateClassTest(TestCaseData $keeper)
    {
        $generator = $keeper->getGenerator();

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->class_prefix, '', $generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->test_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->test_path . '/' . $output_name . $generator->getName() . '.php';

        if (is_file($output_name)) {
            return;
        }

        $class = new ClassGenerator();
        $class->setNamespaceName(str_replace('Generated\\', '', $generator->getNamespaceName()));
        $class->setName($generator->getName());
        $class->setExtendedClass($generator->getNamespaceName() . '\\' . $generator->getName());

        $code = '<?php' . PHP_EOL . PHP_EOL . $class->generate();

        file_put_contents($output_name, $code);
    }

    /**
     * @param ClassGenerator $class_generator
     */
    private function generateBaseContextTest(ClassGenerator $class_generator)
    {
        // If context is from another package
        if (strpos($class_generator->getNamespaceName(), 'Generated\\Tests\\' . $this->context_prefix) !== 0) {
            return;
        }

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->context_prefix, '', $class_generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->base_test_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->base_test_path . '/' . $output_name . $class_generator->getName() . '.php';

        $code = '<?php' . PHP_EOL . PHP_EOL . $class_generator->generate();

        file_put_contents($output_name, $code);
    }

    /**
     * @param ClassGenerator $class_generator
     */
    private function generateContextTest(ClassGenerator $class_generator)
    {
        // If context is from another package
        if (strpos($class_generator->getNamespaceName(), 'Generated\\Tests\\' . $this->context_prefix) !== 0) {
            return;
        }

        $output_name = str_replace('\\', '/', trim(str_replace('Generated\\Tests\\' . $this->context_prefix, '', $class_generator->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        @mkdir($this->root_dir . '/' . $this->test_path . '/' . $output_name, 0777, true);

        $output_name = $this->root_dir . '/' . $this->test_path . '/' . $output_name . $class_generator->getName() . '.php';

        if (is_file($output_name)) {
            return;
        }

        $class = new ClassGenerator();
        $class->setNamespaceName(str_replace('Generated\\', '', $class_generator->getNamespaceName()));
        $class->setName($class_generator->getName());
        $class->setExtendedClass($class_generator->getNamespaceName() . '\\' . $class_generator->getName());

        foreach ($class_generator->getMethods() as $method_generator) {
            if ($method_generator->isAbstract()) {
                $method = new MethodGenerator();
                $method->setName($method_generator->getName());
                $method->setParameters($method_generator->getParameters());
                $method->setVisibility($method_generator->getVisibility());
                $method->setReturnType($method_generator->getReturnType());
                $method->setBody('throw new \Exception(\'Method "' . $method->getName() . '" is not implemented yet.\');');

                $class->addMethodFromGenerator($method);
            }
        }

        $code = '<?php' . PHP_EOL . PHP_EOL . $class->generate();

        file_put_contents($output_name, $code);
    }

    /**
     * @param Annotation $annotation
     * @param \ReflectionClass $reflection
     * @param \ReflectionMethod $method
     * @param ClassKeeper $class_keeper
     * @param TestCaseKeeper $test_case_keeper
     * @param MethodKeeper $method_keeper
     */
    private function onCreateMethodAnnotation(
        $annotation,
        \ReflectionClass $reflection,
        \ReflectionMethod $method,
        ClassData $class_keeper,
        TestCaseData $test_case_keeper,
        MethodData $method_keeper
    )
    {
        if (!$annotation instanceof Annotation) {
            return;
        }

        $annotation->setReflectionClass($reflection);
        $annotation->setReflectionMethod($method);
        $annotation->setClassKeeper($class_keeper);
        $annotation->setTestCaseKeeper($test_case_keeper);
        $annotation->setMethodKeeper($method_keeper);
        $annotation->setIsMethodAnnotation(true);

        if ($annotation instanceof PlainStep) {
            $annotation->setStepKeeper(new StepData());
            $annotation->setStepData(new StepData());
        }

        $annotation->onCreate();

//        if ($annotation instanceof Step && $annotation instanceof AutoArguments) {
//            $this->setAutoArguments($annotation, $reflection, $method, $class_keeper, $test_case_keeper, $method_keeper);
//        }
//
//        if ($annotation instanceof Collection) {
//            foreach ($annotation->steps as $step) {
//                $this->onCreateMethodAnnotation($step, $reflection, $method, $class_keeper, $test_case_keeper, $method_keeper);
//            }
//        }
    }

    /**
     * @param \ReflectionClass $reflection_class
     * @param \ReflectionMethod $reflection_method
     * @param ClassKeeper $class_keeper
     * @param TestCaseKeeper $test_case_keeper
     * @param MethodKeeper $method_keeper
     * @throws MutatorException
     */
//    private function setAutoArguments(
//        Step $step,
//        \ReflectionClass $reflection_class,
//        \ReflectionMethod $reflection_method,
//        ClassKeeper $class_keeper,
//        TestCaseKeeper $test_case_keeper,
//        MethodKeeper $method_keeper
//    )
//    {
//        $current_arguments = $step->arguments;
//
//        $remote_class_reflection = new \ReflectionClass($step->getAutoArgumentsClass());
//        $remote_method_found = false;
//
//        foreach ($remote_class_reflection->getMethods() as $remote_method) {
//            if ($remote_method->getName() !== $step->getAutoArgumentsMethod()) {
//                continue;
//            }
//
//            $remote_method_found = true;
//
//            $reader = new AnnotationReader();
//            $remote_method_annotations = $reader->getMethodAnnotations($remote_method);
//            $new_arguments = [];
//
//            foreach ($remote_method->getParameters() as $parameter) {
//                $found = false;
//
//                foreach ($remote_method_annotations as $remote_method_annotation) {
//                    if ($remote_method_annotation instanceof Inject && $parameter->getName() == $remote_method_annotation->name) {
//                        /** @var Annotation $variable */
//                        $variable = $remote_method_annotation->variable;
//                        $variable->setReflectionClass($reflection_class);
//                        $variable->setReflectionMethod($reflection_method);
//                        $variable->setClassKeeper($class_keeper);
//                        $variable->setMethodKeeper($method_keeper);
//                        $variable->setTestCaseKeeper($test_case_keeper);
//                        $variable->setStepKeeper($step->getStepKeeper());
//
//                        $new_arguments[] = $variable;
//                        $found = true;
//                    }
//                }
//
//                if (!$found) {
//                    if ($step->arguments) {
//                        if ($current_arguments) {
//                            $new_arguments[] = array_shift($current_arguments);
//                        }
//                    } elseif (!$parameter->isOptional()) {
//                        $new_arguments[] = $parameter->getName();
//                    }
//                }
//            }
//
//            if (count($current_arguments) > 0) {
//                throw new MutatorException(sprintf('%s.%s has excessive arguments.',
//                    $step->name,
//                    $step->method
//                ));
//            }
//
//            if ($new_arguments) {
//                $step->arguments = $new_arguments;
//            }
//        }
//
//        if ($remote_method_found === false) {
//            throw new MutatorException(sprintf('method "%s" is not found in "%s".',
//                $step->method,
//                $step->name
//            ));
//        }
//    }
}
