<?php

namespace DachcomDigital\Payum\Curabill\Action\Api;

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
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Sync;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

/**
 * @property Api $api
 */
class ProcessAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

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
     * @param Process $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if($details['transaction_success'] === true) {
            $details['transaction_processed'] = true;
        }

        $this->gateway->execute(new Sync($request->getModel()));

        throw new HttpResponse('OK', 200);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Process &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
