<?php

declare(strict_types=1);

namespace ListDAV\WebUI;

use ListDAV\Core\Container;
use ListDAV\Module\ModuleRegistry;
use ListDAV\Trash\TrashManager;
use ListDAV\WebUI\Controllers\ApiController;
use Sabre\DAV\ICollection;

class WebUIHandler
{
    private ApiController $api;
    private Router $router;

    public function __construct(
        Container $container,
        ModuleRegistry $registry,
        TrashManager $trashManager,
        ICollection $rootCollection,
    ) {
        $this->api = new ApiController($registry, $trashManager, $rootCollection);
        $this->router = new Router();
        $this->setupRoutes();
    }

    private function setupRoutes(): void
    {
        // Main UI page
        $this->router->get('/ui', fn() => $this->serveUI());
        $this->router->get('/ui/', fn() => $this->serveUI());

        // API endpoints
        $this->router->get('/ui/api/browse', fn() => $this->handleBrowse());
        $this->router->post('/ui/api/copy', fn() => $this->handleCopy());
        $this->router->post('/ui/api/move', fn() => $this->handleMove());
        $this->router->post('/ui/api/delete', fn() => $this->handleDelete());
        $this->router->post('/ui/api/rename', fn() => $this->handleRename());
        $this->router->post('/ui/api/mkdir', fn() => $this->handleMkdir());
        $this->router->get('/ui/api/file', fn() => $this->handleGetFile());
        $this->router->get('/ui/api/search', fn() => $this->handleSearch());
    }

    public function handle(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Check if this is a UI request
        if (!str_starts_with($uri, '/ui')) {
            return false;
        }

        // Start session for CSRF protection
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $result = $this->router->dispatch($method, $uri);

        if ($result === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return true;
        }

        return true;
    }

    private function serveUI(): void
    {
        $html = file_get_contents(__DIR__ . '/Views/index.html');

        // Inject CSRF token
        $html = str_replace(
            '</head>',
            '<meta name="csrf-token" content="' . $_SESSION['csrf_token'] . '"></head>',
            $html
        );

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    private function handleBrowse(): void
    {
        $path = $_GET['path'] ?? '/';
        $result = $this->api->browse($path);
        $this->jsonResponse($result);
    }

    private function handleCopy(): void
    {
        $this->validateCsrf();
        $data = $this->getJsonBody();
        $sources = $data['sources'] ?? [];
        $destination = $data['destination'] ?? '/';
        $result = $this->api->copy($sources, $destination);
        $this->jsonResponse($result);
    }

    private function handleMove(): void
    {
        $this->validateCsrf();
        $data = $this->getJsonBody();
        $sources = $data['sources'] ?? [];
        $destination = $data['destination'] ?? '/';
        $result = $this->api->move($sources, $destination);
        $this->jsonResponse($result);
    }

    private function handleDelete(): void
    {
        $this->validateCsrf();
        $data = $this->getJsonBody();
        $paths = $data['paths'] ?? [];
        $result = $this->api->delete($paths);
        $this->jsonResponse($result);
    }

    private function handleRename(): void
    {
        $this->validateCsrf();
        $data = $this->getJsonBody();
        $path = $data['path'] ?? '';
        $newName = $data['newName'] ?? '';
        $result = $this->api->rename($path, $newName);
        $this->jsonResponse($result);
    }

    private function handleMkdir(): void
    {
        $this->validateCsrf();
        $data = $this->getJsonBody();
        $parentPath = $data['path'] ?? '/';
        $name = $data['name'] ?? '';
        $result = $this->api->createFolder($parentPath, $name);
        $this->jsonResponse($result);
    }

    private function handleGetFile(): void
    {
        $path = $_GET['path'] ?? '';
        $result = $this->api->getFile($path);
        $this->jsonResponse($result);
    }

    private function handleSearch(): void
    {
        $query = $_GET['q'] ?? '';
        $path = $_GET['path'] ?? '/';
        $result = $this->api->search($query, $path);
        $this->jsonResponse($result);
    }

    private function validateCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if ($token !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            $this->jsonResponse(['error' => 'Invalid CSRF token']);
            exit;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonBody(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
