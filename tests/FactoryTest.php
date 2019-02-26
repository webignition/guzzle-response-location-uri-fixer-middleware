<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\Guzzle\Middleware\ResponseLocationUriFixer\Tests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\Guzzle\Middleware\ResponseLocationUriFixer\Factory;

class FactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteNoMutation(ResponseInterface $response)
    {
        $middleware = Factory::create();

        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $handlerStack->push($middleware);

        $closure = $handlerStack->resolve();

        /* @var Promise $promise */
        $promise = $closure(
            new Request('GET', 'http://example.com/'),
            []
        );
        $nonMutatedResponse = $promise->wait();

        $this->assertSame($nonMutatedResponse, $response);
    }

    public function executeDataProvider(): array
    {
        return [
            '200 response' => [
                'response' => new Response(200),
            ],
            '301 response lacking location header' => [
                'response' => new Response(301),
            ],
            '301 response with non-matching location header' => [
                'response' => new Response(
                    301,
                    [
                        'Location' => 'http://example.com/redirect',
                    ]
                ),
            ],
        ];
    }
}
