<?php

namespace DachcomDigital\Payum\Curabill\Action;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Request\Api\DirectProcess;
use DachcomDigital\Payum\Curabill\Request\Api\OffsiteAuthorize;
use DachcomDigital\Payum\Curabill\Request\Api\OffsiteProcess;
use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
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
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (isset($httpRequest->query['authorize'])) {
            $details['transaction_success'] = true;
            $details['transaction_authorized'] = true;
            return;
        } elseif (isset($httpRequest->query['cancel'])) {
            $details['transaction_cancel'] = true;
            return;
        } elseif (isset($httpRequest->query['error'])) {
            $details['transaction_error'] = true;
            return;
        } elseif (isset($httpRequest->query['success'])) {
            $details['transaction_success'] = true;
            return;
        }

        /** @var $payment PaymentInterface */
        $payment = $request->getFirstModel();

        $transformCustomer = new InvoiceTransformer($payment);
        $transformCustomer->setDocumentType($this->api->getPaymentMethod());
        $this->gateway->execute($transformCustomer);

        $details['invoice'] = base64_encode($this->api->generateInvoiceXml($transformCustomer));
        $details['transaction_token'] = $payment->getNumber();

        if ($this->api->getProcessingType() === Api::PROCESSING_TYPE_REDIRECT_DIRECT) {
            $api = new OffsiteProcess($details);
            $api->setToken($request->getToken());
            $this->gateway->execute($api);
        } elseif ($this->api->getProcessingType() === Api::PROCESSING_TYPE_REDIRECT_MANUALLY) {
            $api = new OffsiteAuthorize($details);
            $api->setToken($request->getToken());
            $this->gateway->execute($api);
        } elseif ($this->api->getProcessingType() === Api::PROCESSING_TYPE_DIRECT) {
            $this->gateway->execute(new DirectProcess($details));
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
