<?php

namespace DachcomDigital\Payum\Curabill\Action\Api;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Exception\CurabillException;
use DachcomDigital\Payum\Curabill\Request\Api\Confirm;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class ConfirmAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * {@inheritDoc}
     *
     * @param Confirm $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details->validateNotEmpty(['transaction_token', 'invoice']);

        $details['transaction_captured'] = false;

        try {
            $result = $this->api->generateConfirmRequest($details);

            $status = $result['status'];
            $details['confirm_status'] = $status['type'];
            $details['confirm_message'] = $status['message'];

            if ($status['type'] === 'success') {
                $details['transaction_captured'] = true;
            }

            $request->setResult($details);

        } catch (CurabillException $e) {
            $details['transaction_confirm_failed'] = true;
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Confirm &&
            $request->getModel() instanceof \ArrayAccess;
    }
}