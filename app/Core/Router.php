<?php
namespace App\Core;
use App\Core\NotFoundException;
use App\Core\Response;

class Router{
    protected array $routes = [];

    public function get(string $uri, callable|array $action, $middleware = null): void{
        $this->routes['GET'][$uri] = ['action' => $action, 'middleware' => $middleware];
        //   'GET' => [
        // '/restaurants' => function() { return "List of restaurants"; },
        // '/users' => function() { return "List of users"; },
        // '/orders' => function() { return "List of orders"; }
        //]
    }

    public function post(string $uri, callable|array $action, $middleware = null):void{
        $this->routes['POST'][$uri] = ['action' => $action, 'middleware' => $middleware]; 
    }

    public function put(string $uri, callable $action, $middleware = null):void{
        $this->routes['PUT'][$uri] = ['action' => $action, 'middleware' => $middleware]; 
    }

    public function delete(string $uri, callable $action, $middleware = null):void{
        $this->routes['DELETE'][$uri] = ['action' => $action, 'middleware' => $middleware]; 
    }

    public function patch(string $uri, callable $action, $middleware = null):void{
        $this->routes['PATCH'][$uri] = ['action' => $action, 'middleware' => $middleware]; 
    }


    //Get the client to the designated action 
    public function dispatch(string $method, string $uri){
        $uri = parse_url($uri, PHP_URL_PATH);
        //if full http://localhost/store?item=1
        //it returns /store part

        $route = $this->routes[$method][$uri] ?? null;
        //Method = "get, post, ......"
        if($route){

             $middleware = isset($route['middleware']) && $route['middleware'] !== null;

            if($middleware){
                call_user_func($route['middleware']);
            }

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
                
                // Call middleware
                if (isset($route['middleware']) && $route['middleware'] !== null) {
                    call_user_func($route['middleware']);
                }
                
                // Call action with parameters
                call_user_func_array($route['action'], $params);
                return;
            }
        }
        // No route found
            throw new NotFoundException("No existing URI found");
    }
}