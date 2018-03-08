<?php

namespace DachcomDigital\Payum\Curabill;

use DachcomDigital\Payum\Curabill\Library\XmlGenerator;
use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    const TEST = 'test';

    const PRODUCTION = 'production';

    protected $signatureParams = [

    ];

    protected $options = [
        'environment'        => self::TEST,
        'username'           => null,
        'transactionToken'   => null,
        'paymentMethod'      => null,
        'shopCode'           => null,
        'optionalParameters' => []
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'username',
            'transactionToken',
            'paymentMethod',
            'shopCode',
        ]);

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->options['paymentMethod'];
    }

    public function generateInvoiceXml(InvoiceTransformer $invoiceTransformer)
    {
        $xmlGenerator = new XmlGenerator();
        $data = $xmlGenerator->generateInvoiceXml($invoiceTransformer);
        return $data;
    }

    /**
     * @return string
     */
    public function getOffSiteUrl()
    {
        if ($this->options['sandbox'] === false) {
            return 'https://prod.middlelayer.curabill.ch';
        }

        return 'https://int.middlelayer.curabill.ch';
    }

    /**
     * @param  array $params
     *
     * @return array
     */
    public function prepareOffSitePayment(array $params)
    {
        $this->addGlobalParams($params);
        return $params;
    }

    /**
     * @param  array $params
     */
    protected function addGlobalParams(array &$params)
    {
        $systemParams = [
            'payment_method'    => $this->options['paymentMethod'],
            'transaction_token' => $this->options['transactionToken'],
            'username'          => $this->options['username'],
            'shop_code'         => $this->options['shopCode'],
        ];

        $params = array_merge($systemParams, $this->options['optionalParameters'], $params);

        $params['signature'] = $this->createShaHash($params, $this->options['transactionToken']);

    }

    /**
     * @param array $data
     * @param       $token
     * @return string
     */
    public function createShaHash(array $data, $token)
    {
        uksort($data, 'strnatcasecmp');
        $hashParts = [];

        foreach ($data as $key => $value) {
            $str = $this->stringValue($value);
            if ($str == '' || $key == 'signature' || $key == 'transactionToken') {
                continue;
            }
            $hashParts[] = strtolower($key) . '=' . $str . $token;
        }
        return strtolower(hash('sha512', implode('', $hashParts)));
    }

    /**
     * @param $value
     * @return string
     */
    public function stringValue($value)
    {
        if ($value === 0) {
            return '0';
        }

        return (string)$value;
    }
}
