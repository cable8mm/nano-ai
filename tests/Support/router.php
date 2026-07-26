<?php

declare(strict_types=1);

// Router for PHP built-in web server (`php -S`). tests/Support/MockServer starts the server with this file.
// Mimics responses matching the actual OpenAI/OpenRouter format to verify that HttpClient/Client
// properly handle real cURL request-response round trips without accessing the network.

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$rawBody = file_get_contents('php://input') ?: '';
$decoded = json_decode($rawBody, true);
$decoded = is_array($decoded) ? $decoded : [];

header('Content-Type: application/json');

switch ($path) {
    case '/success':
        http_response_code(200);
        echo json_encode(['choices' => [['message' => ['content' => 'mock server response']]]], JSON_UNESCAPED_UNICODE);
        break;

    case '/echo-has-image':
        // Echoes back whether the request payload actually contained image_url for verification.
        http_response_code(200);
        $hasImage = isset($decoded['messages'][0]['content'][1]['image_url']['url']);
        echo json_encode([
            'choices' => [['message' => ['content' => $hasImage ? 'image received' : 'no image']]],
        ], JSON_UNESCAPED_UNICODE);
        break;

    case '/auth-error':
        http_response_code(401);
        echo json_encode(['error' => ['message' => 'Invalid API key']], JSON_UNESCAPED_UNICODE);
        break;

    case '/rate-limit':
        http_response_code(429);
        echo json_encode(['error' => ['message' => 'Rate limit exceeded']], JSON_UNESCAPED_UNICODE);
        break;

    case '/server-error':
        http_response_code(500);
        echo json_encode(['error' => ['message' => 'Internal server error']], JSON_UNESCAPED_UNICODE);
        break;

    case '/malformed-json':
        http_response_code(200);
        echo '{not valid json';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => ['message' => 'not found: '.$path]], JSON_UNESCAPED_UNICODE);
}
