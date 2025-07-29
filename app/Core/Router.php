<?php
namespace App\Core;
use App\Core\NotFoundException;
use App\Core\Response;
use App\Core\Request;

class Router{

    public function __construct(private Request $request) {}
    protected array $routes = [];

    private function add(string $verb, string $uri, callable|array $action, array $mws): void
    {
        $this->routes[$verb][$uri] = ['action' => $action, 'middleware' => $mws];
    }

    //...$mws allows for multiple middlewares to be passed
    public function get   (string $u, callable|array $a, ...$mws){ $this->add('GET'   ,$u,$a,$mws);}
    public function post  (string $u, callable|array $a, ...$mws){ $this->add('POST'  ,$u,$a,$mws);}
    public function put   (string $u, callable|array $a, ...$mws){ $this->add('PUT'   ,$u,$a,$mws);}
    public function patch (string $u, callable|array $a, ...$mws){ $this->add('PATCH' ,$u,$a,$mws);}
    public function delete(string $u, callable|array $a, ...$mws){ $this->add('DELETE',$u,$a,$mws);}

    //Get the client to the designated action 
    public function dispatch(string $method, string $uri){

        $method = $this->request->method();     // GET / POST …
        $uri    = $this->request->path();       // cleansed path

        $route = $this->routes[$method][$uri] ?? null;
         /* 1. exact match */
        if ($route) {
            $this->runMiddleware($route['middleware']);
            $this->callAction($route['action']);
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
                // ['42', '123']  with /users/42/posts/123

                //set the router params with request
                $this->request->setRouteParams(
                    $this->buildParamMap($pattern,$params)
                );

                $this->runMiddleware($route['middleware']);
                $this->callAction($route['action'], $params);
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
                    {{ (new $mw($this->request))->check(); }}
                } else {
                    throw new \RuntimeException("Bad middleware: ".print_r($mw,true));
                }
            }
        }

    private function buildParamMap(string $pattern, array $matches): array 
    {   
        //Route pattern: /users/{id}/posts/{post_id}
        //URL: /users/123/posts/456
        $params = [];
        //Result: ['id' => '123', 'post_id' => '456']
        // Extract parameter names from {param} in pattern
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        // Combine param names with their matched values
        // Starting from index 1 to skip the full match
        foreach ($paramNames[1] as $index => $name) {
            $params[$name] = $matches[$index];
        }
        
        return $params;
    }

    // Call action checks whether it is a controller method or closure
    // and injects Request as first argument if needed
    private function callAction(callable|array $action, array $routeParams = []): void
    {
        // if action expects a Request first argument, give it
        // e.g. action = [ControllerClass, 'methodName'] or Closure
        $ref = is_array($action)
             ? new \ReflectionMethod($action[0], $action[1])
             //action 0 controller class name, action 1 controller method name
             : new \ReflectionFunction($action);

        $args = [];
        //it is checking the number of params from controller methods
        if ($ref->getNumberOfParameters() > 0 &&
            $ref->getParameters()[0]->getType()?->getName() === Request::class) {
            $args[] = $this->request;
        }
        // append route param values (positional)
        $args = array_merge($args, $routeParams);

        //A smart way to call the action pair it with the params
        $ref->invokeArgs(
            is_array($action) ? $action[0] : null,
            $args
        );
    }
    /* --- call controller/closure with DI: inject Request first if wanted --- */
}