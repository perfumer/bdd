<?php

namespace Perfumer\Component\Bdd;

class StepParser implements StepParserInterface
{
    /**
     * @param string $value
     * @return string
     */
    public function parseForMethod($value)
    {
        if (substr($value, 0, 5) == 'this.') {
            $value = substr($value, 5);
        }

        return '$' . $value;
    }

    /**
     * @param string $value
     * @return string
     */
    public function parseForCall($value)
    {
        if (substr($value, 0, 5) == 'this.') {
            $value = substr($value, 5);

            return '$this->' . $value;
        } else {
            return '$' . $value;
        }
    }

    /**
     * @param string $value
     * @return string
     */
    public function parseReturn($value)
    {
        if (!$value) {
            return '';
        } elseif (substr($value, 0, 5) == 'this.') {
            $value = substr($value, 5);

            return '$this->' . $value . ' = ';
        } elseif ($value === '_return') {
            return '$_return = ';
        } else {
            return '$' . $value . ' = ';
        }
    }
}
