<?php

namespace Perfumerlabs\Perfumer\Step;

abstract class StaticCallStep extends ExpressionStep
{
    /**
     * @var string
     */
    protected $_class;

    /**
     * @var string
     */
    protected $_method;

    public function onCreate(): void
    {
        if ($this->_class[0] !== '\\') {
            $this->_class = '\\' . $this->_class;
        }

        $this->_expression = $this->_class . '::' . $this->_method;

        parent::onCreate();
    }
}
