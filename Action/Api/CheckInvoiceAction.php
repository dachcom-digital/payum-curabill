<?php

namespace DachcomDigital\Payum\Curabill\Action\Api;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Exception\CurabillException;
use DachcomDigital\Payum\Curabill\Request\Api\CheckInvoice;
use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;

class CheckInvoiceAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
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
     * @param CheckInvoice $request
     *
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        /** @var $payment PaymentInterface */
        $payment = $request->getFirstModel();

        $transformCustomer = new InvoiceTransformer($payment);
        $transformCustomer->setDocumentType($this->api->getPaymentMethod());
        $this->gateway->execute($transformCustomer);

        $details['invoice'] = base64_encode($this->api->generateInvoiceXml($transformCustomer));
        $details['transaction_token'] = $payment->getNumber();
        $details['transaction_authorized'] = false;

        try {
            $result = $this->api->generateInvoiceCheckRequest($details);

            $status = $result['status'];
            $details['authorize_status'] = $status['type'];
            $details['authorize_message'] = $status['message'];

            if ($status['type'] === 'success') {
                $details['transaction_authorized'] = true;
            }

            $request->setResult($details);

        } catch (CurabillException $e) {
            $details['transaction_authorization_failed'] = true;
            $this->populateDetailsWithError($details, $e, $request);
            $request->setResult($details);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof CheckInvoice &&
            $request->getModel() instanceof \ArrayAccess;
    }
}