<?php

require_once dirname(__FILE__) . '/RedisAdapterInterface.php';

abstract class Resque_RedisAdapter_AbstractRedisAdapter implements Resque_RedisAdapter_RedisAdapterInterface
{
    /**
     * Instance of Backend Redis object
     *
     * e.g. Redisent, RedisentCluster, Redis
     *
     * @var mixed
     */
    protected $backend = null;

    /**
     * Default options for Adapter - should be overridden by concrete classes
     * @var array
     */
    protected $defaultOptions = array();

	/**
	 * @var array List of all commands in Redis that supply a key as their
	 *	first argument. Used to prefix keys with the Resque namespace.
	 */
	protected $keyCommands = array(
		'exists',
		'del',
		'type',
		'keys',
		'expire',
		'ttl',
		'move',
		'set',
		'get',
		'getset',
		'setnx',
		'incr',
		'incrby',
		'decr',
		'decrby',
		'rpush',
		'lpush',
		'llen',
		'lrange',
		'ltrim',
		'lindex',
		'lset',
		'lrem',
		'lpop',
		'rpop',
		'sadd',
		'srem',
		'spop',
		'scard',
		'sismember',
		'smembers',
		'srandmember',
		'zadd',
		'zrem',
		'zrange',
		'zrevrange',
		'zrangebyscore',
		'zcard',
		'zscore',
		'zremrangebyscore',
		'sort'
	);

    /**
     * Redis namespace
     * @var string
     */
    protected $defaultNamespace = 'resque:';

    public function __construct($options = array())
    {
        $options = array_merge($this->defaultOptions, $options);
        $this->initBackend($options);
    }

    public function prefix($namespace)
    {
        if (strpos($namespace, ':') === false) {
   	        $namespace .= ':';
   	    }

   	    $this->defaultNamespace = $namespace;
    }

    public function __call($name, $args)
    {
        if(in_array($name, $this->keyCommands)) {
            $args[0] = $this->defaultNamespace . $args[0];
        }

        return call_user_func_array(array($this->backend, $name), $args);
    }

    abstract protected function initBackend(array $options = array());
}