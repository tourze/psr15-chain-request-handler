<?php

declare(strict_types=1);

namespace Tourze\PSR15ChainRequestHandler;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 链式请求处理器
 *
 * 依次尝试多个处理器，如果返回404则继续尝试下一个处理器
 */
class ChainRequestHandler implements RequestHandlerInterface
{
    /**
     * @var RequestHandlerInterface[] 处理器数组
     */
    private array $handlers = [];

    /**
     * @param RequestHandlerInterface[] $handlers 要处理的处理器列表
     */
    public function __construct(array $handlers = [])
    {
        foreach ($handlers as $handler) {
            $this->addHandler($handler);
        }
    }

    /**
     * 添加一个请求处理器到链中
     */
    public function addHandler(RequestHandlerInterface $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * 依次尝试处理器，直到找到非404响应或处理完所有处理器
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // 如果没有处理器，直接返回404
        if (empty($this->handlers)) {
            return new Response(404, body: 'No handlers available');
        }

        // 遍历所有处理器
        foreach ($this->handlers as $handler) {
            $response = $handler->handle($request);

            // 如果响应不是404，直接返回
            if ($response->getStatusCode() !== 404) {
                return $response;
            }
        }

        // 所有处理器都返回了404，最终返回404
        return new Response(404, body: 'Not Found');
    }
}
