<?php

declare(strict_types=1);

namespace Tourze\PSR15ChainRequestHandler\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tourze\PSR15ChainRequestHandler\ChainRequestHandler;

/**
 * ChainRequestHandler 类的单元测试
 */
class ChainRequestHandlerTest extends TestCase
{
    /**
     * 测试空处理器链返回404响应
     */
    public function testEmptyHandlersReturns404(): void
    {
        $handler = new ChainRequestHandler();
        $request = new ServerRequest('GET', '/');

        $response = $handler->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('No handlers available', (string)$response->getBody());
    }

    /**
     * 测试非404响应会立即返回
     */
    public function testNon404ResponseReturnsImmediately(): void
    {
        // 模拟请求处理器，返回200响应
        /** @var RequestHandlerInterface&MockObject $mockHandler */
        $mockHandler = $this->createMock(RequestHandlerInterface::class);
        $mockHandler->method('handle')
            ->willReturn(new Response(200, body: 'Success'));

        // 模拟另一个请求处理器，此处理器应该不会被调用
        /** @var RequestHandlerInterface&MockObject $mockHandler2 */
        $mockHandler2 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler2->expects($this->never())
            ->method('handle');

        $chainHandler = new ChainRequestHandler([$mockHandler]);
        $chainHandler->addHandler($mockHandler2);

        $request = new ServerRequest('GET', '/');
        $response = $chainHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', (string)$response->getBody());
    }

    /**
     * 测试所有处理器返回404时最终返回404
     */
    public function testAllHandlersReturn404(): void
    {
        // 模拟两个请求处理器，都返回404
        /** @var RequestHandlerInterface&MockObject $mockHandler1 */
        $mockHandler1 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler1->method('handle')
            ->willReturn(new Response(404, body: 'Not found 1'));

        /** @var RequestHandlerInterface&MockObject $mockHandler2 */
        $mockHandler2 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler2->method('handle')
            ->willReturn(new Response(404, body: 'Not found 2'));

        $chainHandler = new ChainRequestHandler([$mockHandler1, $mockHandler2]);

        $request = new ServerRequest('GET', '/');
        $response = $chainHandler->handle($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', (string)$response->getBody());
    }

    /**
     * 测试多个处理器直到找到非404响应
     */
    public function testMultipleHandlersUntilNon404(): void
    {
        // 第一个处理器返回404
        /** @var RequestHandlerInterface&MockObject $mockHandler1 */
        $mockHandler1 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler1->method('handle')
            ->willReturn(new Response(404, body: 'Not found 1'));

        // 第二个处理器返回404
        /** @var RequestHandlerInterface&MockObject $mockHandler2 */
        $mockHandler2 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler2->method('handle')
            ->willReturn(new Response(404, body: 'Not found 2'));

        // 第三个处理器返回200
        /** @var RequestHandlerInterface&MockObject $mockHandler3 */
        $mockHandler3 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler3->method('handle')
            ->willReturn(new Response(200, body: 'Success 3'));

        // 第四个处理器不应被调用
        /** @var RequestHandlerInterface&MockObject $mockHandler4 */
        $mockHandler4 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler4->expects($this->never())
            ->method('handle');

        $chainHandler = new ChainRequestHandler();
        $chainHandler->addHandler($mockHandler1)
            ->addHandler($mockHandler2)
            ->addHandler($mockHandler3)
            ->addHandler($mockHandler4);

        $request = new ServerRequest('GET', '/');
        $response = $chainHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success 3', (string)$response->getBody());
    }

    /**
     * 测试构造函数和addHandler方法
     */
    public function testConstructorAndAddHandler(): void
    {
        // 测试通过构造函数添加处理器
        /** @var RequestHandlerInterface&MockObject $mockHandler1 */
        $mockHandler1 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler1->method('handle')
            ->willReturn(new Response(200, body: 'Success 1'));

        $chainHandler1 = new ChainRequestHandler([$mockHandler1]);
        $request = new ServerRequest('GET', '/');
        $response1 = $chainHandler1->handle($request);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals('Success 1', (string)$response1->getBody());

        // 测试通过addHandler方法添加处理器
        /** @var RequestHandlerInterface&MockObject $mockHandler2 */
        $mockHandler2 = $this->createMock(RequestHandlerInterface::class);
        $mockHandler2->method('handle')
            ->willReturn(new Response(201, body: 'Success 2'));

        $chainHandler2 = new ChainRequestHandler();
        $chainHandler2->addHandler($mockHandler2);
        $response2 = $chainHandler2->handle($request);

        $this->assertEquals(201, $response2->getStatusCode());
        $this->assertEquals('Success 2', (string)$response2->getBody());
    }

    /**
     * 测试真实请求处理器的集成
     */
    public function testIntegrationWithRealHandlers(): void
    {
        // 创建真实的请求处理器
        $handler1 = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($request->getUri()->getPath() === '/handler1') {
                    return new Response(200, body: 'Handler 1 Response');
                }
                return new Response(404, body: 'Not Found in Handler 1');
            }
        };

        $handler2 = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if ($request->getUri()->getPath() === '/handler2') {
                    return new Response(200, body: 'Handler 2 Response');
                }
                return new Response(404, body: 'Not Found in Handler 2');
            }
        };

        $chainHandler = new ChainRequestHandler([$handler1, $handler2]);

        // 测试匹配第一个处理器的请求
        $request1 = new ServerRequest('GET', '/handler1');
        $response1 = $chainHandler->handle($request1);
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals('Handler 1 Response', (string)$response1->getBody());

        // 测试匹配第二个处理器的请求
        $request2 = new ServerRequest('GET', '/handler2');
        $response2 = $chainHandler->handle($request2);
        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertEquals('Handler 2 Response', (string)$response2->getBody());

        // 测试不匹配任何处理器的请求
        $request3 = new ServerRequest('GET', '/unknown');
        $response3 = $chainHandler->handle($request3);
        $this->assertEquals(404, $response3->getStatusCode());
        $this->assertEquals('Not Found', (string)$response3->getBody());
    }
}
