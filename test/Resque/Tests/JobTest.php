<?php
namespace Resque\Tests;
use Resque\Worker,
    Resque\Resque,
    Resque\Job,
    Resque\Stat;

require_once __DIR__ . '/bootstrap.php';

/**
 * \Resque\Job tests.
 *
 * @package		Resque/Tests
 * @author		Chris Boulton <chris.boulton@interspire.com>
 * @copyright	(c) 2010 Chris Boulton
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class JobTest extends TestCase
{
	protected $worker;

	public function setUp()
	{
		parent::setUp();

		// Register a worker to test with
		$this->worker = new Worker('jobs');
		$this->worker->registerWorker();
	}

	public function testJobCanBeQueued()
	{
		$this->assertTrue((bool) Resque::enqueue('jobs', '\Resque\Tests\TestJob'));
	}

	public function testQeueuedJobCanBeReserved()
	{
		Resque::enqueue('jobs', '\Resque\Tests\TestJob');

		$job = Job::reserve('jobs');
		if($job == false) {
			$this->fail('Job could not be reserved.');
		}
		$this->assertEquals('jobs', $job->queue);
		$this->assertEquals('\Resque\Tests\TestJob', $job->payload['class']);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testObjectArgumentsCannotBePassedToJob()
	{
		$args = new \stdClass;
		$args->test = 'somevalue';
		Resque::enqueue('jobs', '\Resque\Tests\TestJob', $args);
	}

	public function testQueuedJobReturnsExactSamePassedInArguments()
	{
		$args = array(
			'int' => 123,
			'numArray' => array(
				1,
				2,
			),
			'assocArray' => array(
				'key1' => 'value1',
				'key2' => 'value2'
			),
		);
		Resque::enqueue('jobs', '\Resque\Tests\TestJob', $args);
		$job = Job::reserve('jobs');

		$this->assertEquals($args, $job->payload['args']);
	}

	public function testAfterJobIsReservedItIsRemoved()
	{
		Resque::enqueue('jobs', '\Resque\Tests\TestJob');
		Job::reserve('jobs');
		$this->assertFalse(Job::reserve('jobs'));
	}

	public function testRecreatedJobMatchesExistingJob()
	{
		$args = array(
			'int' => 123,
			'numArray' => array(
				1,
				2,
			),
			'assocArray' => array(
				'key1' => 'value1',
				'key2' => 'value2'
			),
		);

		Resque::enqueue('jobs', '\Resque\Tests\TestJob', $args);
		$job = Job::reserve('jobs');

		// Now recreate it
		$job->recreate();

		$newJob = Job::reserve('jobs');
		$this->assertEquals($job->payload['class'], $newJob->payload['class']);
		$this->assertEquals($job->payload['args'], $newJob->payload['args']);
	}

	public function testFailedJobExceptionsAreCaught()
	{
		$payload = array(
			'class' => '\Resque\Tests\FailingJob',
			'args' => null
		);
		$job = new Job('jobs', $payload);
		$job->worker = $this->worker;

		$this->worker->perform($job);

		$this->assertEquals(1, Stat::get('failed'));
		$this->assertEquals(1, Stat::get('failed:'.$this->worker));
	}

	/**
	 * @expectedException \Resque\Exception
	 */
	public function testJobWithoutPerformMethodThrowsException()
	{
		Resque::enqueue('jobs', '\Resque\Tests\TestJobWithoutPerformMethod');
		$job = $this->worker->reserve();
		$job->worker = $this->worker;
		$job->perform();
	}

	/**
	 * @expectedException \Resque\Exception
	 */
	public function testInvalidJobThrowsException()
	{
		Resque::enqueue('jobs', 'InvalidJob');
		$job = $this->worker->reserve();
		$job->worker = $this->worker;
		$job->perform();
	}

	public function testJobWithSetUpCallbackFiresSetUp()
	{
		$payload = array(
			'class' => '\Resque\Tests\TestJobWithSetUp',
			'args' => array(
				'somevar',
				'somevar2',
			),
		);
		$job = new Job('jobs', $payload);
		$job->perform();

		$this->assertTrue(TestJobWithSetUp::$called);
	}

	public function testJobWithTearDownCallbackFiresSetUp()
	{
		$payload = array(
			'class' => '\Resque\Tests\TestJobWithTearDown',
			'args' => array(
				'somevar',
				'somevar2',
			),
		);
		$job = new Job('jobs', $payload);
		$job->perform();

		$this->assertTrue(TestJobWithTearDown::$called);
	}
}