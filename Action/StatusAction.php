<?php

namespace DachcomDigital\Payum\Curabill\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if ($details['transaction_processed'] === true) {
            $request->markCaptured();
            return;
        }
        if ($details['transaction_success'] === true) {
            $request->markAuthorized();
            return;
        }
        if ($details['transaction_refused'] === true) {
            $request->markFailed();
            return;
        }
        if ($details['transaction_error'] === true) {
            $request->markFailed();
            return;
        }
        if ($details['transaction_cancel'] === true) {
            $request->markCanceled();
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
