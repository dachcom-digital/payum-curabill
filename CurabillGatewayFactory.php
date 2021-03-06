<?php

namespace DachcomDigital\Payum\Curabill;

use DachcomDigital\Payum\Curabill\Action\Api\CancelAction;
use DachcomDigital\Payum\Curabill\Action\Api\CheckInvoiceAction;
use DachcomDigital\Payum\Curabill\Action\Api\ConfirmAction;
use DachcomDigital\Payum\Curabill\Action\Api\DirectProcessAction;
use DachcomDigital\Payum\Curabill\Action\Api\RefundAction;
use DachcomDigital\Payum\Curabill\Action\Api\Transformer\InvoiceTransformerAction;
use DachcomDigital\Payum\Curabill\Action\CaptureAction;
use DachcomDigital\Payum\Curabill\Action\NotifyAction;
use DachcomDigital\Payum\Curabill\Action\OffsiteProcessAction;
use DachcomDigital\Payum\Curabill\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\Sofort\Action\SyncAction;

class CurabillGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([

            'payum.factory_name'  => 'curabill',
            'payum.factory_title' => 'Curabill E-Commerce',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.status'  => new StatusAction(),
            'payum.action.sync'    => new SyncAction(),
            'payum.action.notify'  => new NotifyAction(),

            'payum.action.api.check_invoice'       => new CheckInvoiceAction(),
            'payum.action.api.direct_process'      => new DirectProcessAction(),
            'payum.action.api.offsite_process'     => new OffsiteProcessAction(),
            'payum.action.api.confirm'             => new ConfirmAction(),
            'payum.action.api.cancel'              => new CancelAction(),
            'payum.action.api.refund'              => new RefundAction(),
            'payum.action.api.invoice_transformer' => new InvoiceTransformerAction(),

        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'environment'      => Api::TEST,
                'username'         => '',
                'transactionToken' => '',
                'responseToken'    => '',
                'paymentMethod'    => '',
                'shopCode'         => '',
                'processingType'   => '',
                'sandbox'          => true,
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['username', 'transactionToken', 'paymentMethod', 'shopCode'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    [
                        'sandbox'            => $config['environment'] === Api::TEST,
                        'username'           => $config['username'],
                        'transactionToken'   => $config['transactionToken'],
                        'responseToken'      => $config['responseToken'],
                        'paymentMethod'      => $config['paymentMethod'],
                        'shopCode'           => $config['shopCode'],
                        'processingType'     => $config['processingType'],
                        'optionalParameters' => isset($config['optionalParameters']) ? $config['optionalParameters'] : []
                    ],
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };
        }
    }
}
