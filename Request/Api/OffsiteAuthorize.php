<?php

namespace DachcomDigital\Payum\Curabill\Request\Api;

use CoreShop\Bundle\PayumBundle\Model\PaymentSecurityToken;
use Payum\Core\Request\Generic;

class OffsiteAuthorize extends Generic
{
    /**
     * @var PaymentSecurityToken
     */
    protected $token;

    /**
     * @var array
     */
    protected $result;

    /**
     * @param $token
     */
    public function setToken(PaymentSecurityToken $token)
    {
        $this->token = $token;
    }

    /**
     * @return PaymentSecurityToken
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