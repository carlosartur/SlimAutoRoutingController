# SlimAutoRoutingController

This library adds a abstract controller to auto generate routes for a slim framework project.

## How to use
### Install

```
composer require carlosartur/slim-auto-routing-controller
```

### Supported HTTP Methods

- GET
- POST
- PUT
- DELETE
- OPTIONS
- PATCH

### Usage example
#### How it works
- To generate *path*
    - If the controller has "ROUTE_FIXED" constant defined:
        - Use value of "ROUTE_FIXED" constant as path.
    - Else:
        - Get the controller's name.
        - Remove "Controller" word of it.
        - Slugify the remaining string.
        - Prefix it with value of "ROUTE_PREFIX" constant (optional).
- To generate a route:
    - Searches on controller the **public** methods which have the word "Action" on it's name, in any part of name.
        - To change the "Action" word to anything else, add a "METHOD_ROUTE_SUFFIX" constant on controller, with another word.
    - To generate a "GET" action, the method must have the "GET" word inside it's name.
    - To generate a "POST" action, the method must have the "POST" word inside it's name.
    - To generate a "PUT" action, the method must have the "PUT" word inside it's name.
    - To generate a "DELETE" action, the method must have the "DELETE" word inside it's name.
    - To generate a "OPTIONS" action, the method must have the "OPTIONS" word inside it's name.
    - To generate a "PATCH" action, the method must have the "PATCH" word inside it's name.
    - If method's name is more than \<verb\>\<suffix\>:
        - Remove http verb and suffix from name;
        - Slugify the remaining string
        - Add result of slug to ***path***
- Parameters and Validation parameter's regex:
    - To add parameter, just add it on method, after "Request" and "Response" parameters.
    - To validate it, 2 ways are possible
        - By type:
            - Add a "TYPES_REGEX" constant on your controller, or use the library default, wich validates float and int only.
        - By name:
            - Add a "PARAMETER_VALIDATION_REGEX" constant on your controller, or use the library default, wich validates a parameter named "id" as a integer value.
#### Controller

```php
<?php

use AutoRotingController\AutoRotingController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ExampleController extends AutoRotingController
{
    /** Method to generate a "GET" /example route */
    public function getAction(Request $request, Response $response)
    {}

    /** Method to generate a "POST" /example route */
    public function postAction(Request $request, Response $response)
    {}

    /** Method to generate a "PUT" /example route */
    public function putAction(Request $request, Response $response)
    {}

    /** Method to generate a "DELETE" /example route */
    public function deleteAction(Request $request, Response $response)
    {}

    /** Method to generate a "OPTIONS" /example route */
    public function optionsAction(Request $request, Response $response)
    {}

    /** Method to generate a "PATCH" /example route */
    public function patchAction(Request $request, Response $response)
    {}

    /** Method to generate a "GET" /example/foo-bar route */
    public function getFooBarAction(Request $request, Response $response)
    {}

    /** Method to generate a "POST" /example/foo-bar route */
    public function postFooBarAction(Request $request, Response $response)
    {}

    /** Method to generate a "PUT" /example/foo-bar route */
    public function putFooBarAction(Request $request, Response $response)
    {}

    /** Method to generate a "DELETE" /example/foo-bar route */
    public function deleteFooBarAction(Request $request, Response $response)
    {}

    /** Method to generate a "OPTIONS" /example/foo-bar route */
    public function optionsFooBarAction(Request $request, Response $response)
    {}

    /** Method to generate a "PATCH" /example/foo-bar route */
    public function patchFooBarAction(Request $request, Response $response)
    {}

}

```

#### On your routing file

##### Instead of doing this

```php
<?php
$app->get('/example', ExampleController::class . ':getAction');
$app->post('/example', ExampleController::class . ':postAction');
$app->put('/example', ExampleController::class . ':putAction');
$app->delete('/example', ExampleController::class . ':deleteAction');
$app->options('/example', ExampleController::class . ':optionsAction');
$app->patch('/example', ExampleController::class . ':patchAction');
```

##### It's necessary only run this
```php
ExampleController::generateRoutes($app);
```

## Configuration
To configure each controller individually, some constants can be used:

```php
/** Path prefix, to add a prefix on route */
protected const ROUTE_PREFIX = "";

/** To use a fixed path for all routes of a controller */
protected const ROUTE_FIXED = "";

/** The route methods has to have this value. All other methods of the class will be ignored */
protected const METHOD_ROUTE_SUFFIX = 'Action';

/** The prefix of the method used to call route function. You can implement your own callers. */
protected const CALL_FUNCTION_PREFIX = "callRouteMethod";

/** The argument regexes. The key is it's type. */
public const TYPES_REGEX = [
    "int" => '[0-9]+',
    "float" => '[+-]?([0-9]*[.])?[0-9]+',
];

/** The argument regexes. The key is it's name. */
public const PARAMETER_VALIDATION_REGEX = [
    "id" => '[0-9]+',
];
```