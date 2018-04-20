<?php

namespace DachcomDigital\Payum\Curabill\Action;

use DachcomDigital\Payum\Curabill\Api;
use DachcomDigital\Payum\Curabill\Request\Api\OffsiteAuthorize;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpPostRedirect;
use League\Uri\Http as HttpUri;
use League\Uri\Modifiers\MergeQuery;

/**
 * @property Api $api
 */
class OffsiteAuthorizeAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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
     * @param OffsiteAuthorize $request
     * @throws \Exception
     * @throws \Payum\Core\Reply\ReplyInterface
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $targetUri = HttpUri::createFromString($request->getToken()->getTargetUrl());

        $successModifier = new MergeQuery('success=1&authorize=1');
        $successUri = $successModifier->process($targetUri);
        $model['success_url'] = (string)$successUri;

        $errorModifier = new MergeQuery('error=1');
        $errorUri = $errorModifier->process($targetUri);
        $model['error_url'] = (string)$errorUri;

        $cancelModifier = new MergeQuery('cancel=1');
        $cancelUri = $cancelModifier->process($targetUri);
        $model['cancel_url'] = (string)$cancelUri;

        $model['processing'] = Api::REDIRECT_TYPE_DEFERRED;

        throw new HttpPostRedirect(
            $this->api->getOffSiteProcessUrl(),
            $this->api->prepareOffSitePayment($model->toUnsafeArray())
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof OffsiteAuthorize &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
