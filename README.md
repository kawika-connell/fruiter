# Fruiter
A simple routing library. Highly inspired by functional programming. Skip to [installation](#fruiter-installation).

This project was created as a challenge for me. I've messed around with Laravel before and used their router, but I never really felt confident I understood what it was doing. I doubt writing this library will give me a complete understanding as to what Laravel is doing (considering I didn't bother to look at its source code before writing it), but I enjoyed writing a routing library from scratch, trying to solve all the problems I encountered on my own. I used this project to explore functional programming too. At first everything was higher-order functions (a function that either takes a function as an argument or returns a function), but I switched to classes in the end. I still followed some functional principles for the project. That aside, installation and usage instruction is below.

<h2 id="fruiter-installation">Installation</h2>

You can install the package via composer or by downloading it and including `fruiter.php` in your program.

### Installation via Composer
Run the following command in your project root:
```
composer require kawikaconnell/fruiter
```

### Manual Installation
1. Click the green _clone or download_ in the top right corner.
2. Pick an option (cloning via https or ssh, opening in desktop, or downloading the zip file)
3. Make sure the code is somewhere in the project root
4. Require it
        ```
        require_once '/where/ever/you/put/it/fruiter.php'; 
        ```

## Usage
Here is a basic usage example:
```php
<?php

declare(strict_types=1);

require_once '/where/ever/you/put/it/fruiter.php'; 

use KawikaConnell\Fruiter\Route;
use function KawikaConnell\Fruiter\uri_router;
use function KawikaConnell\Fruiter\get_path_from_url;

$index = new Route('/', function() {
  return 'Hello World!';
});
$greeter = new Route('/hello/{name}', function(array $arguments) {
  return "Hello {$arguments['name']}";
});
$routes = [$index, $greeter];

$path   = get_path_from_url($url);
$result = uri_router($routes, $path);
if ($result === null) {
    http_response_code(404);
    echo "404 ERROR";
    die();
}

$action    = $result->getRoute()->getAction();
$arguments = $result->getArguments();

echo $action($arguments);
```

### RoutingResult
`KawikaConnell\Fruiter\RoutingResult` is a class made to hold the
- route matching the query,
- and the arguments extracted from the query.
You can access these via the `getRoute()` and `getArguments()` methods:
```php
$routingResult = new KawikaConnell\Fruiter\RoutingResult(new Route(...), []);
$routingResult->getRoute();     // Route
$routingResult->getArguments(); // array
```

This is returned by all the routers in Fruiter. If you are writing code that mutates the data in `RoutingResult`, you will have to make a new instance, because it was designed to be immutable. Lets say you want to take a `RoutingResult` and append '-ish' to every argument:
```php
$routingResult = new KawikaConnell\Fruiter\RoutingResult(
  new Route('/{person}/{company}', ...),
  ['person' => 'John', 'company' => 'John Co']
);

function ishizeArguments($routingResult) {
  $arguments = $routingResult->getArguments();
  
  foreach ($arguments as $argumentName => $argument) {
    $arguments[$argumentName] = $argument.'-ish';
  }

  return new KawikaConnell\Fruiter\RoutingResult($routingResult->getRoute(), $arguments);
}

$routingResult = ishizeArguments($routingResult);
$routingResult->getArguments(); // ['person' => 'John-ish', 'company' => 'John Co-ish']
```

### Route
`KawikaConnell\Fruiter\Route` is a class made to hold a pattern, and an action. You can access these via the `getPattern()` and `getAction` methods.
```php
$route = new KawikaConnell\Fruiter\Route('/', function(...) {...});

$route->getPattern(); // "/" (string) 
$route->getAction();  // Closure
```

This class does not enforce your query matching the pattern, you want to make sure you are checking whether the query matches the pattern before you invoke the action. All the routers in Fruiter do this for you, as they return the route matching the query.

### Matcher
`KawikaConnell\Fruiter\Matcher` is the core component of Fruiter. It's what determines whether a query matches a pattern, and extracts the arguments from the query, as defined in the pattern. The matcher splits the pattern and query into chunks, and compares the the chunk in the query to the corresponding chunk in the pattern to determine whether they match.

#### Examples
The all the examples below, it is assumed that the delimiter for all the matches is '/'.

##### Patterns with no parameters

Patterns with no parameters require an exact match.

The example below yields a match:
```
pattern = '/about'
query   = '/about'
-----------------------
matches = true
argumentsExtracted = []
```

The example below doesn't yield a match:
```
pattern = '/about'
query   = '/products'
-----------------------
matches = false
argumentsExtracted = []
```

##### Required parameters

Parameters are required parameters by default. They are defined like `{this}` with 'this' being the parameter name. If an argument were extracted from a query, its key would be the parameters name, as defined in the pattern:
```
pattern = '/page/{page}'
query   = '/page/about-us'
-----------------------
matches = true
argumentsExtracted = [
  'page' = 'about-us'
]
```

Patterns with parameters are less strict than patterns without them, as the only requirement is that a value is provided.

##### Optional parameters

Optional parameters are optional, so if the query is missing a chunk, and that chunk is optional, the query still matches the pattern:
```
pattern = '/project/{projectId}/{?taskId}'
query   = '/project/123'
-----------------------
matches = true
argumentsExtracted = [
  projectId = 123
  taskId    = null
]
```

You can have multiple optional parameters in succession:
```
pattern = '/project/{?projectId}/{?taskId}'
query   = '/project'
-----------------------
matches = true
argumentsExtracted = [
  projectId = null
  taskId    = null
]
```

If values aren't provided for optional parameters, the matcher will assign `null` as the argument value. So optional parameters are always populated. Having required parameters after optional ones require that the optional parameter be assigned a value, since it evaluates the query from left to right, checking each parameter in that order. This makes optional parameters required if any required parameters are present after the optional one:
```
pattern = '/checkedFirst/{?checkedSecond}/{checkedThird}'
query   = '/checkedFirst/this'
-----------------------
matches = false // because checkedThird doesn't have a corresponding chunk in the query.
argumentsExtracted = []
```

## Ideas

- Create an HTTP router that also checks for matching methods, and handles HTTP errors.
- Create a command line application router that accounts for flags for short flags, long flags and flag arguments.
