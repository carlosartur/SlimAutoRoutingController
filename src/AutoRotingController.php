<?php

namespace AutoRotingController;

use BadMethodCallException;
use Slim\App;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

abstract class AutoRotingController
{
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

    protected const HTTP_METHOD_GET = 'GET';

    protected const HTTP_METHOD_POST = 'POST';

    protected const HTTP_METHOD_PUT = 'PUT';

    protected const HTTP_METHOD_DELETE = 'DELETE';

    protected const HTTP_METHOD_OPTIONS = 'OPTIONS';

    protected const HTTP_METHOD_PATCH = 'PATCH';

    /**
     * Generated routes by this class, on all children's classes.
     * Can be useful to list all auto-generated routes.
     *
     * @var array
     */
    public static $generatedRoutes = [];

    /**
     * Generate slim routes, based on name of controller's methods, using this convencion:
     * getAction     - Generate a get route
     * postAction    - Generate a post route
     * putAction     - Generate a put route
     * deleteAction  - Generate a delete route
     * optionsAction - Generate a options route
     * patchAction   - Generate a patch route
     * 
     * @param App $app
     * @return void
     */
    final public static function generateRoutes(App $app)
    {
        $reflection = new ReflectionClass(static::class);
        $prefix = static::getRoutePrefix($reflection);

        $publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($publicMethods as $method) {
            self::buildRoute($method, $app, $prefix);
        }
    }

    /**
     * Create a single route or not, based on current method name.
     *
     * @param ReflectionMethod $method
     * @param App $app
     * @param string $prefix
     * @return bool - returns true if a route is crated, otherwise, false
     */
    private static function buildRoute(ReflectionMethod $method, App $app, string $prefix): bool
    {        
        $methodName = $method->getName();

        if (false === stripos($methodName, static::METHOD_ROUTE_SUFFIX)) {
            return false;
        }

        $path = self::buildRoutePath($method, $prefix);

        $actualClassName = static::class;

        $nameUcfirst = ucfirst($methodName);
        $methodPrefix = self::CALL_FUNCTION_PREFIX;

        $callable = "{$actualClassName}:{$methodPrefix}{$nameUcfirst}";

        $isRouteMethod = false;
        $httpMethod = null;

        switch (true) {
            case false !== stripos($methodName, static::HTTP_METHOD_GET):
                $app->get($path, $callable);
                $isRouteMethod = true;
                $httpMethod = self::HTTP_METHOD_GET;
                break;

            case false !== stripos($methodName, static::HTTP_METHOD_POST):
                $app->post($path, $callable);
                $isRouteMethod = true;
                $httpMethod = self::HTTP_METHOD_POST;
                break;

            case false !== stripos($methodName, static::HTTP_METHOD_PUT):
                $app->put($path, $callable);
                $isRouteMethod = true;
                $httpMethod = self::HTTP_METHOD_PUT;
                break;

            case false !== stripos($methodName, static::HTTP_METHOD_DELETE):
                $app->delete($path, $callable);
                $isRouteMethod = true;
                $httpMethod = self::HTTP_METHOD_DELETE;
                break;

            case false !== stripos($methodName, static::HTTP_METHOD_OPTIONS):
                $app->options($path, $callable);
                $isRouteMethod = true;
                $httpMethod = self::HTTP_METHOD_OPTIONS;
                break;

            case false !== stripos($methodName, static::HTTP_METHOD_PATCH):
                $app->patch($path, $callable);
                $isRouteMethod = true;
                $httpMethod = self::HTTP_METHOD_PATCH;
                break;
        }

        if ($isRouteMethod) {
            static::$generatedRoutes[] = compact('path', 'callable', 'methodName', 'httpMethod');
        }

        return $isRouteMethod;
    }

    /**
     * Returns the path of the route, based on method's parameters and name of the class.
     * A prefix can be defined on ROUTE_PREFIX constant, or all path, excluding parameters,
     * can be defined on ROUTE_FIXED constant.
     * Parameters regexes validators is defined by type.
     * To customise these regexes, overwrite TYPES_REGEX constant.
     *
     * @param ReflectionMethod $method
     * @param string $prefix
     * @return string
     */
    private static function buildRoutePath(ReflectionMethod $method, string $prefix): string
    {
        $routeString = "/{$prefix}";

        $methodNameAddPath = str_ireplace([
            static::METHOD_ROUTE_SUFFIX,
            static::HTTP_METHOD_GET,
            static::HTTP_METHOD_POST,
            static::HTTP_METHOD_PUT,
            static::HTTP_METHOD_DELETE,
            static::HTTP_METHOD_OPTIONS,
            static::HTTP_METHOD_PATCH,
        ], "", $method->getName());

        if ($methodNameAddPath) {
            $routeString .= "-" . self::slugify($methodNameAddPath);
        }

        $parameters = $method->getParameters();
        /** @var ReflectionParameter */
        foreach ($parameters as $parameter) {
            $typeString = (string) $parameter->getType();
            if (in_array($typeString, [Response::class, Request::class])) {
                continue;
            }

            $regex = self::getParameterRegex($parameter);

            $format = $parameter->allowsNull() ? '[/{%s%s}]' : '/{%s%s}';
            $routeString .= sprintf($format, $parameter->getName(), $regex);
        }
        return $routeString;
    }

    private static function getParameterRegex(ReflectionParameter $reflectionParameter): string
    {
        $parameterName = $reflectionParameter->getName();
        if (array_key_exists($parameterName, static::PARAMETER_VALIDATION_REGEX)) {
            return ':' . static::PARAMETER_VALIDATION_REGEX[$parameterName];
        }

        $typeString = (string) $reflectionParameter->getType();

        if (array_key_exists($typeString, static::TYPES_REGEX)) {
            return ':' . static::TYPES_REGEX[$typeString];
        }
        return "";
    }

    /**
     * Get prefix of the route, based on class name or ROUTE_FIXED constant.
     *
     * @param ReflectionClass $reflection
     * @return string
     */
    private static function getRoutePrefix(ReflectionClass $reflection): string
    {
        if (static::ROUTE_FIXED) {
            return static::ROUTE_FIXED;
        }

        $snakeCase = self::slugify($reflection->getShortName());
        return static::ROUTE_PREFIX . str_replace("-controller", "", $snakeCase);
    }
 
    /**
     * Change camelCase to snake_case, with configurable separator
     *
     * @param string $stringToSlugify
     * @param string $separator
     * @return string
     */
    private static function slugify(string $stringToSlugify, string $separator = "-"): string
    {
        $snakeCase = ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $stringToSlugify)), $separator);
        return $snakeCase;
    }

    /**
     * Call method on child's class
     *
     * @param string $method
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function callRouteMethod(string $method, Request $request, Response $response, array $args)
    {
        $arguments = array_merge(compact('request', 'response'), $args);
        return call_user_func_array([$this, $method], $arguments);
    }

    /**
     * Call methods
     *
     * @param [type] $name
     * @param [type] $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (false !== stripos($name, self::CALL_FUNCTION_PREFIX)) {
            $request = $arguments[0];
            $response = $arguments[1];
            $args = $arguments[2];

            $method = lcfirst(str_replace(self::CALL_FUNCTION_PREFIX, "", $name));

            $arguments = array_merge(compact('request', 'response'), $args);

            return call_user_func_array([$this, $method], $arguments);
        
        }

        throw new BadMethodCallException("Bad method call: {$name} method does not exists on class [" . static::class . "]");
    }
}
