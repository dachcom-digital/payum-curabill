<?php

namespace DachcomDigital\Payum\Curabill\Action;

use DachcomDigital\Payum\Curabill\Api;
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
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use League\Uri\Http as HttpUri;
use League\Uri\Modifiers\MergeQuery;

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

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (isset($httpRequest->query['cancel'])) {
            $model['transaction_cancel'] = true;
            return;
        } elseif (isset($httpRequest->query['error'])) {
            $model['transaction_error'] = true;
            return;
        } elseif (isset($httpRequest->query['success'])) {
            $model['transaction_success'] = true;
            return;
        }

        /** @var $payment PaymentInterface */
        $payment = $request->getFirstModel();


        $transformCustomer = new InvoiceTransformer($request->getFirstModel());
        $transformCustomer->setDocumentType($this->api->getPaymentMethod());
        $this->gateway->execute($transformCustomer);

        $model['invoice'] = base64_encode($this->api->generateInvoiceXml($transformCustomer));

        $targetUri = HttpUri::createFromString($request->getToken()->getTargetUrl());

        $successModifier = new MergeQuery('success=1');
        $successUri = $successModifier->process($targetUri);
        $model['success_url'] = (string)$successUri;

        $errorModifier = new MergeQuery('error=1');
        $errorUri = $errorModifier->process($targetUri);
        $model['error_url'] = (string)$errorUri;

        $cancelModifier = new MergeQuery('cancel=1');
        $cancelUri = $cancelModifier->process($targetUri);
        $model['cancel_url'] = (string)$cancelUri;

        $model['transaction_token'] = $payment->getNumber();
        $model['processing'] = $this->api->getProcessingType();

        $authorizeToken = $this->tokenFactory->createAuthorizeToken(
            $request->getToken()->getGatewayName(),
            $request->getToken()->getDetails(),
            $request->getToken()->getAfterUrl()
        );

        $model['process_url'] = $authorizeToken->getTargetUrl();

        throw new HttpPostRedirect(
            $this->api->getOffSiteUrl(),
            $this->api->prepareOffSitePayment($model->toUnsafeArray())
        );

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
