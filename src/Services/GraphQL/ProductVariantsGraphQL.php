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
        $filterString = !empty($filter) ? 'query: "product_id:1528167104547"' : '';
        $nodeString = implode(' ',$node);

        $query = <<<GRAPHQL
            query {
                productVariants(query: "product_id:1528167104547", first: $first) {
                    edges {
                        cursor,
                 node { id,
            title,
            price,
            sku,
            position,
            inventoryPolicy,
            compareAtPrice,
            inventoryManagement,
            createdAt,
            updatedAt,
            taxable,
            barcode,
            weight,
            weightUnit,
            inventoryQuantity,
            requiresShipping,
            product { id} }
               },
               pageInfo {
                        hasNextPage,
                 hasPreviousPage
               }
             }
         }
GRAPHQL;

        $result = $this->client->post([], $query);
    }
}