<?php

/**
 *
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 **/
interface Resque_RedisAdapter_RedisAdapterInterface
{
    public function prefix($namespace);
    public function __call($name, $args);
}