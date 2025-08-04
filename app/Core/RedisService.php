<?php
namespace App\Core;

use Predis\Client;

class RedisService
{
    private Client $client; //This is the client of the redis
    private int $defaultTtl;

    //Its like a controller to communicate with actual redis in the backend
    public function __construct()
    {

        $this->client = new Client([
            'scheme' => 'tcp',
            'host'   => (string) $_ENV['REDIS_HOST'] ?? 'redis',
            'port'   => (int) $_ENV['REDIS_PORT'] ?? 6379,
        ]);
        $this->defaultTtl  = $config['ttl'] ?? 300;

    
    }

    /* ---------- Cache helpers ---------- */
    public function remember(string $key, int $ttl, callable $callback)
    {
        //Use the client to check is the key is exist within redis
        if ($this->client->exists($key)) {
            return unserialize($this->client->get($key));
            //Rebuilt the variable/value
            
        }
        //call back is a function that calls to get value
        $value = $callback(); //When cache miss
        $this->client->setex($key, $ttl, serialize($value));
        return $value;
    }

    /** Remove one or many keys (supports wildcard flush) */
    public function forget(string|array $keys): void
    {
        $keys = (array) $keys;
        $this->client->del($keys);
    }
    /** Simple primitives if you need them */
    public function set(string $key, mixed $val, ?int $ttl = null): void
    {
        $jsonValue = $this->encode($val);
        $this->client->setex(
            $key, 
            $ttl ?? $this->defaultTtl, 
            $jsonValue);
    }
    public function get(string $key, mixed $default = null): mixed
    {

        
        $decodeJson = $this->decode((string) $this->client->get($key));
        return $this->client->exists($key)
            ? $decodeJson
            : $default;
    }

    /** ---------- Internal helpers ---------- */
    private function encode(mixed $v): string  { 
        return json_encode($v, JSON_THROW_ON_ERROR); 
    }
    private function decode(string $v): mixed {
        if (empty($v)) {
            return null; // Or any other appropriate default value
        }
        {{ return json_decode($v, true, 512, JSON_THROW_ON_ERROR); }}
    }
}