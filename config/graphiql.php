<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Routes configuration
    |--------------------------------------------------------------------------
    |
    | Set the key as URI at which the GraphiQL UI can be viewed,
    | and add any additional configuration for the route.
    |
    | You can add multiple routes pointing to different GraphQL endpoints.
    |
    */

    'routes' => [
        '/graphiql' => [
            'name' => 'graphiql',
            // No middleware needed — this is just the UI page, not the API endpoint.
            // The API auth (X-IAE-KEY) is injected as a header in the fetch calls.

            /*
            |--------------------------------------------------------------------------
            | Default GraphQL endpoint
            |--------------------------------------------------------------------------
            |
            | The default endpoint that the GraphiQL UI is set to.
            |
            */

            'endpoint' => '/graphql',

            /*
            |--------------------------------------------------------------------------
            | Subscription endpoint
            |--------------------------------------------------------------------------
            */

            'subscription-endpoint' => env('GRAPHIQL_SUBSCRIPTION_ENDPOINT', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Control GraphiQL availability
    |--------------------------------------------------------------------------
    |
    | Control if the GraphiQL UI is accessible at all.
    | This allows you to disable it in certain environments,
    | for example you might not want it active in production.
    |
    */

    'enabled' => env('GRAPHIQL_ENABLED', true),
];
