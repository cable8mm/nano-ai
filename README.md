# NanoAI

[![code-style](https://github.com/cable8mm/nano-ai/actions/workflows/code-style.yml/badge.svg)](https://github.com/cable8mm/nano-ai/actions/workflows/code-style.yml)
[![run-tests](https://github.com/cable8mm/nano-ai/actions/workflows/run-tests.yml/badge.svg)](https://github.com/cable8mm/nano-ai/actions/workflows/run-tests.yml)
![PHP Version](https://img.shields.io/packagist/dependency-v/cable8mm/nano-ai/php)
![Packagist Version](https://img.shields.io/packagist/v/cable8mm/nano-ai)
![Packagist Downloads](https://img.shields.io/packagist/dt/cable8mm/nano-ai)
![Packagist License](https://img.shields.io/packagist/l/cable8mm/nano-ai)

Ultra-lightweight, zero-config PHP AI SDK. No agents/RAG — just `generate()` for text + image (multimodal) calls.
Use it for testing, lightweight pipelines, and idea validation.

## Supported Providers

| Provider     | Description                                                                                                                           |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------- |
| `openai`     | Direct connection to OpenAI Chat Completions                                                                                          |
| `openrouter` | Call most models (OpenAI/Gemini/DeepSeek/Qwen, etc.) via a single API by just changing the model name. Free models use `:free` suffix |

## Installation

```bash
composer require cable8mm/nano-ai
```

## Usage

```php
use Cable8mm\NanoAI\Client;

// If API Key is omitted, it will be automatically read from the OPENAI_API_KEY / OPENROUTER_API_KEY environment variables.
$client = new Client(provider: 'openai');

echo $client->generate('Hello?');

// Images: supports URL, data URI, and local file path (auto base64 conversion)
echo $client->generate('Describe this photo', imageUrl: '/path/to/photo.jpg');
```

To test with free/low-cost models:

```php
$client = new Client(
    provider: 'openrouter',
    model: 'deepseek/deepseek-chat:free', // Check the latest list at https://openrouter.ai/models?max_price=0
);
```

## Exceptions

- `AuthenticationException` — Missing/invalid API Key (401/403)
- `RateLimitException` — Request rate limit exceeded (429), common with free models
- `ApiException` — Other API errors, provides `getStatusCode()` / `getResponseBody()`
- `NetworkException` — cURL-level failures such as timeouts

All extend `NanoAIException`, so you can catch them all at once.

## Using Guzzle as the HTTP Client (Optional)

By default, nano-ai uses a minimal cURL-based HTTP client with zero external dependencies.
If you prefer Guzzle (or need its advanced features like middleware, retries, proxies, etc.),
install it and inject the provided `GuzzleHttpClient`:

```bash
composer require guzzlehttp/guzzle
```

```php
use Cable8mm\NanoAI\Client;
use Cable8mm\NanoAI\Http\GuzzleHttpClient;

// Use Guzzle with default timeout settings (30s / 10s connect)
$client = new Client(
    provider: 'openai',
    apiKey: 'sk-...',
    httpClient: new GuzzleHttpClient(),
);

// Or inject a pre-configured Guzzle client for full control
$guzzle = new \GuzzleHttp\Client([
    'timeout' => 60,
    'proxy' => 'http://localhost:8080',
]);
$client = new Client(
    provider: 'openai',
    apiKey: 'sk-...',
    httpClient: new GuzzleHttpClient($guzzle),
);
```

The `GuzzleHttpClient` implements the same `HttpClientInterface` as the default cURL client,
so it is a drop-in replacement. All nano-ai features (timeouts, error handling, etc.) work identically.

## Adding a New Provider

Create a class implementing `NanoAI\Provider\ProviderInterface` (if it's an OpenAI-compatible API, you can extend `AbstractOpenAICompatibleProvider`), then add one line to `ProviderFactory::make()`.

No need to modify `Client` or `HttpClient`.

## Development

```bash
composer install
composer test
composer lint
```

## License

MIT
