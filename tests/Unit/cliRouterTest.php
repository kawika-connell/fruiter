<?php

declare(strict_types=1);

namespace KawikaConnell\Fruiter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KawikaConnell\Fruiter\Route;
use KawikaConnell\Fruiter\RoutingResult;

use function KawikaConnell\Fruiter\cli_router;

class cliRouterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $index = new Route('', function() {
            return "Hello World!";
        });

        $greeter = new Route('greet {name}', function(array $arguments) {
            return "Hello {$arguments['name']}!";
        });

        $this->routes = [$index, $greeter];
    }

    public function testReturnsRoutingResultWhenQueryMatchesARoute()
    {

        $this->assertInstanceOf(RoutingResult::class, cli_router($this->routes, ' '));
        $this->assertInstanceOf(RoutingResult::class, cli_router($this->routes, 'greet John'));
    }

    public function testReturnsNullWhenNoRouteMatches()
    {
        $this->assertEquals(null, cli_router($this->routes, 'doesnt-match'));
    }
}