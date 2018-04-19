<?php

namespace DachcomDigital\Payum\Curabill\Action;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Request\Api\Process;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Authorize;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;

/**
 * @property Api $api
 */
class AuthorizeAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }
        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        $postParams = [];
        parse_str($httpRequest->content, $postParams);

        if (isset($postParams['status'])) {
            if ($postParams['status'] === 'success') {
                $details['transaction_success'] = true;
            } elseif ($postParams['status'] === 'refused') {
                $details['transaction_refused'] = true;
            } elseif ($postParams['status'] === 'error') {
                $details['transaction_error'] = true;
            }
            $details['transaction_message'] = $postParams['message'];

            $this->gateway->execute(new Process($details));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Authorize &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
