<?php

namespace DachcomDigital\Payum\Curabill;

use DachcomDigital\Payum\Curabill\Exception\CurabillException;
use DachcomDigital\Payum\Curabill\Library\XmlGenerator;
use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;
use GuzzleHttp\Client;
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

    const REDIRECT_TYPE_IMMEDIATE = 'immediate';

    const REDIRECT_TYPE_DEFERRED = 'deferred';

    const PROCESSING_TYPE_DIRECT = 'direct_process';

    const PROCESSING_TYPE_REDIRECT_DIRECT = 'redirect_process';

    const PROCESSING_TYPE_REDIRECT_MANUALLY = 'manually_process';

    protected $options = [
        'environment'         => self::TEST,
        'username'            => null,
        'transactionToken'    => null,
        'responseToken'       => null,
        'paymentMethod'       => 'invoice',
        'shopCode'            => null,
        'useDirectProcessing' => false,
        'processingType'      => self::PROCESSING_TYPE_DIRECT,
        'optionalParameters'  => []
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
     * @return string
     */
    public function useDirectProcessing()
    {
        return $this->options['useDirectProcessing'] === true;
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
    public function getRedirectType()
    {
        if ($this->getProcessingType() === self::PROCESSING_TYPE_REDIRECT_DIRECT) {
            return self::REDIRECT_TYPE_IMMEDIATE;
        } elseif ($this->getProcessingType() === self::PROCESSING_TYPE_REDIRECT_MANUALLY) {
            return self::REDIRECT_TYPE_DEFERRED;
        } else {
            throw new \Exception(sprintf('invalid processingType. valid types: %s, %s', self::PROCESSING_TYPE_REDIRECT_DIRECT, self::PROCESSING_TYPE_REDIRECT_MANUALLY));
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getOffSiteProcessUrl()
    {
        $path = '/services/invoice/process';
        return $this->getProviderHost() . $path;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getDirectProcessUrl()
    {
        $path = '/services/invoice/direct-process';
        return $this->getProviderHost() . $path;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getCaptureUrl()
    {
        $path = '/services/invoice/capture';
        return $this->getProviderHost() . $path;
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        $path = '/services/invoice/cancel';
        return $this->getProviderHost() . $path;
    }

    /**
     * @return string
     */
    public function getRefundUrl()
    {
        $path = '/services/invoice/refund';
        return $this->getProviderHost() . $path;
    }

    /**
     * @return string
     */
    public function getProviderHost()
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
        unset($params['birthdate'], $params['deliveryMethod']);

        $params['payment_method'] = $this->options['paymentMethod'];

        $this->addGlobalParams($params);
        return $params;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws CurabillException
     */
    public function generateDirectProcessRequest(ArrayObject $details)
    {
        $params = [
            'invoice'           => $details['invoice'],
            'transaction_token' => $details['transaction_token']
        ];

        $this->addGlobalParams($params);

        try {
            $response = $this->doRequest($this->getDirectProcessUrl(), $params);
        } catch (\Exception $e) {
            throw new CurabillException($e->getMessage());
        }

        return $response;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws CurabillException
     */
    public function generateConfirmRequest(ArrayObject $details)
    {
        $params = [
            'transaction_token' => $details['transaction_token']
        ];

        $this->addGlobalParams($params);

        try {
            $response = $this->doRequest($this->getCaptureUrl(), $params);
        } catch (\Exception $e) {
            throw new CurabillException($e->getMessage());
        }

        return $response;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws CurabillException
     */
    public function generateCancelRequest(ArrayObject $details)
    {
        $params = [
            'transaction_token' => $details['transaction_token']
        ];

        $this->addGlobalParams($params);

        try {
            $response = $this->doRequest($this->getCancelUrl(), $params);
        } catch (\Exception $e) {
            throw new CurabillException($e->getMessage());
        }

        return $response;
    }

    /**
     * @param ArrayObject $details
     * @return array
     * @throws CurabillException
     */
    public function generateRefundRequest(ArrayObject $details)
    {
        $params = [
            'transaction_token' => $details['transaction_token'],
            'delivery_method'   => $details['deliveryMethod'],
            'refund_id'         => $details['refund_id'],
            'amount'            => $details['amount'],
            'reason'            => $details['reason']
        ];

        $this->addGlobalParams($params);

        try {
            $response = $this->doRequest($this->getRefundUrl(), $params);
        } catch (\Exception $e) {
            throw new CurabillException($e->getMessage());
        }

        return $response;
    }

    /**
     * @param string $url
     * @param array  $fields
     *
     * @return array
     */
    protected function doRequest($url, array $fields)
    {
        $debug = $this->options['sandbox'] === true;
        $client = new Client(['verify' => !$debug, 'query' => $fields, 'debug' => false]);

        $response = $client->request('GET', $url);
        $xmlResponse = $response->getBody()->getContents();

        try {
            $responseData = json_decode(json_encode((array)simplexml_load_string($xmlResponse)), 1);
        } catch (\Exception $e) {
            throw new LogicException("Response content is not valid xml: \n\n{$xmlResponse}");
        }

        return $responseData;
    }

    /**
     * @param  array $params
     */
    protected function addGlobalParams(array &$params)
    {
        $systemParams = [
            'username'  => $this->options['username'],
            'shop_code' => $this->options['shopCode']
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
