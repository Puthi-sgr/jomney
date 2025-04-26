<?php
namespace App\Core;
use App\Core\NotFoundException;
use App\Core\Response;

class Router{
    protected array $routes = [];

    public function get(string $uri, callable $action, $middleware = null): void{
        $this->routes['GET'][$uri] = ['action' => $action, 'middleware' => $middleware];
        //   'GET' => [
        // '/restaurants' => function() { return "List of restaurants"; },
        // '/users' => function() { return "List of users"; },
        // '/orders' => function() { return "List of orders"; }
        //]
    }

    public function post(string $uri, callable $action):void{
        $this->routes['POST'][$uri] = $action; 
    }

    //Get the client to the designated action 
    public function dispatch(string $method, string $uri){
        $uri = parse_url($uri, PHP_URL_PATH);
        //if full http://localhost/store?item=1
        //it returns /store part

        $route = $this->routes[$method][$uri] ?? null;
        //Method = "get, post, ......"
        if(!$route){
            throw new NotFoundException("No existing URI  found");     
            return;
        }
        $middleware = isset($route['middleware']);

        if($middleware){
            call_user_func($route['middleware']);
        }

        call_user_func($route['action']);
    }
}