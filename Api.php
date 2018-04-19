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

    const PROCESSING_IMMEDIATE = 'immediate';

    const PROCESSING_DEFERRED = 'deferred';

    protected $options = [
        'environment'        => self::TEST,
        'username'           => null,
        'transactionToken'   => null,
        'responseToken'      => null,
        'paymentMethod'      => 'invoice',
        'shopCode'           => null,
        'processingType'     => self::PROCESSING_IMMEDIATE,
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
            'responseToken',
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

    /**
     * @param InvoiceTransformer $invoiceTransformer
     * @return mixed
     */
    public function generateInvoiceXml(InvoiceTransformer $invoiceTransformer)
    {
        $xmlGenerator = new XmlGenerator();
        $data = $xmlGenerator->generateInvoiceXml($invoiceTransformer);
        return $data;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getProcessingType()
    {
        return $this->options['processingType'];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getOffSiteUrl()
    {
        $path = '';

        if ($this->options['processingType'] === self::PROCESSING_IMMEDIATE) {
            $endPoint = 'process';
        } elseif ($this->options['processingType'] === self::PROCESSING_DEFERRED) {
            $endPoint = 'capture';
        } else {
            throw new \Exception('invalid processtype. valid types: immediate ore deferred');
        }

        if ($this->options['paymentMethod'] === 'invoice') {
            $path = '/services/invoice/' . $endPoint;
        } elseif ($this->options['paymentMethod'] === 'curapay') {
            $path = '/services/invoice/' . $endPoint;
        }

        if ($this->options['sandbox'] === false) {
            return 'https://prod.middlelayer.curabill.ch' . $path;
        }

        return 'https://int.middlelayer.curabill.ch' . $path;
    }

    /**
     * @param  array $params
     *
     * @return array
     */
    public function prepareOffSitePayment(array $params)
    {
        unset($params['birthdate'], $params['deliveryMethod']);

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
            'username'          => $this->options['username'],
            'shop_code'         => $this->options['shopCode']
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
        ksort($data);
        $hashParts = [];

        foreach ($data as $key => $value) {

            $str = $this->stringValue($value);
            if ($str == '') {
                continue;
            }

            $hashParts[] = strtolower($key) . '=' . $str;
        }

        return hash_hmac('sha512', implode('', $hashParts), $token);
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
