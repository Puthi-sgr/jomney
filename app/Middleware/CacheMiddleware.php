<?php
namespace App\Middleware;

use App\Core\{Request, RedisService, Response};

class CacheMiddleware
{
    public function __construct(RedisService $redis,  Request $req)
    {
         error_log("CacheMiddleware initialized with Redis connection");
        $this->req = $req; // Store the request for later use
        $this->redis = $redis;
         error_log("Redis connection established in CacheMiddleware");
       
       
    }

    public function __invoke(Request $req, callable $next): void
    {
        error_log("CacheMiddleware invoked for: " . $req->path());
        $this->req = $req; // Store the request for later use
        $this->handle($req, $next);
    }

    public function handle(Request $req, callable $next): Response
    {
        error_log("Handling request in CacheMiddleware for: " . $req->path());
        /* Cache only safe, idempotent GET requests */
        if ($req->method() !== 'GET') {
            error_log("Skipping cache for non-GET request: " . $req->method());
            return $next($req);
            // Skip caching for non-GET requests
        }

        /* Build a unique cache key: verb + path + sorted query */
        $key = sprintf( //String formatter
            "httpcache:%s:%s?%s",
            strtolower($req->method()), // get
            $req->path(), // /api/v1/books
            http_build_query($req->get, '', '&', PHP_QUERY_RFC3986) //This is query parameters -> ?category=books&sort=asc
        );
        error_log("Cache key generated: $key");
        // Example key: httpcache:get:/api/v1/books?category=books&sort=asc
       
        /* Try hit  */
        $cached = $this->redis->get($key);
        if ($cached) {
            error_log("Cache hit for key: $key");
            return new Response($cached['body'], $cached['code'], $cached['headers']);
        }

        /* Run downstream & capture */
        // if cache miss
        // We inject the request to the next middleware 
        error_log("Request: ". $req->path() . " with method: " . $req->method());
        error_log("Cache miss for key: $key");
        $response = $next($req);//Which most likely is the controller
      
        
        /* Persist only 2xx/3xx responses for 60 s  */
        if ($response->isSuccessful()) {
            error_log("Storing response in cache for key: $key");
            //if the res succeeded
            //We store the response in redis
            $this->redis->set($key, [
                'body'    => $response->body(),
                'headers' => $response->headers(),
                'code'    => $response->status(),
            ], 60);
        }
        return $response;
    }

}