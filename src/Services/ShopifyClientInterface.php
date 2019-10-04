<?php

namespace BoldApps\ShopifyToolkit\Services;


interface ShopifyClientInterface
{
    public function get($path, $params = [], array $cookies = [], $password = null, $frontendApi = false);
    public function post($path, $params, $body, array $cookies = [], $password = null, $frontendApi = false, $extraHeaders = []);

    public function getThreshold($response);
}