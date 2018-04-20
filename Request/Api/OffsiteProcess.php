<?php

namespace DachcomDigital\Payum\Curabill\Request\Api;

use Payum\Core\Request\Generic;
use Payum\Core\Security\TokenInterface;

class OffsiteProcess extends Generic
{
    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var array
     */
    protected $result;

    /**
     * @param $token
     */
    public function setToken(TokenInterface $token)
    {
        $this->token = $token;
    }

    /**
     * @return TokenInterface
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

}