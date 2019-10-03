<?php

namespace BoldApps\ShopifyToolkit\Services\GraphQL;

use BoldApps\ShopifyToolkit\Contracts\ShopBaseInfo;
use BoldApps\ShopifyToolkit\Contracts\ShopAccessInfo;
use BoldApps\ShopifyToolkit\Contracts\RequestHookInterface;
use BoldApps\ShopifyToolkit\Exceptions\NotAcceptableException;
use BoldApps\ShopifyToolkit\Exceptions\NotFoundException;
use BoldApps\ShopifyToolkit\Exceptions\TooManyRequestsException;
use BoldApps\ShopifyToolkit\Exceptions\UnauthorizedException;
use BoldApps\ShopifyToolkit\Exceptions\UnprocessableEntityException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

class GraphQLClient
{
    /** @var ShopBaseInfo */
    protected $shopBaseInfo;

    /** @var ShopAccessInfo */
    protected $shopAccessInfo;

    /** @var Client|GuzzleClient */
    protected $client;

    /** @var ApiSleeper */
    protected $apiSleeper;

    /** @var ApiRateLimiter */
    protected $rateLimiter;

    /** @var RateLimitKeyGenerator */
    protected $rateLimitKeyGenerator;

    /**
     * Client constructor.
     *
     * @param ShopBaseInfo         $shopBaseInfo
     * @param ShopAccessInfo       $shopAccessInfo
     * @param GuzzleClient         $client
     * @param RequestHookInterface $requestHookInterface
     */
    public function __construct(ShopBaseInfo $shopBaseInfo, ShopAccessInfo $shopAccessInfo, GuzzleClient $client, RequestHookInterface $requestHookInterface)
    {
        $this->shopBaseInfo = $shopBaseInfo;
        $this->shopAccessInfo = $shopAccessInfo;
        $this->client = $client;
        $this->requestHookInterface = $requestHookInterface;
    }

    /**
     * @param array  $params
     * @param array  $cookies
     * @param string $password
     * @param bool   $frontendApi
     *
     * If password is set it will auth to /password before it does anything. Useful for frontend calls.
     * Cookies is an array of SetCookie objects. See the Cart service for an example.
     *
     * @return array
     */
    public function post($params, $body, array $cookies = [], $password = null, $frontendApi = false, $extraHeaders = [])
    {
        $headers = ['X-Shopify-Access-Token' => $this->shopAccessInfo->getToken(), 'Content-Type' => 'application/json', 'charset' => 'utf-8'];
        $headers = array_merge($headers, $extraHeaders);

        $domain = $frontendApi ? $this->shopBaseInfo->getDomain() : $this->shopBaseInfo->getMyShopifyDomain();

        // ToDo:: Date might need to be passed as well

        $uri = new Uri(sprintf('https://%s/admin/api/graphql.json', $domain));
        $uri = $uri->withQuery(http_build_query($params)); // ToDo:: We might not need this, could maybe remove

        $json = \GuzzleHttp\json_encode([
            'query' => $body
        ]);

        $request = new Request('POST', $uri, $headers, $json);

        return $this->sendRequestToShopify($request, $cookies, $password);
    }

    /**
     * @param Request       $request
     * @param array         $cookies
     * @param string | null $password
     *
     * $cookies is an array of SetCookie objects. see the Cart service for examples.
     * If password is set it will attempt to authenticate with the frontend /password route first.
     *
     * @return array|null
     *
     * @throws UnauthorizedException
     * @throws NotFoundException
     * @throws NotAcceptableException
     * @throws UnprocessableEntityException
     * @throws TooManyRequestsException
     */
    private function sendRequestToShopify(Request $request, array $cookies = [], $password = null)
    {
        $result = null;

        $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
        $options = [
            'cookies' => $cookieJar,
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/32.0.1700.107 Chrome/32.0.1700.107 Safari/537.36',
        ];

        try {
            $domain = $request->getUri()->getHost();

            if ($password) {
                $uri = new Uri(sprintf('https://%s/password', $domain));
                $uri = $uri->withQuery(http_build_query(['password' => $password]));
                $authRequest = new Request('GET', $uri);
                $this->client->send($authRequest, $options);
            }

            foreach ($cookies as $cookie) {
                //set the cookies that were passed in for the next request
                $cookie->setDomain($domain);
                $cookieJar->setCookie($cookie);
            }

            $this->requestHookInterface->beforeRequest($request);

            $response = $this->client->send($request, $options);

            $result = \GuzzleHttp\json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            if (!$response) {
                throw $e;
            }

            switch ($response->getStatusCode()) {
                case 401:
                    throw new UnauthorizedException($e->getMessage());
                case 404:
                    throw new NotFoundException($e->getMessage());
                case 406:
                    throw new NotAcceptableException($e->getMessage());
                case 422:
                    throw new UnprocessableEntityException($e->getMessage());
                case 429:
                    throw new TooManyRequestsException($e->getMessage());
                default:
                    throw $e;
            }
        } catch (\Exception $e) {
            $response = null;
        } finally {
            // ToDo:: We need a separate throttling class for the GraphQL client based on points
            $this->requestHookInterface->afterRequest($response);
        }

        return $result;
    }
}
