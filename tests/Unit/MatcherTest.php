<?php

declare(strict_types=1);

namespace KawikaConnell\Fruiter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KawikaConnell\Fruiter\Matcher;

class MatcherTest extends TestCase
{
    public function testReturnsTrueOnMatchingQuery()
    {
        $result = (new Matcher(' '))(' ', ' ');
        $this->assertEquals(true, $result->getMatched());
        $this->assertEquals([], $result->getArguments());

        $result = (new Matcher(' '))('pattern', 'pattern');
        $this->assertEquals(true, $result->getMatched());
        $this->assertEquals([], $result->getArguments());
    }

    public function testReturnsArgumentsFromMatchingQuery()
    {
        $result = (new Matcher(' '))('pattern {parameter}', 'pattern value');
        $this->assertEquals(true, $result->getMatched());
        $this->assertEquals(['parameter' => 'value'], $result->getArguments());
    }

    public function testReturnsFalseOnNonMatchingPattern()
    {
        $result = (new Matcher(' '))('this', 'doesnt-match');
        $this->assertEquals(false, $result->getMatched());
        $this->assertEquals([], $result->getArguments());

        $result = (new Matcher(' '))('pattern {requiredParameter}', 'pattern');
        $this->assertEquals(false, $result->getMatched());
        $this->assertEquals([], $result->getArguments());
    }

    public function testReturnsWhenLastLastArgumentMissingWithOptionalParameter()
    {
        $result = (new Matcher(' '))('pattern {?optionalParameter}', 'pattern');
        $this->assertEquals(true, $result->getMatched());
        $this->assertEquals(['optionalParameter' => null], $result->getArguments());
    }

    public function testReturnsProvidedArgumentForOptionalParameter()
    {
        $result = (new Matcher(' '))('pattern {?optionalParameter}', 'pattern value');
        $this->assertEquals(true, $result->getMatched());
        $this->assertEquals(['optionalParameter' => 'value'], $result->getArguments());
    }
}