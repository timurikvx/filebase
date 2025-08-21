<?php

namespace Timurikvx\Filebase\Query;

class Where
{
    public string $field;

    public string $equal;

    public mixed $value;

    public function __construct(string $field, string $equal, mixed $value)
    {
        $this->field = $field;
        $this->equal = $equal;
        $this->value = $value;
    }

}