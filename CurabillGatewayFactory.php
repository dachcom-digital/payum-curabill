<?php

namespace DachcomDigital\Payum\Curabill;

use DachcomDigital\Payum\Curabill\Action\Api\Transformer\InvoiceTransformerAction;
use DachcomDigital\Payum\Curabill\Action\AuthorizeAction;
use DachcomDigital\Payum\Curabill\Action\CaptureAction;
use DachcomDigital\Payum\Curabill\Action\ConvertPaymentAction;
use DachcomDigital\Payum\Curabill\Action\StatusAction;
use DachcomDigital\Payum\Curabill\Action\Api\ProcessAction;
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

            'payum.action.capture'         => new CaptureAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.authorize'       => new AuthorizeAction(),
            'payum.action.sync'            => new SyncAction(),

            'payum.action.api.process'             => new ProcessAction(),
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
                        'optionalParameters' => isset($config['optionalParameters']) ? $config['optionalParameters'] : []
                    ],
                    $config['payum.http_client'],
                    $config['httplug.message_factory']
                );
            };
        }
    }
}
