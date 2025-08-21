<?php

namespace Timurikvx\Filebase\Table;

class IndexRow
{
    private int $key;

    private string $type;

    private mixed $value;

    function __construct(string $string)
    {
        $this->key = intval(trim(substr($string, 0, 12)));
        $this->type = trim(substr($string, 12, 14));
        $value = trim(substr($string, 26));
        $this->value = $this->get($value);
    }

    public function key(): int
    {
        return $this->key;
    }

    public function value(): mixed
    {
        return  $this->value;
    }

    public function type(): string
    {
        return $this->type;
    }

    private function get($value)
    {
        if ($this->type == 'float'){
            return floatval($value);
        }
        if ($this->type == 'int'){
            return intval($value);
        }
        if ($this->type == 'bool'){
            return boolval($value);
        }
        if ($this->type == 'datetime'){
            return (new \DateTime())->setTimestamp(intval($value));
        }
        return $value;
    }

}