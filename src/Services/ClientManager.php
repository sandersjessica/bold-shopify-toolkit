<?php

namespace BoldApps\ShopifyToolkit\Services;

use App\Services\CacheService;
use BoldApps\ShopifyToolkit\Services\GraphQL\GraphQLClient;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;

class ClientManager
{
    protected $graphQLClient;
    protected $restAPIClient;
    protected static $currentClient;

    public function __construct(ShopifyClientInterface $currentClient, GraphQLClient $graphQLClient, Client $restAPIClient)
    {
        $this->graphQLClient = $graphQLClient;
        $this->restAPIClient = $restAPIClient;
        $shouldSwitchBack = false;
        $clientType = CacheService::getShopifyClientCacheValue("jessicas-checkout-store.myshopify.com");
        $clientTime = CacheService::getShopifyClientTimeCacheValue("jessicas-checkout-store.myshopify.com");
        if ($clientTime !== null) {
            $shouldSwitchBack = Carbon::now()->subSeconds(7) > Carbon::parse($clientTime);
        }

        logger()->debug($clientType);
        if ($clientType == "graphQL" && !$shouldSwitchBack) {
            static::$currentClient = $graphQLClient;
        } else {
            CacheService::setShopifyClientTimeValue("jessicas-checkout-store.myshopify.com", Carbon::now()->toDateTimeString());
            CacheService::setShopifyClientValue("jessicas-checkout-store.myshopify.com", "restApi");

            logger()->debug("Switching back to rest client");
            static::$currentClient = $restAPIClient;
        }
    }

    public function getThreshold(Response $response)
    {
        $threshold = 0;
        if (get_class(static::$currentClient) == Client::class) {
            $threshold = static::$currentClient->getThreshold($response);
        }

        if ($threshold > 20) {
            logger()->debug("Switching to GQL");
            static::$currentClient = $this->graphQLClient;
            CacheService::setShopifyClientTimeValue("jessicas-checkout-store.myshopify.com", Carbon::now()->toDateTimeString());
            CacheService::setShopifyClientValue("jessicas-checkout-store.myshopify.com", "graphQL");
        }
    }

    public function get($path, $params = [], array $cookies = [], $password = null, $frontendApi = false)
    {
        $resp = static::$currentClient->get($path, $params = [], $cookies, $password, $frontendApi);
        $this->getThreshold($resp);

        return \GuzzleHttp\json_decode((string) $resp->getBody(), true);
    }

    public function post($path, $params, $body, array $cookies = [], $password = null, $frontendApi = false, $extraHeaders = [])
    {
        $resp = static::$currentClient->post($path, $params = [], $cookies, $password, $frontendApi);
        return \GuzzleHttp\json_decode((string) $resp->getBody(), true);
    }
}