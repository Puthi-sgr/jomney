<?php
// app/Middleware/ResponseCache.php
namespace App\Middleware;

use App\Core\RedisService;
use App\Core\Request;
use App\Core\Response;

class ResponseCache
{
  public function __construct(private RedisService $redis) {}
   public function handle(Request $req, callable $next): Response
    {
        // Cache only idempotent GETs, skip auth-protected routes if needed
        if ($req->method !== 'GET') { return $next($req); }

        $key = "route:{$req->path}:".md5(http_build_query($req->queryParams));
        return $this->redis->remember($key, fn() => $next($req)->clone(), 60);
    }
}

