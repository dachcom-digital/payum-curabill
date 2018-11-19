<?php

namespace DachcomDigital\Payum\Curabill\Action\Api;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Exception\CurabillException;
use DachcomDigital\Payum\Curabill\Request\Api\DirectProcess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;

class DirectProcessAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $details['transaction_accepted'] = false;
        $details['transaction_captured'] = false;

        try {

            if ($this->api->getProcessingType() === Api::PROCESSING_TYPE_DIRECT_PROCESS) {
                $result = $this->api->generateDirectProcessRequest($details, Api::PROCESSING_IMMEDIATE);
            } else {
                $result = $this->api->generateDirectProcessRequest($details, Api::PROCESSING_DEFERRED);
            }

            $status = $result['status'];
            $details['process_status'] = $status['type'];
            $details['process_message'] = $status['message'];

            if ($status['type'] === 'success') {
                $details['transaction_accepted'] = true;
                if ($this->api->getProcessingType() === Api::PROCESSING_TYPE_DIRECT_PROCESS) {
                    $details['transaction_captured'] = true;
                }
            }

            $request->setResult($details);

        } catch (CurabillException $e) {
            $details['transaction_processing_failed'] = true;
            $this->populateDetailsWithError($details, $e, $request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof DirectProcess &&
            $request->getModel() instanceof \ArrayAccess;
    }
}