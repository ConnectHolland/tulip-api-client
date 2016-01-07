<?php

namespace ConnectHolland\TulipAPI;

use DOMAttr;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Psr\Http\Message\ResponseInterface;

/**
 * ResponseParser.
 *
 * @author Niels Nijens <niels@connectholland.nl>
 */
class ResponseParser
{
    /**
     * The response instance.
     *
     * @var ResponseInterface
     */
    private $response;

    /**
     * The DOMDocument instance created from the response body.
     *
     * @var DOMDocument
     */
    private $domDocument;

    /**
     * Constructs a new ResponseParser.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Returns the response code from the API.
     *
     * @param ResponseInterface $response
     *
     * @return int
     */
    public function getResponseCode()
    {
        $code = 0;

        $dom = $this->getDOMDocument();
        $xpath = new DOMXPath($dom);
        if (($codeAttribute = $xpath->query('/response/@code')->item(0)) instanceof DOMAttr) {
            $code = intval($codeAttribute->nodeValue);
        }

        return $code;
    }

    /**
     * Returns the error message from the Tulip API.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        $errorMessage = '';

        $dom = $this->getDOMDocument();
        $xpath = new DOMXPath($dom);
        if (($errorNode = $xpath->query('/response/error')->item(0)) instanceof DOMNode) {
            $errorMessage = $errorNode->nodeValue;
        }

        return $errorMessage;
    }

    /**
     * Returns the response body as DOMDocument instance.
     *
     * @return DOMDocument
     */
    public function getDOMDocument()
    {
        if ($this->domDocument instanceof DOMDocument === false) {
            $this->domDocument = new DOMDocument('1.0', 'UTF-8');

            libxml_clear_errors();
            $previousSetting = libxml_use_internal_errors(true);

            @$this->domDocument->loadXML($this->response->getBody());

            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);
        }

        return $this->domDocument;
    }
}
