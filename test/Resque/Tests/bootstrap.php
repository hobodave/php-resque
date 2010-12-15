<?php

namespace Resque\Tests;

/**
 * Resque test bootstrap file - sets up a test environment.
 *
 * @package		Resque/Tests
 * @author		Chris Boulton <chris.boulton@interspire.com>
 * @copyright	(c) 2010 Chris Boulton
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
define('CWD', __DIR__);
define('RESQUE_LIB', CWD . '/../../../lib/');

define('TEST_MISC', realpath(CWD . '/../../misc/'));
define('REDIS_CONF', TEST_MISC . '/redis.conf');

// Change to the directory this file lives in. This is important, due to
// how we'll be running redis.

require_once CWD . '/TestCase.php';

// Include Resque
require_once RESQUE_LIB . 'Resque/Resque.php';
require_once RESQUE_LIB . 'Resque/Worker.php';

// Attempt to start our own redis instance for tesitng.
exec('which redis-server', $output, $returnVar);
if($returnVar != 0) {
	echo "Cannot find redis-server in path. Please make sure redis is installed.\n";
	exit(1);
}

exec('cd ' . TEST_MISC . '; redis-server ' . REDIS_CONF, $output, $returnVar);
usleep(500000);
if($returnVar != 0) {
	echo "Cannot start redis-server.\n";
	exit(1);

}

// Get redis port from conf
$config = file_get_contents(REDIS_CONF);
if(!preg_match('#^\s*port\s+([0-9]+)#m', $config, $matches)) {
	echo "Could not determine redis port from redis.conf";
	exit(1);
}

\Resque\Resque::setBackend('localhost:' . $matches[1]);

// Shutdown
function killRedis($pid)
{
    if (getmypid() !== $pid) {
        return; // don't kill from a forked worker
    }
	$config = file_get_contents(REDIS_CONF);
	if(!preg_match('#^\s*pidfile\s+([^\s]+)#m', $config, $matches)) {
		return;
	}

	$pidFile = TEST_MISC . '/' . $matches[1];
	$pid = trim(file_get_contents($pidFile));
	posix_kill((int) $pid, 9);

	if(is_file($pidFile)) {
		unlink($pidFile);
	}

	// Remove the redis database
	if(!preg_match('#^\s*dir\s+([^\s]+)#m', $config, $matches)) {
		return;
	}
	$dir = $matches[1];

	if(!preg_match('#^\s*dbfilename\s+([^\s]+)#m', $config, $matches)) {
		return;
	}

	$filename = TEST_MISC . '/' . $dir . '/' . $matches[1];
	if(is_file($filename)) {
		unlink($filename);
	}
}
register_shutdown_function('\Resque\Tests\killRedis', getmypid());

if(function_exists('pcntl_signal')) {
	// Override INT and TERM signals, so they do a clean shutdown and also
	// clean up redis-server as well.
	function sigint()
	{
	 	exit;
	}
	pcntl_signal(SIGINT, '\Resque\Tests\sigint');
	pcntl_signal(SIGTERM, '\Resque\Tests\sigint');
}

class TestJob
{
	public function perform()
	{

	}
}

class FailingJobException extends \Exception
{

}

class FailingJob
{
	public function perform()
	{
		throw new FailingJobException('Message!');
	}
}

class TestJobWithoutPerformMethod
{

}

class TestJobWithSetUp
{
	public static $called = false;
	public $args = false;

	public function setUp()
	{
		self::$called = true;
	}

	public function perform()
	{

	}
}


class TestJobWithTearDown
{
	public static $called = false;
	public $args = false;

	public function perform()
	{

	}

	public function tearDown()
	{
		self::$called = true;
	}
}