<?php

namespace Perfumerlabs\Perfumer\Step;

abstract class SharedClassCallStep extends ClassCallStep
{
    public function onCreate(): void
    {
        $name = str_replace('\\', '_', $this->_class);

        $this->_expression = '$this->get_' . $name . '()->' . $this->_method;

        parent::onCreate();
    }

    public function onBuild(): void
    {
        parent::onBuild();

        $this->getClassData()->addSharedClass($this->_class);
    }
}