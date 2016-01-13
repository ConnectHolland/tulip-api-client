<?php

namespace ConnectHolland\TulipAPI\Test;

use ConnectHolland\TulipAPI\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\MultipartStream;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * ClientTest.
 *
 * @author Niels Nijens <niels@connectholland.nl>
 */
class ClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * The Tulip URL used for testing.
     *
     * @var string
     */
    const TULIP_URL = 'https://api.example.com';

    /**
     * The Tulip API version used for testing.
     *
     * @var string
     */
    const TULIP_API_VERSION = '1.1';

    /**
     * The Tulip API client ID used for testing.
     *
     * @var string
     */
    const CLIENT_ID = 'test-3x3e4dhmeaiooo48k4soc8ks4c88k48s@api.example.com';

    /**
     * The Tulip API shared secret used for testing.
     *
     * @var string
     */
    const SHARED_SECRET = '0wcog8c848sccsogkc00gkwc88888k0osss88ksw8w0wokgo4cw0s80s48w0o404';

    /**
     * Tests if constructing a new Client instance sets the instance properties.
     */
    public function testConstruct()
    {
        $client = new Client(self::TULIP_URL, self::TULIP_API_VERSION, self::CLIENT_ID, self::SHARED_SECRET);

        $this->assertAttributeSame(self::TULIP_URL, 'tulipUrl', $client);
        $this->assertAttributeSame(self::TULIP_API_VERSION, 'apiVersion', $client);
        $this->assertAttributeSame(self::CLIENT_ID, 'clientId', $client);
        $this->assertAttributeSame(self::SHARED_SECRET, 'sharedSecret', $client);
    }

    /**
     * Tests if Client::getServiceUrl returns the expected Tulip API URL.
     */
    public function testGetServiceUrl()
    {
        $client = new Client(self::TULIP_URL, self::TULIP_API_VERSION);

        $this->assertSame(self::TULIP_URL.'/api/'.self::TULIP_API_VERSION.'/contact/list', $client->getServiceUrl('contact', 'list'));
    }

    /**
     * Tests if Client::setHTTPClient sets a ClientInterface instance on the Client instance.
     */
    public function testSetHTTPClient()
    {
        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);

        $this->assertAttributeSame($httpClientMock, 'httpClient', $client);
    }

    /**
     * Tests if Client::callService creates a correct Request instance and returns a Psr\Http\Message\ResponseInterface instance.
     *
     * @depends testSetHTTPClient
     */
    public function testCallService()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->with(
                    $this->callback(function (RequestInterface $request) {
                        $headers = array('Host' => array('api.example.com'));

                        return $request->getMethod() === 'POST' && strval($request->getUri()) === self::TULIP_URL.'/api/1.1/contact/list' && $request->getHeaders() === $headers && $request->getBody() instanceof MultipartStream;
                    })
                )->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);

        $response = $client->callService('contact', 'list');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * Tests if Client::callService creates a correct Request instance and returns a Psr\Http\Message\ResponseInterface instance.
     *
     * @depends testSetHTTPClient
     */
    public function testCallServiceWithFileUpload()
    {
        $expectedRequestBody = "Content-Disposition: form-data; name=\"photo\"; filename=\"fileupload-test.txt\"\r\n";
        $expectedRequestBody .= "Content-Length: 5\r\n";
        $expectedRequestBody .= "Content-Type: text/plain\r\n\r\n";
        $expectedRequestBody .= "test\n";

        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->with(
                    $this->callback(function (RequestInterface $request) use ($expectedRequestBody) {
                        $body = $request->getBody();
                        if ($body instanceof MultipartStream) {
                            $body->seek(17);

                            return $body->read(130) == $expectedRequestBody;
                        }

                        return false;
                    })
                )->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'save', array(), array('photo' => fopen(__DIR__.'/Resources/fileupload-test.txt', 'r')));
    }

    /**
     * Tests if Client::callService adds the correct request headers when a client ID and shared secret are provided for authentication.
     *
     * @depends testCallService
     */
    public function testCallServiceWithClientIdAndSharedSecretAddsRequestHeaders()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->with(
                    $this->callback(function (RequestInterface $request) {
                        $headers = array(
                            'Host' => array('api.example.com'),
                            'X-Tulip-Client-ID' => array('test-3x3e4dhmeaiooo48k4soc8ks4c88k48s@api.example.com'),
                            'X-Tulip-Client-Authentication' => array('8c1b44413b03a2eda9f94d47ffd3aa2110e1881109aaf20b29f70d7b2a704442'),
                        );

                        return strval($request->getUri()) === self::TULIP_URL.'/api/1.1/contact/list' && $request->getHeaders() === $headers;
                    })
                )->willReturn($responseMock);

        $client = new Client(self::TULIP_URL, '1.1', self::CLIENT_ID, self::SHARED_SECRET);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'list');
    }

    /**
     * Tests if Client::callService adds the correct request headers when a client ID and shared secret are provided for authentication.
     *
     * @depends testCallServiceWithClientIdAndSharedSecretAddsRequestHeaders
     */
    public function testCallServiceWithClientIdAndSharedSecretAndObjectIdentifierAddsRequestHeaders()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->with(
                    $this->callback(function (RequestInterface $request) {
                        $headers = array(
                            'Host' => array('api.example.com'),
                            'X-Tulip-Client-ID' => array('test-3x3e4dhmeaiooo48k4soc8ks4c88k48s@api.example.com'),
                            'X-Tulip-Client-Authentication' => array('118e6e9b08899fb71014962eab4bbaabad04bb228f665c36022ba1bdb6b854c3'),
                        );

                        return strval($request->getUri()) === self::TULIP_URL.'/api/1.1/contact/save' && $request->getHeaders() === $headers;
                    })
                )->willReturn($responseMock);

        $client = new Client(self::TULIP_URL, '1.1', self::CLIENT_ID, self::SHARED_SECRET);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'save', array('id' => 1));
    }

    /**
     * Tests if Client::callService adds the correct request headers when a client ID and shared secret are provided for authentication.
     *
     * @depends testCallServiceWithClientIdAndSharedSecretAndObjectIdentifierAddsRequestHeaders
     */
    public function testCallServiceWithClientIdAndSharedSecretAndApiIdObjectIdentifierAddsRequestHeaders()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1000'><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->with(
                    $this->callback(function (RequestInterface $request) {
                        $headers = array(
                            'Host' => array('api.example.com'),
                            'X-Tulip-Client-ID' => array('test-3x3e4dhmeaiooo48k4soc8ks4c88k48s@api.example.com'),
                            'X-Tulip-Client-Authentication' => array('fa40ddfce9a027380efe40fae9acc57294a768833f91aa05c466d198c02153ce'),
                        );

                        return strval($request->getUri()) === self::TULIP_URL.'/api/1.0/contact/save' && $request->getHeaders() === $headers;
                    })
                )->willReturn($responseMock);

        $client = new Client(self::TULIP_URL, '1.0', self::CLIENT_ID, self::SHARED_SECRET);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'save', array('api_id' => 1));
    }

    /**
     * Tests if Client::callService throws an NotAuthorizedException based on the response code.
     *
     * @expectedException        ConnectHolland\TulipAPI\Exception\NotAuthorizedException
     * @expectedExceptionMessage Not authorized to access the Tulip API: Host '127.0.0.1' is not allowed to access the API.
     */
    public function testCallServiceThrowsNotAuthorizedExceptionBasedOnResponseCode()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1001'><error>Not authorized to access the Tulip API: Host '127.0.0.1' is not allowed to access the API.</error><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'list');
    }

    /**
     * Tests if Client::callService throws an UnknownServiceException based on the response code.
     *
     * @expectedException        ConnectHolland\TulipAPI\Exception\UnknownServiceException
     * @expectedExceptionMessage Unknown service: /api/1.1/contact/details
     */
    public function testCallServiceThrowsUnknownServiceExceptionBasedOnResponseCode()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1003'><error>Unknown service: /api/1.1/contact/details</error><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'details');
    }

    /**
     * Tests if Client::callService throws an ParametersRequiredException based on the response code.
     *
     * @expectedException        ConnectHolland\TulipAPI\Exception\ParametersRequiredException
     * @expectedExceptionMessage The following required parameters were not supplied or incorrect: id
     */
    public function testCallServiceThrowsParametersRequiredExceptionBasedOnResponseCode()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1004'><error>The following required parameters were not supplied or incorrect: id</error><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'detail');
    }

    /**
     * Tests if Client::callService throws an NonExistingObjectException based on the response code.
     *
     * @expectedException        ConnectHolland\TulipAPI\Exception\NonExistingObjectException
     * @expectedExceptionMessage The supplied id does not exist in the database.
     */
    public function testCallServiceThrowsNonExistingObjectExceptionBasedOnResponseCode()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='1005'><error>The supplied id does not exist in the database.</error><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'list');
    }

    /**
     * Tests if Client::callService throws an UnknownErrorException based on the response code.
     *
     * @expectedException ConnectHolland\TulipAPI\Exception\UnknownErrorException
     */
    public function testCallServiceThrowsUnknownErrorExceptionBasedOnResponseCode()
    {
        $responseMock = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $responseMock->expects($this->once())
                ->method('getBody')
                ->willReturn("<?xml version='1.0' encoding='UTF-8'?><response code='0'><result offset='0' limit='0' total='0'/></response>");

        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->expects($this->once())
                ->method('send')
                ->willReturn($responseMock);

        $client = new Client(self::TULIP_URL);
        $client->setHTTPClient($httpClientMock);
        $client->callService('contact', 'list');
    }
}
