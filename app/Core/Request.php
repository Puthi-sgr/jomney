<?php
namespace App\Core;

/**
 * Unified HTTP request wrapper.
 *
 *  ── Features ──────────────────────────────────────────────
 *  • Supports JSON & form-data bodies (auto-parse)  
 *  • Easy access to headers, bearer-token, files, route params  
 *  • Works with multi-middleware pipeline (immutable-ish)  
 *  • Minimal dependencies – pure PHP 8+
 */
class Request {
    public array $get;
    public array $post;
    public array $server;
    public array $session;
    public array $files;
    public array $cookies;
    public mixed $json;

      /* Router-injected */
    private array $routeParams = []; 

    //Snap shop of the request data
    //This is the constructor that will be called when the Request class is instantiated
    public function __construct() {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->server = $_SERVER ?? [];
        $this->session = $_SESSION ?? [];
        $this->files = $_FILES ?? [];
        $this->cookies = $_COOKIE ?? [];

        $this->json = $this->isJson()
            ? json_decode(file_get_contents('php://input'), true)
            : null;
    }

    /* ---------- Core helpers ---------- */

    /** Combined input: JSON ▸ POST ▸ GET (priority) */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json[$key]
            ?? $this->post[$key]
            ?? $this->get[$key]
            ?? $default;
    }
   
    /** Return array of *all* key/value inputs (merged) */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json ?? []);
    }

    /** Path only (no query-string) e.g. “/api/vendors/1” */
    public function path(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
    }

    /* ---------- Header & auth ---------- */

    /** Retrieve HTTP header (case-insensitive) */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$key] ?? $default;
    }

    /** Bearer JWT from “Authorization: Bearer <token>” or null */
    //Grab the server and check the Authorization header
    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        return (preg_match('/Bearer\s+(.+)/i', $auth ?? '', $m))
               ? trim($m[1]) : null;
    }

 
    /* ---------- JSON / multipart helpers ---------- */

    public function isJson(): bool
    {
        return str_contains(
            strtolower($this->server['CONTENT_TYPE'] ?? ''),
            'application/json'
        );
    }

    public function isMultipart(): bool
    {
        return str_contains(
            strtolower($this->server['CONTENT_TYPE'] ?? ''),
            'multipart/form-data'
        );
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;   // ['tmp_name'=>…, 'name'=>…]
    }

    /* ---------- Router param support ---------- */

    /** Called by router after route-match */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }
    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /* ---------- Convenience ---------- */

    public function ip(): string       { return $this->server['REMOTE_ADDR'] ?? ''; }
    public function userAgent(): string{ return $this->server['HTTP_USER_AGENT'] ?? ''; }

}