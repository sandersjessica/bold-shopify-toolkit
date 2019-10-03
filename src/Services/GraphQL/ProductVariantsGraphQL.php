<?php
/**
 * Created by PhpStorm.
 * User: jessicasanders
 * Date: 2019-10-03
 * Time: 1:23 PM
 */

namespace BoldApps\ShopifyToolkit\Services\GraphQL;

use BoldApps\ShopifyToolkit\Services\GraphQL\GraphQLClient as ShopifyGraphQLClient;


class ProductVariantsGraphQL
{
    protected $client;

    /**
     * ProductVariantsGraphQL constructor.
     * @param ShopifyGraphQLClient $client
     */
    public function __construct(ShopifyGraphQLClient $client)
    {
        $this->client = $client;
    }

    public function query($filter = [], $node = [], $first = 10)
    {
        $filterString = !empty($filter) ? 'query: "' . implode(' ', $filter) . '"' : '';
        $nodeString = implode(' ',$node);

        $query = <<<GRAPHQL
            query {
                productVariants( $filterString, first: $first) {
                    edges {
                        cursor,
                 node { $nodeString }
               },
               pageInfo {
                        hasNextPage,
                 hasPreviousPage
               }
             }
         }
GRAPHQL;

        $result = $this->client->post([], $query);
        dd($result);
    }
}