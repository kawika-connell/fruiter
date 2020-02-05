<?php

declare(strict_types=1);

/**
 * ``` @setUp for Router, uri_router, cli_router
 * > $routes = [
 * >     new Route('/', function () { return 'Hello World!' })
 * >     new Route('hello/{name}', function($arguments) { return "Hello {$arguments['name']}" })
 * > ];
 * ```
 */
namespace KawikaConnell\Fruiter;

use Closure;

/**
 * Appends all elements in $appends to $appendTo.
 * ```
 * > append([1, 2], 3, 4, 5, 6, 7);
 * [1, 2, 3, 4, 5, 6, 7]
 * ```
 *
 * @param array[mixed] $appends
 */
function append(array $appendTo, ...$appends): array {
    foreach ($appends as $append) {
        $appendTo[] = $append;
    }

    return $appendTo;
}

/**
 * Let's you chain single-parameter functions together.
 * ```
 * > compose('strtoupper', 'str_split')('hello'); // == str_split(strtoupper('hello'))
 * ['H', 'E', 'L', 'L', 'O']
 * ```
 *
 * @param array[callable] $callables
 */
function compose(...$functions): Closure {
    return function($value) use ($functions) {
        foreach ($functions as $function) {
            $value = $function($value);
        }

        return $value;
    };
}

/**
 * Returns a new function that fixes $fixedArguments to the left-most
 * parameters on $function, starting with the first parameter.
 * ```
 * > partial_left('explode', ' ')('Hello World') // == explode(' ', 'Hello World');
 * ['Hello', 'World']
 * ```
 *
 * ```
 * > partial_left('str_replace', ' ' '|')('Hello John! Hello Jane!') // str_replace(' ', '_', 'Hello John! Hello Jane!');
 * 'Hello_John!_Hello_Jane!'
 * ```
 *
 * @param mixed $value
 */
function partial_left(callable $function, ...$fixedArguments): Closure {
    return function(...$passedArguments) use (&$function, $fixedArguments) {
        $arguments = append($fixedArguments, ...$passedArguments);
        return $function(...$arguments);
    };
}

/**
 * Returns a new function that fixes $fixedArguments to the right-most
 * parameters on $function, starting with the last parameter.
 * ```
 * > partial_right('explode', 'Hello World')(' '); // == explode(' ', 'Hello World')
 * ['Hello', 'World']
 * ```
 *
 * ```
 * > partial_left('str_replace', 'Hello John! Hello Jane!', '_')(' '); // str_replace(' ', '_', 'Hello John! Hello Jane!')
 * 'Hello_John!_Hello_Jane!'
 * ```
 *
 * @param mixed $value
 */
function partial_right(callable $function, ...$fixedArguments): Closure {
    return function(...$passedArguments) use (&$function, $fixedArguments) {
        $arguments = append($passedArguments, ...array_reverse($fixedArguments));
        return $function(...$arguments);
    };
}

/**
 * Matches string patterns.
 * ```
 * > (new Matcher(' '))('must match this', 'must match this');
 * MatchResult{$matched = true, $arguments = []}
 * > (new Matcher(' '))('must match this', 'must match me!');
 * MatchResult{$matched = false, $arguments = []}
 * ```
 *
 * Patterns can have parameters.
 * ```
 * > (new Matcher(' '))('must match {this}', 'must match me');
 * MatchResult{$matched = true, $arguments = ['this' => 'me']}
 * > (new Matcher(' '))('must match {this}', 'must match');
 * MatchResult{$matched = false, $arguments = []}
 * ```
 *
 * Parameters can have optional.
 * ```
 * > (new Matcher(' '))('must match {?this}', 'must match');
 * MatchResult{$matched = true, $arguments = ['this' => null]}
 * ```
 */
class Matcher
{
    const OPTIONAL_PARAMETER_OPENING_STRING = '{?';
    const PARAMETER_OPENING_STRING          = '{';
    const PARAMETER_CLOSING_STRING          = '}';

    /**
     * @var string
     */
    protected $delimiter;

    public function __construct(string $delimiter)
    { $this->delimiter = $delimiter; }

    public function __invoke(string $pattern, string $query): MatchResult
    {
        $arguments = [];
        $pattern   = self::_explodeAndFilter($pattern);
        $query     = self::_explodeAndFilter($query);

        if (count($query) > count($pattern)) {
            return new MatchResult(false);
        }

        foreach ($pattern as $key => $part) {
            $queryPart = $query[$key] ?? null;

            if (self::_isOptionalParameter($part)) {
                $key = self::_getParameterName($part, self::OPTIONAL_PARAMETER_OPENING_STRING);
                $arguments[$key] = $queryPart;
                continue;
            }

            if (self::_isParameter($part) and $queryPart !== null) {
                $key = self::_getParameterName($part);
                $arguments[$key] = $queryPart;
                continue;
            }

            if ($part !== $queryPart) {
                return new MatchResult(false);
            }
        }

        return new MatchResult(true, $arguments);
    }

    protected function _explodeAndFilter(string $x): array
    {
        return compose(
            partial_left('explode', $this->delimiter),
            'array_filter'
        )($x);
    }

    /**
     * Tells you if the $parameterChunk is an optional argument.
     */
    protected static function _isOptionalParameter(string $parameterChunk): bool {
        return ($parameterChunk[0] ?? '').($parameterChunk[1] ?? '') === self::OPTIONAL_PARAMETER_OPENING_STRING;
    }

    /**
     * Tells you if the $parameterChunk is an argument.
     */
    protected static function _isParameter(string $parameterChunk): bool {
        return $parameterChunk[0] === self::PARAMETER_OPENING_STRING;
    }

    /**
     * Gets the parameter name.
     */
    protected static function _getParameterName(string $parameterChunk, string $type = self::PARAMETER_OPENING_STRING): string {
        return substr(
            $parameterChunk,
            strlen($type),
            -(strlen(self::PARAMETER_CLOSING_STRING))
        );
    }
}

/**
 * @see KawikaConnell\Fruiter\Matcher
 */
class MatchResult
{
    /**
     * @var bool
     */
    protected $matched;

    /**
     * @var array
     */
    protected $arguments;

    public function __construct(bool $matched, array $arguments = [])
    {
        $this->matched   = $matched;
        $this->arguments = $arguments;
    }

    public function getMatched(): bool
    { return $this->matched; }

    public function getArguments(): array
    { return $this->arguments; }
}

/**
 * Gets route matching query from a group of routes.
 * ```
 * > (new Router(new Matcher(' ')))($routes, '');
 * RoutingResult{$route = Route, $arguments = []}
 * ```
 *
 * The Router extracts arguments from the query.
 * ```
 * > (new Router(new Matcher(' ')))($routes, 'hello John');
 * RoutingResult{$route = Route, $arguments = ['name' => 'John']}
 * ```
 *
 * Returns null when no matching routes were found.
 * ```
 * > (new Router(new Matcher(' ')))($routes, 'no match');
 * null
 * ```
 */
class Router
{
    /**
     * @var KawikaConnell\Fruiter\Matcher
     */
    public $matcher;

    public function __construct(Matcher $matcher)
    { $this->matcher = $matcher; }

    /**
     * Get the route that matches the $query.
     *
     * @param array[KawikaConnell\Fruiter\Route] $routes
     */
    public function __invoke(array $routes, string $query): ?RoutingResult
    {
        foreach ($routes as $route) {
            $matchResult = ($this->matcher)($route->getPattern(), $query);
            if ($matchResult->getMatched()) {
                return new RoutingResult($route, $matchResult->getArguments());
            }
        }

        return null;  
    }
}

/**
 * @see KawikaConnell\Fruiter\Matcher
 */
class RoutingResult
{
    /**
     * @var KawikaConnell\Fruiter\Route
     */
    protected $route;

    /**
     * @var array
     */
    protected $arguments;

    public function __construct(Route $route, array $arguments)
    {
        $this->route     = $route;
        $this->arguments = $arguments;
    }

    public function getRoute(): Route
    { return $this->route; }

    public function getArguments(): array
    { return $this->arguments; }
}

class Route
{
    /**
     * @var string
     */
    protected $pattern;

    /**
     * @var callable
     */
    protected $action;

    public function __construct(string $pattern, callable $action)
    {
        $this->pattern = $pattern;
        $this->action  = $action;
    }

    public function getPattern(): string
    { return $this->pattern; }

    public function getAction(): callable
    { return $this->action; }
}

const URI_DELIMITER = '/';

/**
 * Gets the route that matches the $query.
 * ```
 * > uri_router($routes, '/');
 * RoutingResult{$route = Route, $arguments = []}
 * ```
 *
 * uri_router() extracts parameters from the query.
 * ```
 * > uri_router($routes, 'hello/John');
 * RoutingResult{$route = Route, $arguments = ['name' => 'John']}
 * ```
 *
 * uri_router() passes arguments through htmlspecialchars by default().
 * ```
 * > uri_router($routes, 'hello/<br>John');
 * RoutingResult{$route = Route, $arguments = ['name' => '&lt;br&gt;John']}
 * ```
 *
 * Returns null when no matching routes wher found.
 * ```
 * > uri_router($routes, 'no/match');
 * null
 * ```
 *
 * @param array[KawikaConnell\Fruiter\Route] $routes
 */
function uri_router(array $routes, string $query): ?RoutingResult {
    $result = (new Router(new Matcher(URI_DELIMITER)))($routes, $query);

    if ($result === null) {
        return $result;
    }

    $arguments = array_map(
        partial_right('htmlspecialchars', 'UTF-8', ENT_QUOTES),
        $result->getArguments()
    );

    return new RoutingResult($result->getRoute(), $arguments);
}

const CLI_DELIMITER = ' ';

/**
 * Gets the route that matches the $query.
 * ```
 * > (new Router(new Matcher(' ')))($routes, '');
 * RoutingResult{$route = Route, $arguments = []}
 * ```
 *
 * cli_router() extracts parameters from the query.
 * ```
 * > (new Router(new Matcher(' ')))($routes, 'hello John');
 * RoutingResult{$route = Route, $arguments = ['name' => 'John']}
 * ```
 *
 * Returns null when no matching routes wher found.
 * ```
 * > (new Router(new Matcher(' ')))($routes, 'no match');
 * null
 * ```
 *
 * @param array[KawikaConnell\Fruiter\Route] $routes
 */
function cli_router(array $routes, string $query): ?RoutingResult {
    return (new Router(new Matcher(CLI_DELIMITER)))($routes, $query);
}
