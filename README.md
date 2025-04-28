# psr15-chain-request-handler

[![Latest Version](https://img.shields.io/packagist/v/tourze/psr15-chain-request-handler.svg)](https://packagist.org/packages/tourze/psr15-chain-request-handler)
[![Build Status](https://github.com/tourze/php-monorepo/actions/workflows/ci.yml/badge.svg)](https://github.com/tourze/php-monorepo/actions)
[![Coverage Status](https://coveralls.io/repos/github/tourze/php-monorepo/badge.svg?branch=main)](https://coveralls.io/github/tourze/php-monorepo?branch=main)

## Introduction

A PSR-15 compatible chain request handler for PHP. This package allows you to combine multiple request handlers in a chain, processing HTTP requests sequentially until a handler returns a non-404 response. If all handlers return 404, a final 404 response is returned.

## Features

- Chain multiple PSR-15 request handlers
- Automatically skips handlers that return 404
- Simple API for adding handlers dynamically
- Fully PSR-15 compatible
- Well-tested and easy to integrate

## Installation

Requirements:

- PHP >= 8.1
- psr/http-message ^1.1|^2.0
- psr/http-server-handler ^1.0
- nyholm/psr7 ^1.8.2

Install via Composer:

```bash
composer require tourze/psr15-chain-request-handler
```

## Quick Start

```php
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use Tourze\PSR15ChainRequestHandler\ChainRequestHandler;
use Psr\Http\Server\RequestHandlerInterface;

// Example handler that always returns 404
class NotFoundHandler implements RequestHandlerInterface {
    public function handle($request): Response {
        return new Response(404, body: 'Not Found');
    }
}

// Example handler that returns 200 for a specific path
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

## Documentation

- [API Reference](src/ChainRequestHandler.php)
- See `tests/ChainRequestHandlerTest.php` for usage scenarios

## Advanced Usage

- Add handlers dynamically with `$chain->addHandler($handler);`
- Handlers are processed in the order they are added
- If no handler returns a non-404 response, the chain returns a 404 with body 'Not Found'

## Contributing

- Please submit issues or pull requests via GitHub
- Follow PSR coding standards
- Run tests with PHPUnit before submitting PRs

## License

MIT License. See [LICENSE](LICENSE) for details.

## Changelog

See the [CHANGELOG](../../CHANGELOG.md) for release history and upgrade notes.
