# Fruiter
A simple routing library. Highly inspired by functional programming. Skip to [installation](#fruiter-installation).

This project was created as a challenge for me. I've messed around with Laravel before and used their router, but I never really felt confident I understood what it was doing. I doubt writing this library will give me a complete understanding as to what Laravel is doing (considering I didn't bother to look at its source code before writing it), but I enjoyed writing a routing library from scratch, trying to solve all the problems I encountered on my own. I used this project to explore functional programming too. At first everything was higher-order functions (a function that either takes a function as an argument or returns a function), but I switched to classes in the end. I still followed some functional principles for the project. That aside, installation and usage instruction is below.

<h2 id="fruiter-installation">Installation</h2>
You can install the package via composer or by downloading it and including `fruiter.php` in your program.

### Installation via composer
Run the following command in your project root:
```
composer require kawikaconnell/fruiter
```

### Manual Installation
1. Click the green _clone or download_ in the top right corner.
2. Pick an option (cloning via https or ssh, opeining in desktop, or downloading the zip)
3. Make sure code in somewhere in project root
4. Require it
        ```
        require_once './where/ever/you/put/it/fruiter.php'; 
        ```

## Usage
Here is a basic usage example:
```
require_once './where/ever/you/put/it/fruiter.php';

use KawikaConnell\Fruiter\Route;
use function KawikaConnell\Fruiter\uri_router;

$routes = [
  new Route('/', function() {
    return 'Hello World!';
  }),
  // You'll pass the arguments later.
  new Route('/hello/{name}', funciton(array $arguments) {
    return "Hello {$arguments['name']}";
  }),
];
```
