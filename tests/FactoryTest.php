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
     * @dataProvider executeNoMutationDataProvider
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

    public function executeNoMutationDataProvider(): array
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

    /**
     * @dataProvider executeHasMutationDataProvider
     */
    public function testExecuteHasMutation(ResponseInterface $response, string $expectedLocation)
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

        /* @var ResponseInterface $mutatedResponse */
        $mutatedResponse = $promise->wait();

        $this->assertNotSame($mutatedResponse, $response);
        $this->assertEquals($expectedLocation, $mutatedResponse->getHeaderLine('Location'));
    }

    public function executeHasMutationDataProvider(): array
    {
        return [
            '301 response with invalid http url' => [
                'response' => new Response(
                    301,
                    [
                        'Location' => 'http:///example.com/redirect',
                    ]
                ),
                'expectedLocation' => 'http://example.com/redirect',
            ],
            '301 response with invalid https url' => [
                'response' => new Response(
                    301,
                    [
                        'Location' => 'https:///example.com/redirect',
                    ]
                ),
                'expectedLocation' => 'https://example.com/redirect',
            ],
        ];
    }
}
