<?php

namespace DachcomDigital\Payum\Curabill\Action\Api\Transformer;

use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;

class InvoiceTransformerAction implements ActionInterface
{
    use GatewayAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @param InvoiceTransformer $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof InvoiceTransformer &&
            $request->getModel() instanceof \ArrayAccess;
    }
}