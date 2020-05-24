<?php

namespace Envms\FluentPDO;

class Literal
{
    private string $value = '';

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
