<?php

namespace App\Tests;

use App\FizzBuzz;
use PHPUnit\Framework\TestCase;

final class FizzBuzzTest extends TestCase
{
    public function testGeneratesUpTo15(): void
    {
        self::assertSame(
            ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz'],
            (new FizzBuzz())->generate(15),
        );
    }

    public function testZeroGivesEmptyList(): void
    {
        self::assertSame([], (new FizzBuzz())->generate(0));
    }
}
