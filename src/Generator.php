<?php

namespace Perfumer\Component\Bdd;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Perfumer\Component\Bdd\Annotations\Call;
use Perfumer\Component\Bdd\Annotations\Extend;
use Perfumer\Component\Bdd\Annotations\Format;
use Perfumer\Component\Bdd\Annotations\Service;
use Perfumer\Component\Bdd\Annotations\Validate;

class Generator
{
    /**
     * @var \TwigGenerator\Builder\Generator
     */
    private $generator;

    /**
     * @var string
     */
    private $interface_prefix;

    /**
     * @var string
     */
    private $class_prefix;

    /**
     * @var string
     */
    private $root_dir;

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
    private $classes;

    /**
     * @var array
     */
    private $contexts;

    /**
     * @var StepParserInterface
     */
    private $step_parser;

    /**
     * @param StepParserInterface $step_parser
     * @param string $root_dir
     * @param array $options
     */
    public function __construct(StepParserInterface $step_parser, $root_dir, $options = [])
    {
        $this->generator = new \TwigGenerator\Builder\Generator();
        $this->step_parser = $step_parser;

        $this->root_dir = $root_dir;

        if (isset($options['interface_prefix'])) {
            $this->interface_prefix = (string) $options['interface_prefix'];
        }

        if (isset($options['class_prefix'])) {
            $this->class_prefix = (string) $options['class_prefix'];
        }

        if (isset($options['base_src_path'])) {
            $this->base_src_path = (string) $options['base_src_path'];
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
    public function addClass(string $class)
    {
        $this->classes[] = $class;

        return $this;
    }

    /**
     * @param string $alias
     * @param string $context
     * @return $this
     */
    public function addContext(string $alias, string $context)
    {
        $this->contexts[$alias] = $context;

        return $this;
    }

    public function generate()
    {
        AnnotationRegistry::registerFile(__DIR__ . '/Annotations.php');

        $reader = new AnnotationReader();

        foreach ($this->classes as $class) {
            $reflection = new \ReflectionClass($class);
            $class_annotations = $reader->getClassAnnotations($reflection);

            $runtime_context = new RuntimeContext();

            $namespace = str_replace($this->interface_prefix, $this->class_prefix, $reflection->getNamespaceName());

            $runtime_context->setNamespace($namespace);
            $runtime_context->setClassName($reflection->getShortName());

            foreach ($class_annotations as $annotation) {
                if ($annotation instanceof Extend) {
                    $runtime_context->setExtendsClass($annotation->name);
                }
            }

            foreach ($reflection->getMethods() as $method) {
                $runtime_action = new RuntimeAction();
                $runtime_action->setMethodName($method->name);

                foreach ($method->getParameters() as $parameter) {
                    $runtime_action->addMethodArgument('$' . $parameter->name);
                }

                $method_annotations = $reader->getMethodAnnotations($method);

                foreach ($method_annotations as $annotation) {
                    if (!$annotation instanceof Validate && !$annotation instanceof Call && !$annotation instanceof Service && !$annotation instanceof Format) {
                        continue;
                    }

                    $runtime_step = new RuntimeStep();

                    if ($annotation instanceof Format || $annotation instanceof Validate) {
                        $runtime_step->setContext($this->contexts[$annotation->name]);
                        $runtime_step->setMethod($annotation->method);
                        $runtime_step->setFunctionName($annotation->name . ucfirst($annotation->method));
                    }

                    if ($annotation instanceof Service && $annotation->name) {
                        $runtime_step->setService($annotation->name);
                        $runtime_step->setMethod($annotation->method);

                        if ($annotation->name !== '_parent') {
                            $runtime_context->addProperty($annotation->name);
                        }
                    }

                    if ($annotation instanceof Call) {
                        $runtime_step->setFunctionName($annotation->name);
                    }

                    if ($annotation instanceof Format || $annotation instanceof Service || $annotation instanceof Call) {
                        if ($annotation->return) {
                            $runtime_step->setReturnExpression($this->step_parser->parseReturn($annotation->return));

                            if ($annotation->return != '_return') {
                                if (substr($annotation->return, 0, 5) == 'this.') {
                                    $runtime_context->addProperty(substr($annotation->return, 5));
                                } else {
                                    $runtime_action->addLocalVariable('$' . $annotation->return, null);
                                }
                            }
                        }
                    }

                    if ($annotation instanceof Validate) {
                        $runtime_step->setReturnExpression('$_error = ');
                    }

                    foreach ($annotation->arguments as $argument) {
                        $argument_var = $this->step_parser->parseForMethod($argument);
                        $argument_value = $this->step_parser->parseForCall($argument);

                        $runtime_step->addMethodArgument($argument_var);
                        $runtime_step->addCallArgument($argument_value);

                        if (
                            !in_array($argument_var, $runtime_action->getMethodArguments()) &&
                            !$runtime_action->hasLocalVariable($argument_var) &&
                            substr($argument, 0, 5) !== 'this.'
                        ) {
                            $runtime_action->addLocalVariable($argument_var, $argument_value);
                        }

                        if (substr($argument, 0, 5) == 'this.') {
                            $runtime_context->addProperty(substr($argument, 5));
                        }
                    }

                    if (!$runtime_context->hasStep($runtime_step->getFunctionName())) {
                        $runtime_context->addStep($runtime_step->getFunctionName(), $runtime_step);
                    }

                    $runtime_action->addStep($runtime_step);
                }

                $runtime_context->addAction($runtime_action);
            }

            $this->generateBaseClass($reflection, $runtime_context);
            $this->generateClass($reflection, $runtime_context);

//            if ($tests) {
//                $this->generateBaseTest($context, $runtime_context);
//                $this->generateTest($context, $runtime_context);
//            }
        }
    }

    /**
     * @param \ReflectionClass $reflection
     * @param RuntimeContext $runtime_context
     */
    private function generateBaseClass(\ReflectionClass $reflection, RuntimeContext $runtime_context)
    {
        $output_name = str_replace('\\', '/', trim(str_replace($this->interface_prefix, '', $reflection->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        $output_name = $output_name . $reflection->getShortName() . '.php';

        $builder = new Builder();
        $builder->setMustOverwriteIfExists(true);
        $builder->setTemplateName('BaseClassBuilder.php.twig');
        $builder->addTemplateDir(__DIR__ . '/template');
        $builder->setGenerator($this->generator);
        $builder->setOutputName($output_name);
        $builder->setVariables([
            'context' => $runtime_context
        ]);

        $builder->writeOnDisk($this->root_dir . '/' . $this->base_src_path);
    }

    /**
     * @param \ReflectionClass $reflection
     * @param RuntimeContext $runtime_context
     */
    private function generateClass(\ReflectionClass $reflection, RuntimeContext $runtime_context)
    {
        $output_name = str_replace('\\', '/', trim(str_replace($this->interface_prefix, '', $reflection->getNamespaceName()), '\\'));

        if ($output_name) {
            $output_name .= '/';
        }

        $output_name = $output_name . $reflection->getShortName() . '.php';

        $builder = new Builder();
        $builder->setMustOverwriteIfExists(true);
        $builder->setTemplateName('ClassBuilder.php.twig');
        $builder->addTemplateDir(__DIR__ . '/template');
        $builder->setGenerator($this->generator);
        $builder->setOutputName($output_name);
        $builder->setVariables([
            'context' => $runtime_context
        ]);

        $builder->writeOnDisk($this->root_dir . '/' . $this->src_path);
    }

    /**
     * @param Context $context
     * @param RuntimeContext $runtime_context
     */
//    private function generateBaseTest(Context $context, RuntimeContext $runtime_context)
//    {
//        $output_name = str_replace('\\', '/', $context->getNamespace()) . '/' . $context->getName() . $context->getNameSuffix() . 'Test.php';
//
//        $builder = new Builder();
//        $builder->setMustOverwriteIfExists(true);
//        $builder->setTemplateName('BaseTestBuilder.php.twig');
//        $builder->addTemplateDir(__DIR__ . '/template');
//        $builder->setGenerator($this->generator);
//        $builder->setOutputName($output_name);
//        $builder->setVariables([
//            'context' => $runtime_context
//        ]);
//
//        $builder->writeOnDisk($this->root_dir . '/' . $this->base_test_path . '/' . ucfirst($context->getSrcDir()));
//    }

    /**
     * @param Context $context
     * @param RuntimeContext $runtime_context
     */
//    private function generateTest(Context $context, RuntimeContext $runtime_context)
//    {
//        $output_name = str_replace('\\', '/', $context->getNamespace()) . '/' . $context->getName() . $context->getNameSuffix() . 'Test.php';
//
//        $builder = new Builder();
//        $builder->setMustOverwriteIfExists(false);
//        $builder->setTemplateName('TestBuilder.php.twig');
//        $builder->addTemplateDir(__DIR__ . '/template');
//        $builder->setGenerator($this->generator);
//        $builder->setOutputName($output_name);
//        $builder->setVariables([
//            'context' => $runtime_context
//        ]);
//
//        $builder->writeOnDisk($this->root_dir . '/' . $this->test_path . '/' . ucfirst($context->getSrcDir()));
//    }
}
