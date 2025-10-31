# psr15-chain-request-handler

[English](README.md) | [中文](README.zh-CN.md)

[![最新版本](https://img.shields.io/packagist/v/tourze/psr15-chain-request-handler.svg)](https://packagist.org/packages/tourze/psr15-chain-request-handler)
[![构建状态](https://github.com/tourze/php-monorepo/actions/workflows/ci.yml/badge.svg)](https://github.com/tourze/php-monorepo/actions)
[![测试覆盖率](https://coveralls.io/repos/github/tourze/php-monorepo/badge.svg?branch=main)](https://coveralls.io/github/tourze/php-monorepo?branch=main)
[![许可证](https://img.shields.io/packagist/l/tourze/psr15-chain-request-handler.svg)](https://packagist.org/packages/tourze/psr15-chain-request-handler)

## 简介

一个符合 PSR-15 标准的 PHP 链式请求处理器。该包允许你将多个请求处理器按顺序组合成链，依次处理 HTTP 请求，直到某个处理器返回非 404 响应。如果所有处理器都返回 404，则最终返回 404 响应。

## 功能特性

- 支持链式组合多个 PSR-15 请求处理器
- 自动跳过返回 404 的处理器
- 简洁的 API，支持动态添加处理器
- 完全兼容 PSR-15
- 测试完善，易于集成

## 安装说明

环境要求：

- PHP >= 8.1
- psr/http-message ^1.1|^2.0
- psr/http-server-handler ^1.0
- nyholm/psr7 ^1.8.2

通过 Composer 安装：

```bash
composer require tourze/psr15-chain-request-handler
```

## 快速开始

```php
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Tourze\PSR15ChainRequestHandler\ChainRequestHandler;
use Psr\Http\Server\RequestHandlerInterface;

// 示例处理器，总是返回 404
class NotFoundHandler implements RequestHandlerInterface {
    public function handle($request): Response {
        return new Response(404, body: 'Not Found');
    }
}

// 示例处理器，仅在特定路径返回 200
class HelloHandler implements RequestHandlerInterface {
    public function handle($request): Response {
        if ($request->getUri()->getPath() === '/hello') {
            return new Response(200, body: 'Hello World');
        }
        return new Response(404, body: 'Not Found');
    }
}

$chain = new ChainRequestHandler([
    new NotFoundHandler(),
    new HelloHandler(),
]);

$request = new ServerRequest('GET', '/hello');
$response = $chain->handle($request);
// $response->getStatusCode() === 200
```

## 详细文档

- [API 文档](src/ChainRequestHandler.php)
- 更多用法见 `tests/ChainRequestHandlerTest.php`

## 高级特性

- 可通过 `$chain->addHandler($handler);` 动态添加处理器
- 处理器按添加顺序依次执行
- 如果所有处理器都返回 404，最终响应为 404 且 body 为 'Not Found'

## 贡献指南

- 欢迎通过 GitHub 提交 Issue 或 PR
- 遵循 PSR 编码规范
- PR 前请先用 PHPUnit 跑测试

## 版权和许可

MIT License，详见 [LICENSE](LICENSE)

## 更新日志

详见 [CHANGELOG](../../CHANGELOG.md) 获取发布历史和升级说明。
