<?php
namespace Resque\Failure;
use Resque\Worker;

/**
 * Failed Resque job.
 *
 * @package		Resque/Failure
 * @author		Chris Boulton <chris.boulton@interspire.com>
 * @copyright	(c) 2010 Chris Boulton
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class Failure
{
	/**
	 * @var string Class name representing the backend to pass failed jobs off to.
	 */
	protected static $backend;

	/**
	 * Create a new failed job on the backend.
	 *
	 * @param object $payload The contents of the job that has just failed.
	 * @param object $exception The exception generated when the job failed to run.
	 * @param object $worker Instance of Resque_Worker that was running this job when it failed.
	 * @param string $queue The name of the queue that this job was fetched from.
	 */
	public static function create($payload, \Exception $exception, Worker $worker, $queue)
	{
		$backend = static::getBackend();
		$failure = new $backend($payload, $exception, $worker, $queue);
		$failure->save();
	}

	/**
	 * Return an instance of the backend for saving job failures.
	 *
	 * @return object Instance of backend object.
	 */
	public static function getBackend()
	{
		if(static::$backend === null) {
			require_once  __DIR__ . '/Redis.php';
			static::$backend = '\Resque\Failure\Redis';
		}

		return static::$backend;
	}

	/**
	 * Set the backend to use for raised job failures. The supplied backend
	 * should be the name of a class to be instantiated when a job fails.
	 * It is your responsibility to have the backend class loaded (or autoloaded)
	 *
	 * @param string $backend The class name of the backend to pipe failures to.
	 */
	public static function setBackend($backend)
	{
		self::$backend = $backend;
	}
	
	/**
	 * Count of failures seen
	 *
	 * @return int
	 **/
	public static function count()
	{
	    $backend = static::getBackend();
	    return $backend::count();
	}

    /**
     * Returns a paginated array of Failure objects
     *
     * @return array
     */
    public static function all($start = 0, $count = 1)
    {
	    $backend = static::getBackend();
	    return $backend::all($start, $count);
    }

    /**
     * An URL where Failures can be viewed
     *
     * @return string
     **/
    public static function url()
    {
        $backend = static::getBackend();
	    return $backend::url();
    }

    /**
     * Clear all failure jobs
     *
     * @return void
     **/
    public static function clear()
    {
        $backend = static::getBackend();
	    return $backend::clear();
    }

    /**
     * Requeue
     *
     * @param  int $index
     * @return void
     **/
    public static function requeue($index)
    {
        $backend = static::getBackend();
	    return $backend::requeue($index);
    }
}