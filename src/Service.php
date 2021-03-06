<?php

namespace Barman;

abstract class Service extends Step
{
    /**
     * @return string
     */
    abstract public function getCallExpression(): string;

    public function onMutate(): void
    {
        parent::onMutate();

        $this->getStepKeeper()->setCallExpression($this->getCallExpression());
    }
}
