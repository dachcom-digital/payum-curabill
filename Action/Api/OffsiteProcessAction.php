<?php

namespace DachcomDigital\Payum\Curabill\Action;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Request\Api\OffsiteProcess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use League\Uri\Http as HttpUri;
use League\Uri\Modifiers\MergeQuery;

/**
 * @property Api $api
 */
class OffsiteProcessAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
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
     * @param OffsiteProcess $request
     *
     * @throws \Exception
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        $targetUri = HttpUri::createFromString($request->getToken()->getTargetUrl());

        $errorModifier = new MergeQuery('error=1');
        $errorUri = $errorModifier->process($targetUri);
        $details['error_url'] = (string)$errorUri;

        $cancelModifier = new MergeQuery('cancel=1');
        $cancelUri = $cancelModifier->process($targetUri);
        $details['cancel_url'] = (string)$cancelUri;

        if ($this->api->getProcessingType() === Api::PROCESSING_TYPE_REDIRECT_AUTHORIZE) {
            $details['processing'] = Api::PROCESSING_DEFERRED;
        } else {

            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );

            $details['processing'] = Api::PROCESSING_IMMEDIATE;
            $details['process_url'] = $notifyToken->getTargetUrl();
        }

        $successModifier = new MergeQuery('success=1');
        $successUri = $successModifier->process($targetUri);
        $details['success_url'] = (string)$successUri;

        throw new HttpPostRedirect(
            $this->api->getOffSiteProcessUrl(),
            $this->api->prepareOffSitePayment($details->toUnsafeArray())
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof OffsiteProcess &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
