<?php
namespace Resque\Failure;
use Resque\Resque;

require_once __DIR__ . '/AbstractFailure.php';

/**
 * Redis backend for storing failed Resque jobs.
 *
 * @package		Resque/Failure
 * @author		Chris Boulton <chris.boulton@interspire.com>
 * @copyright	(c) 2010 Chris Boulton
 * @license		http://www.opensource.org/licenses/mit-license.php
 */
class Redis extends AbstractFailure
{
    public function save()
    {
        $data = new \stdClass;
		$data->failed_at = strftime('%a %b %d %H:%M:%S %Z %Y');
		$data->payload = $this->payload;
		$data->exception = get_class($this->exception);
		$data->error = $this->exception->getMessage();
		$data->backtrace = explode("\n", $this->exception->getTraceAsString());
		$data->worker = (string) $this->worker;
		$data->queue = $this->queue;
		$data = json_encode($data);
		Resque::redis()->rpush('failed', $data);
    }

    public static function count()
    {
        return (int) Resque::redis()->llen('failed');
    }

    public static function all($start = 0, $count = 1)
    {
        /*
            TODO fix this
        */
        //return Resque::list_range('failed', $start, $count);
    }

    public static function clear()
    {
        return Resque::redis()->del('failed');
    }

    public static function requeue($index)
    {
        /*
            TODO Finish requeue support
        */
        $item = static::all($index);
        $item['retried_at'] = strftime('%a %b %d %H:%M:%S %Z %Y');
        Resque::redis()->lset('failed', $index, json_encode($item));
        // Job.create(item['queue'], item['payload']['class'], *item['payload']['args'])
    }
}