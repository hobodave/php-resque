<?php

if(!class_exists('Redisent', false)) {
	require_once dirname(__FILE__) . '/../../Redisent/Redisent.php';
}

class Resque_RedisAdapter_RedisentAdapter extends Resque_RedisAdapter_AbstractRedisAdapter
{
    protected $defaultOptions = array(
        'port' => 6379
    );

    protected function initBackend(array $options = array())
    {
        if (!isset($options['host']) || !isset($options['port'])) {
            throw new Resque_Exception('Could not instantiate Redisent backend without host and port');
        }

        $this->backend = new Redisent($options['host'], $options['port']);
    }
}