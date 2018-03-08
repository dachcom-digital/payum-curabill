<?php

namespace DachcomDigital\Payum\Curabill\Library;

use DachcomDigital\Payum\Curabill\Request\Api\Transformer\InvoiceTransformer;

class XmlGenerator
{
    /**
     * @param InvoiceTransformer $invoiceTransformer
     * @return mixed
     */
    public function generateInvoiceXml(InvoiceTransformer $invoiceTransformer)
    {
        $xsd = realpath(__DIR__ . '/../invoice.xsd');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><invoice/>');

        $xml->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('xmlns:xmlns', 'http://middlelayer.curabill.ch/xsd/invoice');
        $xml->addAttribute('xmlns:xsi:schemaLocation', 'http://middlelayer.curabill.ch/xsd/invoice schema.xsd');

        $invoiceHeader = $xml->addChild('invoiceHeader');
        $this->generateNode($invoiceTransformer->getInvoiceHeader(), $invoiceHeader);

        foreach($invoiceTransformer->getInvoiceItems() as $item) {
            $invoiceItem = $xml->addChild('invoiceItem');
            $this->generateNode($item, $invoiceItem);
        }

        $invoiceFooter = $xml->addChild('invoiceFooter');
        $this->generateNode($invoiceTransformer->getInvoiceFooter(), $invoiceFooter);

        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        $xmlData = $xml->asXML();

        $document->loadXml($xmlData);
        $document->schemaValidate($xsd);

        return $xmlData;
    }

    /**
     * @param array             $node
     * @param \SimpleXMLElement $xml
     */
    private function generateNode(array $node, \SimpleXMLElement $xml)
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subNode = $xml->addChild("$key");
                    $this->generateNode($value, $subNode);
                } else {
                    //var_dump($key);
                    $this->generateNode($value, $xml);
                }
            } else {
                $xml->addChild("$key", "$value");
            }
        }
    }
}
