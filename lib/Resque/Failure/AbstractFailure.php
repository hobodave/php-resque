<?php
namespace Resque\Failure;
use Resque\Worker;

/**
 * Failed Resque job.
 *
 * @package		Resque/Failure
 * @author		Chris Boulton <chris.boulton@interspire.com>
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright	(c) 2010 Chris Boulton
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
abstract class AbstractFailure
{
    /**
     * Constructor
     *
     * @param string $payload
     * @param Exception $exception
     * @param Worker $worker
     * @param string $queue
     */
    public function __construct($payload, \Exception $exception, Worker $worker, $queue) {
        $this->payload = $payload;
        $this->exception = $exception;
        $this->worker = $worker;
        $this->queue = $queue;
    }

    /**
     * When a job fails, a new instance of your Failure backend is created
     * and save() is called.
     *
     * @return void
     **/
    abstract public function save();

    /**
     * The number of Failures
     *
     * @return int
     */
    public static function count()
    {
        return 0;
    }

    /**
     * Returns a paginated array of Failure objects
     *
     * @return array
     */
    public static function all($start = 0, $count = 1)
    {
        return array();
    }

    /**
     * An URL where Failures can be viewed
     *
     * @return string
     **/
    public static function url()
    {
    }

    /**
     * Clears all Failures
     *
     * @return void
     **/
    public static function clear()
    {
    }

    /**
     * Requeue
     *
     * @param  int $index
     * @return void
     **/
    public static function requeue($index)
    {
    }

    /**
     * Logging, via Worker
     *
     * @return void
     **/
    public function log($message)
    {
        $this->worker->log($message);
    }
}