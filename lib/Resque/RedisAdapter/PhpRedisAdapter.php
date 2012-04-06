<?php

require_once dirname(__FILE__) . '/AbstractRedisAdapter.php';

class Resque_RedisAdapter_PhpRedisAdapter extends Resque_RedisAdapter_AbstractRedisAdapter
{
    protected $defaultOptions = array(
        'port' => 6379,
        'timeout' => 0,
    );

    protected function initBackend(array $options = array())
    {
        if (!isset($options['host']) || !isset($options['port'])) {
            throw new Resque_Exception('Could not instantiate PhpRedis backend without host and port');
        }

        $this->backend = new Redis();
        $this->backend->pconnect($options['host'], $options['port'], $options['timeout']);
        $this->backend->setOption(Redis::OPT_PREFIX, $this->defaultNamespace);
    }

    public function prefix($namespace)
    {
        parent::prefix($namespace);
        $this->backend->setOption(Redis::OPT_PREFIX, $this->defaultNamespace);
    }

    public function __call($name, $args)
    {
        return call_user_func_array(array($this->backend, $name), $args);
    }
}