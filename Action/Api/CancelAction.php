<?php

namespace DachcomDigital\Payum\Curabill\Action\Api;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Exception\CurabillException;
use DachcomDigital\Payum\Curabill\Request\Api\Cancel;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class CancelAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use CurabillAwareTrait;

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
     * @param mixed $request
     *
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty(['transaction_token']);

        $details['transaction_cancelled'] = false;

        try {
            $result = $this->api->generateCancelRequest($details);

            \Pimcore\Logger::log('dings: cancel action', 'info', $result);

            $status = $result['status'];
            $details['cancel_status'] = $status['type'];
            $details['cancel_message'] = $status['message'];

            if ($status['type'] === 'success') {
                $details['transaction_cancelled'] = true;
            }

            $request->setResult($details);

        } catch (CurabillException $e) {
            $details['transaction_cancelling_failed'] = true;
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Cancel &&
            $request->getModel() instanceof \ArrayAccess;
    }
}