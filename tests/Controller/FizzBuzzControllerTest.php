<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FizzBuzzControllerTest extends WebTestCase
{
    private const VALID_KEY = 'test-api-key'; // cf. .env.test

    /**
     * Corps de la dernière réponse, décodé. Échoue si ce n'est pas un objet/tableau JSON valide.
     *
     * @return array<mixed>
     */
    private static function responseJson(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $data = json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    public function testReturnsFizzBuzzWithValidKey(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/fizzbuzz?limit=5', server: ['HTTP_X_API_KEY' => self::VALID_KEY]);

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['limit' => 5, 'result' => ['1', '2', 'Fizz', '4', 'Buzz']],
            self::responseJson($client),
        );
    }

    public function testDefaultLimitIs100(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/fizzbuzz', server: ['HTTP_X_API_KEY' => self::VALID_KEY]);

        self::assertResponseIsSuccessful();
        $result = self::responseJson($client)['result'];
        self::assertIsArray($result);
        self::assertCount(100, $result);
    }

    /** @return iterable<string, array{array<string, string>}> */
    public static function badKeys(): iterable
    {
        yield 'missing header' => [[]];
        yield 'wrong key' => [['HTTP_X_API_KEY' => 'nope']];
        yield 'empty key' => [['HTTP_X_API_KEY' => '']];
    }

    /** @param array<string, string> $server */
    #[DataProvider('badKeys')]
    public function testRejectsInvalidKey(array $server): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/fizzbuzz?limit=5', server: $server);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('WWW-Authenticate', 'ApiKey header="X-API-KEY"');
        self::assertSame(['error' => 'Invalid or missing API key.'], self::responseJson($client));
    }

    /** @return iterable<string, array{string, string, int, string}> */
    public static function frameworkErrors(): iterable
    {
        yield 'wrong method' => ['POST', '/api/fizzbuzz', 405, 'Method Not Allowed'];
        yield 'unknown route' => ['GET', '/api/nope', 404, 'Not Found'];
        yield 'limit as array' => ['GET', '/api/fizzbuzz?limit[]=1', 400, 'Bad Request'];
    }

    #[DataProvider('frameworkErrors')]
    public function testFrameworkErrorsAreJson(string $method, string $uri, int $status, string $error): void
    {
        $client = self::createClient();
        $client->request($method, $uri, server: ['HTTP_X_API_KEY' => self::VALID_KEY]);

        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame(['error' => $error], self::responseJson($client));
    }

    /** @return iterable<string, array{string}> */
    public static function badLimits(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-3'];
        yield 'too big' => ['10001'];
        yield 'not a number' => ['abc'];
        yield 'float' => ['1.5'];
    }

    #[DataProvider('badLimits')]
    public function testRejectsInvalidLimit(string $limit): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/fizzbuzz', ['limit' => $limit], server: ['HTTP_X_API_KEY' => self::VALID_KEY]);

        self::assertResponseStatusCodeSame(400);
    }
}
