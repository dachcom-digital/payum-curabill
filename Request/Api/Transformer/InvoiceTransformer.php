<?php

namespace DachcomDigital\Payum\Curabill\Request\Api\Transformer;

use Payum\Core\Request\Generic;

class InvoiceTransformer extends Generic
{
    /**
     * needs to be one of 'Invoice', 'Curapay', 'Instalment'
     *
     * @var string
     */
    protected $documentType = '';

    /**
     * @var array
     */
    protected $invoiceHeader = [];

    /**
     * @var array
     */
    protected $invoiceItems = [];

    /**
     * @var array
     */
    protected $invoiceFooter = [];

    /**
     * @param string $documentType
     */
    public function setDocumentType(string $documentType)
    {
        $this->documentType = ucfirst($documentType);
    }

    /**
     * @param array $invoiceHeader
     */
    public function setInvoiceHeader(array $invoiceHeader)
    {
        $this->invoiceHeader = $invoiceHeader;
    }

    /**
     * @return array
     */
    public function getInvoiceHeader(): array
    {
        return !empty($this->invoiceHeader) ? $this->invoiceHeader : [
            'basicInformation'    => [
                'documentType'           => $this->documentType,
                'documentNumber'         => '',
                'documentDate'           => '',
                'documentCurrency'       => '',
                'contractIdentification' => '',
                'invoiceDate'            => '',
                'invoiceDeliveryMethod'  => '',
            ],
            'invoicingParty'      => [
                'providerNumber'               => '',
                'customerSystemIdentification' => '',
                'vatNumber'                    => '',
                'companyAddress'               => [
                    'companyName' => '',
                    'zip'         => '',
                    'city'        => '',
                    'country'     => '',
                ],
                'contactPerson'                => [
                    'firstname' => '',
                    'lastname'  => '',
                ]
            ],
            'billtoParty'         => [
                'customerNumberAsInSupplierSystem' => '',
                'vatNumber'                        => '',
                'privateAddress'                   => [
                    'lastname'  => '',
                    'firstname' => '',
                    'zip'       => '',
                    'city'      => '',
                    'country'   => '',
                    'birthday'  => '',
                ],
                'identificationOrganisationUnit'   => '',
                'organisationUnitName'             => '',
                'additionalInformationForContact'  => '',
                'checkAge'                         => false,
                'identityCardNumber'               => '',
            ],
            'deliveryInformation' => [
                'deliveryDate'          => '',
                'startServiceProviding' => '',
                'privateAddress'        => [
                    'lastname'  => '',
                    'firstname' => '',
                    'zip'       => '',
                    'city'      => '',
                    'country'   => '',
                    'birthday'  => '',
                ],
            ],

            'paymentInformation' => [
                'discountForPromptPaymentRate'       => '',
                'discountForPromptPaymentExpireDate' => '',
                'bankClearingNumber'                 => '',
                'bankName'                           => '',
                'branch'                             => '',
                'country'                            => '',
            ]
        ];
    }

    /**
     * @param array $invoiceItems
     */
    public function setInvoiceItems(array $invoiceItems)
    {
        $this->invoiceItems = $invoiceItems;
    }

    /**
     * @return array
     */
    public function getInvoiceItems(): array
    {
        return !empty($this->invoiceItems) ? $this->invoiceItems : [
            [
                'positionReference'          => null,
                'productQuantityInformation' => [
                    'description'      => '',
                    'quantityUnit'     => '',
                    'invoicedQuantity' => '',
                ],
                'priceInformation'           => [
                    'invoicedPricePerUnitExclVat' => '',
                    'vatRate'                     => '',
                    'taxBaseAmount'               => '',
                    'taxAmount'                   => '',
                    'totalAmount'                 => '',
                ],
                'additionalInformation'      => null,

            ]
        ];
    }

    /**
     * @param array $invoiceFooter
     */
    public function setInvoiceFooter(array $invoiceFooter)
    {
        $this->invoiceFooter = $invoiceFooter;
    }

    /**
     * @return array
     */
    public function getInvoiceFooter(): array
    {
        return !empty($this->invoiceFooter) ? $this->invoiceFooter : [
            [
                [
                    'vatInformation' => [
                        'vatRate'       => '',
                        'taxBaseAmount' => '',
                        'taxAmount'     => '',
                    ]
                ]
            ],
            'invoiceTotals' => [
                'orderTotalWithoutTax'  => '',
                'orderTotalWithTax'     => '',
                'instalmentTotalAmount' => '',
            ],
        ];
    }
}