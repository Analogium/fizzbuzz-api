<?php

namespace App;

final class FizzBuzz
{
    /**
     * @return list<string>
     */
    public function generate(int $limit): array
    {
        $result = [];

        for ($i = 1; $i <= $limit; ++$i) {
            $result[] = match (true) {
                0 === $i % 15 => 'FizzBuzz',
                0 === $i % 3 => 'Fizz',
                0 === $i % 5 => 'Buzz',
                default => (string) $i,
            };
        }

        return $result;
    }
}
