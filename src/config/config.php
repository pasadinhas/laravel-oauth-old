<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default storage "driver" that will be used to
    | store the tokens. By default, we will use the 'Session' driver but
    | you may specify any of the other wonderful drivers provided here.
    |
    | Supported: 'Session', 'Memory'
    |
    | In the future there may be support for 'SymfonySession' and 'Redis',
    | but at the moment those are not supported.
    |
    */

    'storage' => 'Session',

    /*
    |--------------------------------------------------------------------------
    | Default HTTP Client Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default HTTP Client "driver" that will be used
    | to send the HTTP requests. By default, we will use the 'StreamClient'
    | driver, for backwards compatibility but you may specify any of the other
    | wonderful drivers provided here.
    |
    | Supported: 'StreamClient', 'CurlClient'
    |
    */

    'client' => 'StreamClient',

    /*
    |--------------------------------------------------------------------------
    | Custom Services
    |--------------------------------------------------------------------------
    |
    | Here you may specify your custom OAuth Services. Just list the fully
    | qualified class name in this array and the service will be in the scope
    | of the library. Don't forget that the services must implement the
    | OAuth\Common\Service\ServiceInterface interface.
    | If you don't use this, keep it commented.
    |
    */

//    'services' => [
//        'Fully\Qualified\Service\ExampleClass',
//    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Decorators
    |--------------------------------------------------------------------------
    |
    | If you want to have a specific namespace in your app where all your
    | decorators are, you can add that namespace here. Then that namespace will
    | be searched for a {service_name}Decorator class to decorate the service.
    | If you don't have a namespace for your decorators, keep this commented.
    |
    */

//    'decorators' => 'Decorators\Namespace\',

    /*
    |--------------------------------------------------------------------------
    | Consumers
    |--------------------------------------------------------------------------
    |
    | In this array you must add your keys and redirect URL for the services
    | you will use. Just check the syntax in the ExampleService bellow. (You
    | don't need to provide the full class name, just the service name, like
    | "Facebook")
    |
    | For each consumer service you have the following options:
    |
    | - [required] client.id     - your client ID,
    | - [required] client.secret - your client secret ID,
    | - [optional] redirect      - a default redirect url,
    | - [optional] scopes        - a default array with the required scopes,
    | - [optional] decorator     - a fully qualified decorator class,
    | - [optional] refresh       - boolean weather the access token should be
    |                              automatically refreshed.
    |
    */

    'consumers' => [

        'ExampleService' => [
            'client.id' => 'your client id',
            'client.secret' => 'your client secret',
            'redirect' => 'http://localhost:8000/login',
            'scopes' => [
                'required', 'scopes', 'go', 'here'
            ],
            'decorator' => 'Custom\Decorator\Class',
            'refresh' => true,
        ],

    ]
];