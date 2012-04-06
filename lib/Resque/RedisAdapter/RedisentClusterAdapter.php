<?php

if(!class_exists('RedisentCluster', false)) {
	require_once dirname(__FILE__) . '/../../Redisent/RedisentCluster.php';
}

class Resque_RedisAdapter_RedisentClusterAdapter extends Resque_RedisAdapter_AbstractRedisAdapter
{
    protected function initBackend(array $options = array())
    {
        $this->backend = new RedisentCluster($options);
    }
}