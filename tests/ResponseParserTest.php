<?php

namespace ConnectHolland\TulipAPI\Test;

use ConnectHolland\TulipAPI\ResponseParser;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * ResponseParserTest.
 *
 * @author Niels Nijens <niels@connectholland.nl>
 */
class ResponseParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests if constructing a new ResponseParser instance sets the instance properties.
     */
    public function testConstruct()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();

        $responseParser = new ResponseParser($responseMock);
        $this->assertAttributeSame($responseMock, 'response', $responseParser);
    }

    /**
     * Tests if ResponseParser::getDOMDocument returns the response body as XML DOMDocument.
     */
    public function testGetDOMDocument()
    {
        $xmlBody = "<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>";

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertInstanceOf('DOMDocument', $responseParser->getDOMDocument());
        $this->assertXmlStringEqualsXmlString($xmlBody, $responseParser->getDOMDocument()->saveXML());
    }

    /**
     * Tests if ResponseParser::getDOMDocument returns an empty DOMDocument instance when the response body is invalid XML.
     *
     * @depends testGetDOMDocument
     */
    public function testGetDOMDocumentWithInvalidXML()
    {
        $xmlBody = '';

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertInstanceOf('DOMDocument', $responseParser->getDOMDocument());
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL, $responseParser->getDOMDocument()->saveXML());
    }

    /**
     * Tests if ResponseParser::getResponseCode returns the response code '1000' from the XML response.
     *
     * @depends testGetDOMDocument
     */
    public function testGetResponseCode()
    {
        $xmlBody = "<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>";

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertSame(1000, $responseParser->getResponseCode());
    }

    /**
     * Tests if ResponseParser::getResponseCode returns the response code '0' when the XML response is invalid.
     *
     * @depends testGetDOMDocumentWithInvalidXML
     * @depends testGetResponseCode
     */
    public function testGetResponseCodeWithInvalidXMLReturnsZero()
    {
        $xmlBody = "<?xml version='1.0' encoding='UTF-8'?><response code='1000'/><result offset='0' limit='0' total='0'/>";

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertSame(0, $responseParser->getResponseCode());
    }

    /**
     * Tests if ResponseParser::getErrorMessage returns the error message from the XML response.
     *
     * @depends testGetDOMDocument
     */
    public function testGetErrorMessage()
    {
        $errorMessage = 'Not authorized to access the Tulip API: Application with client ID "test-3x3e4dhmeaiooo48k4soc8ks4c88k48s@api.example.com" is not allowed to access the API.';
        $xmlBody = sprintf("<?xml version='1.0' encoding='UTF-8'?><response code='1001'><error>%s</error><result offset='0' limit='0' total='0'/></response>", $errorMessage);

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertSame($errorMessage, $responseParser->getErrorMessage());
    }

    /**
     * Tests if ResponseParser::getErrorMessage returns an empty string when no error message is found in the XML response.
     *
     * @depends testGetErrorMessage
     */
    public function testGetErrorMessageWithoutErrorReturnsEmptyString()
    {
        $xmlBody = "<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>";

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertSame('', $responseParser->getErrorMessage());
    }

    /**
     * Tests if ResponseParser::getErrorMessage returns an empty string when the XML response is invalid.
     *
     * @depends testGetDOMDocumentWithInvalidXML
     * @depends testGetErrorMessageWithoutErrorReturnsEmptyString
     */
    public function testGetErrorMessageWithInvalidXMLReturnsEmptyString()
    {
        $xmlBody = "<?xml version='1.0' encoding='UTF-8'?><response code='1000'/><result offset='0' limit='0' total='0'/>";

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn($xmlBody);

        $responseParser = new ResponseParser($responseMock);

        $this->assertSame('', $responseParser->getErrorMessage());
    }
}
