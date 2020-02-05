<?php

declare(strict_types=1);

namespace KawikaConnell\Fruiter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KawikaConnell\Fruiter\Route;

use function KawikaConnell\Fruiter\compose;
use function KawikaConnell\Fruiter\partial_left;
use function KawikaConnell\Fruiter\partial_right;

class functionsTest extends TestCase
{
    public function testCompose()
    {
        $x = 'hello';
        $this->assertEquals(
            str_split(strtoupper($x)),
            compose('strtoupper', 'str_split')($x)
        );
    }

    public function testPartialLeft()
    {
        $x = 'a.b.c';
        $separateSenetnceByDot = partial_left('explode', '.');
        $this->assertEquals(
            explode('.', $x),
            $separateSenetnceByDot($x)
        );

        $x = 'Hello John! Hello Jane!';
        $replaceSpacesWithUnderscores = partial_left('str_replace', ' ', '_');
        $this->assertEquals(
            str_replace(' ', '_', $x),
            $replaceSpacesWithUnderscores($x)
        );
    }

    public function testPartialRight()
    {
        $x = 'There are three sentences here. See? There are!';
        $separateThisSentenceBy = partial_right('explode', $x);
        $this->assertEquals(
            explode('.', $x),
            $separateThisSentenceBy('.')
        );

        $this->assertEquals(
            explode(' ', $x),
            $separateThisSentenceBy(' ')
        );

        $redactInSentence = partial_right('str_replace', $x, '**redacted**'); 
        $this->assertEquals(
            str_replace('are', '**redacted**', $x),
            $redactInSentence('are')
        );
    }
}