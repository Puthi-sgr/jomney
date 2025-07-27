<?php
namespace App\Core;
use App\Core\NotFoundException;
use App\Core\Response;

class Router{
    protected array $routes = [];

    private function add(string $verb, string $uri, callable|array $action, array $mws): void
    {
        $this->routes[$verb][$uri] = ['action' => $action, 'middleware' => $mws];
    }


    public function get   (string $u, callable|array $a, ...$mws){ $this->add('GET'   ,$u,$a,$mws);}
    public function post  (string $u, callable|array $a, ...$mws){ $this->add('POST'  ,$u,$a,$mws);}
    public function put   (string $u, callable|array $a, ...$mws){ $this->add('PUT'   ,$u,$a,$mws);}
    public function patch (string $u, callable|array $a, ...$mws){ $this->add('PATCH' ,$u,$a,$mws);}
    public function delete(string $u, callable|array $a, ...$mws){ $this->add('DELETE',$u,$a,$mws);}

    //Get the client to the designated action 
    public function dispatch(string $method, string $uri){
        $uri = parse_url($uri, PHP_URL_PATH);
        //if full http://localhost/store?item=1
        //it returns /store part
        $route = $this->routes[$method][$uri] ?? null;
        //Method = "get, post, ......"
        if ($route) {
            $this->runMiddleware($route['middleware']);
            call_user_func($route['action']);
            return;
        }

        // Check for dynamic routes
        foreach ($this->routes[$method] ?? [] as $pattern => $route) {
            // Convert {id} to regex pattern
            $regex = preg_replace('/\{([^}]+)\}/', '(\d+)', $pattern);
            $regex = '#^' . $regex . '$#';
            
            if (preg_match($regex, $uri, $matches)) {
                // Extract parameters
                array_shift($matches); // Remove full match
                $params = $matches;
                
                $this->runMiddleware($route['middleware']);
                call_user_func_array($route['action'], $params);
                return;
            }
        }
        // No route found
            throw new NotFoundException("No existing URI found");
    }

    private function runMiddleware(array $middlewares): void
    {
        foreach ($middlewares as $mw) {
            error_log("Running middleware: " . print_r($mw, true));
            if ($mw === null) continue;          // skip blanks from routes w/ no mws
            if (is_callable($mw)) {
                call_user_func($mw);             // e.g. [Class,'method'] or closure
            } elseif (is_string($mw) && class_exists($mw)) {
                (new $mw)->handle();             // supports class w/ handle()
            } else {
                throw new \RuntimeException("Bad middleware: ".print_r($mw,true));
            }
        }
    }
}