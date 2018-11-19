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

         // error state
        if (isset($details['transaction_general_error']) && $details['transaction_general_error'] === true) {
            $request->markFailed();
            return;
        }

        // refund state
        if (isset($details['transaction_refunding_failed']) && $details['transaction_refunding_failed'] === true) {
            $request->markFailed();
            return;
        }

        if (isset($details['transaction_refunded'])) {
            if ($details['transaction_refunded'] === true) {
                $request->markRefunded();
            } else {
                $request->markFailed();
            }
            return;
        }

        // cancel state
        if (isset($details['transaction_cancelling_failed']) && $details['transaction_cancelling_failed'] === true) {
            $request->markFailed();
            return;
        }

        if (isset($details['transaction_cancelled'])) {
            if ($details['transaction_cancelled'] === true) {
                $request->markCanceled();
            } else {
                $request->markFailed();
            }
            return;
        }

        // capture state
        if (isset($details['transaction_processing_failed']) && $details['transaction_processing_failed'] === true) {
            $request->markFailed();
            return;
        }

        if (isset($details['transaction_accepted'])) {
            if ($details['transaction_accepted'] === true && $details['transaction_captured'] === true) {
                $request->markCaptured();
            } elseif ($details['transaction_accepted'] === true && $details['transaction_captured'] === false) {
                $request->markAuthorized();
            } else {
                $request->markFailed();
            }
            return;
        }

        // authorize state
        if (isset($details['transaction_authorization_failed']) && $details['transaction_authorization_failed'] === true) {
            $request->markFailed();
            return;
        }

        if (isset($details['transaction_authorized'])) {
            if ($details['transaction_authorized'] === true) {
                $request->markAuthorized();
            } else {
                $request->markFailed();
            }
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
