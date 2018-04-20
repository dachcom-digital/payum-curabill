<?php

namespace DachcomDigital\Payum\Curabill\Exception;

class CurabillException extends \Exception
{
    public function __toString()
    {
        return $this->getMessage() . ' (#' . $this->code . ')';
    }
}