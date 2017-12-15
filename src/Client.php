<?php

namespace ConnectHolland\TulipAPI;

use ConnectHolland\TulipAPI\Exception\NonExistingObjectException;
use ConnectHolland\TulipAPI\Exception\NotAuthorizedException;
use ConnectHolland\TulipAPI\Exception\RequestException;
use ConnectHolland\TulipAPI\Exception\ParametersRequiredException;
use ConnectHolland\TulipAPI\Exception\UnknownErrorException;
use ConnectHolland\TulipAPI\Exception\UnknownServiceException;
use GuzzleHttp\Client as HTTPClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Client.
 *
 * @author Niels Nijens <niels@connectholland.nl>
 */
class Client
{
    /**
     * The URL to the Tulip to communicate with.
     *
     * @var string
     */
    private $tulipUrl;

    /**
     * The version of the Tulip API.
     *
     * @var string
     */
    private $apiVersion;

    /**
     * The client identifier for authentication.
     *
     * @var string|null
     */
    private $clientId;

    /**
     * The shared secret to generate a hash for authentication.
     *
     * @var string|null
     */
    private $sharedSecret;

    /**
     * The Guzzle HTTP client instance.
     *
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * Constructs a new Client instance.
     *
     * @param string      $tulipUrl
     * @param string      $apiVersion
     * @param string|null $clientId
     * @param string|null $sharedSecret
     */
    public function __construct($tulipUrl, $apiVersion = '1.1', $clientId = null, $sharedSecret = null)
    {
        $this->tulipUrl = $tulipUrl;
        $this->apiVersion = $apiVersion;
        $this->clientId = $clientId;
        $this->sharedSecret = $sharedSecret;
    }

    /**
     * Returns the full Tulip API URL for the specified service and action.
     *
     * @param string $serviceName
     * @param string $action
     *
     * @return string
     */
    public function getServiceUrl($serviceName, $action)
    {
        return sprintf('%s/api/%s/%s/%s', $this->tulipUrl, $this->apiVersion, $serviceName, $action);
    }

    /**
     * Set the HTTP client instance.
     *
     * @param ClientInterface $httpClient
     */
    public function setHTTPClient(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Call a Tulip API service.
     *
     * @param string $serviceName
     * @param string $action
     * @param array  $parameters
     * @param array  $files
     *
     * @return ResponseInterface
     *
     * @throws RequestException
     */
    public function callService($serviceName, $action, array $parameters = array(), array $files = array())
    {
        $httpClient = $this->getHTTPClient();

        $url = $this->getServiceUrl($serviceName, $action);
        $request = new Request('POST', $url, $this->getRequestHeaders($url, $parameters), $this->getRequestBody($parameters, $files));

        try {
            $response = $httpClient->send($request);
        } catch (GuzzleRequestException $exception) {
            $response = $exception->getResponse();
        }

        $this->validateAPIResponseCode($request, $response);

        return $response;
    }

    /**
     * Returns a ClientInterface instance.
     *
     * @return ClientInterface
     */
    private function getHTTPClient()
    {
        if ($this->httpClient instanceof ClientInterface === false) {
            $this->httpClient = new HTTPClient();
        }

        return $this->httpClient;
    }

    /**
     * Returns the request authentication headers when a client ID and shared secret are provided.
     *
     * @param string $url
     * @param array  $parameters
     *
     * @return array
     */
    private function getRequestHeaders($url, array $parameters)
    {
        $headers = array();

        if (isset($this->clientId) && isset($this->sharedSecret)) {
            $objectIdentifier = null;
            if ($this->apiVersion === '1.1' && isset($parameters['id'])) {
                $objectIdentifier = $parameters['id'];
            } elseif ($this->apiVersion === '1.0' && isset($parameters['api_id'])) {
                $objectIdentifier = $parameters['api_id'];
            }

            $headers['X-Tulip-Client-ID'] = $this->clientId;
            $headers['X-Tulip-Client-Authentication'] = hash_hmac('sha256', $this->clientId.$url.$objectIdentifier, $this->sharedSecret);
        }

        return $headers;
    }

    /**
     * Returns the multipart request body with the parameters and files.
     *
     * @param array $parameters
     * @param array $files
     *
     * @return MultipartStream
     */
    private function getRequestBody(array $parameters, array $files)
    {
        $body = array();
        foreach ($parameters as $parameterName => $parameterValue) {
            if (is_scalar($parameterValue) || (is_null($parameterValue) && $parameterName !== 'id')) {
                $body[] = array(
                    'name' => $parameterName,
                    'contents' => strval($parameterValue),
                );
            }
        }

        foreach ($files as $parameterName => $fileResource) {
            if (is_resource($fileResource)) {
                $metaData = stream_get_meta_data($fileResource);

                $body[] = array(
                    'name' => $parameterName,
                    'contents' => $fileResource,
                    'filename' => basename($metaData['uri']),
                );
            }
        }

        return new MultipartStream($body);
    }

    /**
     * Validates the Tulip API response code.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @throws NotAuthorizedException      when not authenticated / authorized correctly.
     * @throws UnknownServiceException     when the called API service is not found within the Tulip API.
     * @throws ParametersRequiredException when the required parameters for the service / action were not provided or incorrect.
     * @throws NonExistingObjectException  when a requested object was not found.
     * @throws UnknownErrorException       when an error occurs within the Tulip API.
     */
    private function validateAPIResponseCode(RequestInterface $request, ResponseInterface $response)
    {
        $responseParser = new ResponseParser($response);
        switch ($responseParser->getResponseCode()) {
            case ResponseCodes::SUCCESS:
                break;
            case ResponseCodes::NOT_AUTHORIZED:
                throw new NotAuthorizedException($responseParser->getErrorMessage(), $request, $response);
            case ResponseCodes::UNKNOWN_SERVICE:
                throw new UnknownServiceException($responseParser->getErrorMessage(), $request, $response);
            case ResponseCodes::PARAMETERS_REQUIRED:
                throw new ParametersRequiredException($responseParser->getErrorMessage(), $request, $response);
            case ResponseCodes::NON_EXISTING_OBJECT:
                throw new NonExistingObjectException($responseParser->getErrorMessage(), $request, $response);
            case ResponseCodes::UNKNOWN_ERROR:
            default:
                throw new UnknownErrorException($responseParser->getErrorMessage(), $request, $response);
        }
    }
}
