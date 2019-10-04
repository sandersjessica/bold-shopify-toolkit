<?php

namespace BoldApps\ShopifyToolkit\Support;

use BoldApps\ShopifyToolkit\Contracts\RequestHookInterface;
use BoldApps\ShopifyToolkit\Contracts\ApiSleeper;

class ShopifyApiHandler implements RequestHookInterface, ApiSleeper
{
    /** @var \GuzzleHttp\Psr7\Response|null */
    private $response;

    /**
     * @param \GuzzleHttp\Psr7\Request|null $request
     */
    public function beforeRequest($request)
    {
    }

    /**
     * @param \GuzzleHttp\Psr7\Response|null $response
     */
    public function afterRequest($response)
    {
        $this->sleep($response);
    }

    /**
     * @param \GuzzleHttp\Psr7\Response|null $response
     */
    public function sleep($response)
    {
//        $this->response = $response;
//        $percent = $this->getCallLimitPercent();
//
//        if ($percent > 98) {
//            logger()->debug('98');
//            sleep(15);
//        } elseif ($percent > 96) {
//            logger()->debug('96');
//            sleep(13);
//        } elseif ($percent > 94) {
//            logger()->debug('94');
//            sleep(10);
//        } elseif ($percent > 92) {
//            logger()->debug('92');
//            sleep(8);
//        } elseif ($percent > 90) {
//            logger()->debug('90');
//            sleep(5);
//        } elseif ($percent > 75) {
//            logger()->debug('75');
//            sleep(3);
//        } elseif ($percent > 70) {
//            logger()->debug('70');
//            logger()->debug('SWITCHING CLIENT');
//            logger()->debug(app());
//            logger()->debug('after');
//            //SWITCH CLIENT
////            app()->bind(ShopifyClientInterface::class, GraphQLClient::class);
//        }

        logger()->error('sleepa');
    }


}
