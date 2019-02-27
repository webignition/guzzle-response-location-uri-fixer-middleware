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
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var callable
     */
    private $middleware;

    protected function setUp()
    {
        parent::setUp();

        $factory = new Factory();

        $this->mockHandler = new MockHandler();
        $this->middleware = $factory->create();

        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push($this->middleware);
    }

    /**
     * @dataProvider executeNoMutationDataProvider
     */
    public function testExecuteNoMutation(ResponseInterface $response)
    {
        $this->mockHandler->append($response);
        $nonMutatedResponse = $this->getResponse();

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
        $this->mockHandler->append($response);
        $mutatedResponse = $this->getResponse();

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

    private function getResponse(): ResponseInterface
    {
        $closure = $this->handlerStack->resolve();

        /* @var Promise $promise */
        $promise = $closure(
            new Request('GET', 'http://example.com/'),
            []
        );

        return $promise->wait();
    }
}
