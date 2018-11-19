<?php

namespace DachcomDigital\Payum\Curabill\Action;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Request\Api\CheckInvoice;
use DachcomDigital\Payum\Curabill\Request\Api\DirectProcess;
use DachcomDigital\Payum\Curabill\Request\Api\OffsiteProcess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;

/**
 * @property Api $api
 */
class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
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
     * @param mixed $request
     *
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        // 1. handle off site redirects!
        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (isset($httpRequest->query['success'])) {
            $details['transaction_accepted'] = true;
            if ($this->api->getProcessingType() === Api::PROCESSING_TYPE_REDIRECT_AUTHORIZE) {
                $details['transaction_captured'] = false;
            }
            return;
        } elseif (isset($httpRequest->query['cancel'])) {
            $details['transaction_cancelled'] = true;
            return;
        } elseif (isset($httpRequest->query['error'])) {
            $details['transaction_general_error'] = true;
            return;
        }

        // 2. authorize payment
        if (!isset($details['transaction_authorized'])) {
            $this->gateway->execute($invoiceCheckRequest = new CheckInvoice($request->getFirstModel()));
            $details->replace($invoiceCheckRequest->getResult());
        }

        // 3. do not process data if authorized was unsuccessfully.
        if (
            (isset($details['transaction_authorization_failed']) && $details['transaction_authorization_failed'] === true) ||
            (isset($details['transaction_authorized']) && $details['transaction_authorized'] === false)
        ) {
            return;
        }

        // 4. check internal processes
        if (in_array($this->api->getProcessingType(), [Api::PROCESSING_TYPE_DIRECT_AUTHORIZE, Api::PROCESSING_TYPE_DIRECT_PROCESS])) {
            $this->gateway->execute(new DirectProcess($details));
            return;
        }

        // 5. check off site processes
        if (in_array($this->api->getProcessingType(), [Api::PROCESSING_TYPE_REDIRECT_AUTHORIZE, Api::PROCESSING_TYPE_REDIRECT_PROCESS])) {
            $offSiteProcessAction = new OffsiteProcess($details);
            $offSiteProcessAction->setToken($request->getToken());
            $this->gateway->execute($offSiteProcessAction);
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
