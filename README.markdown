php-resque: PHP Resque Worker (and Enqueue)
===========================================

Resque is a Redis-backed library for creating background jobs, placing
those jobs on multiple queues, and processing them later.

Resque was pioneered and is developed by the fine folks at GitHub (yes,
I am a kiss-ass), and written in Ruby.

What you're seeing here is an almost direct port of the Resque worker
and enqueue system to PHP, which I've thrown together because I'm sure
my PHP developers would have a fit if they had to write a line of Ruby.

For more information on Resque, visit the official GitHub project:
 <http://github.com/defunkt/resque/>

And for background information, the launch post on the GitHub blog:
 <http://github.com/blog/542-introducing-resque>

The PHP port does NOT include its own web interface for viewing queue
stats, as the data is stored in the exact same expected format as the
Ruby version of Resque.

The PHP port allows for much the same as the Ruby version of Rescue:

* Workers can be distributed between multiple machines
* Includes support for priorities (queues)
* Resilient to memory leaks (fork)
* Expects failure

In addition, it also:

* Has the ability to track the status of jobs
* Will mark a job as failed, if a forked child running a job does
not exit with a status code as 0
* Has built in support for `setUp` and `tearDown` methods, called
pre and post jobs

## Jobs ##

### Queueing Jobs ###

Jobs are queued as follows:

    require_once 'lib/Resque.php';

	// Required if redis is located elsewhere
	Resque::setBackend('localhost', 6379);

	$args = array(
		'name' => 'Chris'
	);
	Resque::enqueue('default', 'My_Job', $args);

### Defining Jobs ###

Each job should be in it's own class, and include a `perform` method.

	class My_Job
	{
		public function perform()
		{
			// Work work work
			echo $this->args['name'];
		}
	}

When the job is run, the class will be instantiated and any arguments
will be set as an array on the instantiated object, and are accessible
via `$this->args`.

Any exception thrown by a job will result in the job failing - be
careful here and make sure you handle the exceptions that shouldn't
result in a job failing.

Jobs can also have `setUp` and `tearDown` methods. If a `setUp` method
is defined, it will be called before the `perform` method is run.
The `tearDown` method if defined, will be called after the job finishes.

	class My_Job
	{
		public function setUp()
		{
			// ... Set up environment for this job
		}
		
		public function perform()
		{
			// .. Run job
		}
		
		public function tearDown()
		{
			// ... Remove environment for this job
		}
	}

### Tracking Job Statuses ###

php-resque has the ability to perform basic status tracking of a queued
job. The status information will allow you to check if a job is in the
queue, currently being run, has finished, or failed.

To track the status of a job, pass `true` as the fourth argument to
`Resque::enqueue`. A token used for tracking the job status will be
returned:

	$token = Resque::enqueue('default', 'My_Job', $args, true);
	echo $token;

To fetch the status of a job:

	$status = new \Resque\Job\Status($token);
	echo $status->get(); // Outputs the status

Job statuses are defined as constants in the `\Resque\Job\Status` class.
Valid statuses include:

* `\Resque\Job\Status::STATUS_WAITING` - Job is still queued
* `\Resque\Job\Status::STATUS_RUNNING` - Job is currently running
* `\Resque\Job\Status::STATUS_FAILED` - Job has failed
* `\Resque\Job\Status::STATUS_COMPLETE` - Job is complete
* `false` - Failed to fetch the status - is the token valid?

Statuses are available for up to 24 hours after a job has completed
or failed, and are then automatically expired. A status can also
forcefully be expired by calling the `stop()` method on a status
class.

## Workers ##

Workers work in the exact same way as the Ruby workers. For complete
documentation on workers, see the original documentation.

A basic "up-and-running" resque.php file is included that sets up a
running worker environment is included in the root directory.

The exception to the similarities with the Ruby version of resque is
how a worker is initially setup. To work under all environments,
not having a single environment such as with Ruby, the PHP port makes
*no* assumptions about your setup.

To start a worker, it's very similar to the Ruby version:

    $ QUEUE=file_serve php resque.php

It's your responsibility to tell the worker which file to include to get
your application underway. You do so by setting the `APP_INCLUDE` environment
variable:

   $ QUEUE=file_serve APP_INCLUDE=../application/init.php php resque.php

Getting your application underway also includes telling the worker your job
classes, by means of either an autoloader or including them.

### Logging ###

The port supports the same environment variables for logging to STDOUT.
Setting `VERBOSE` will print basic debugging information and `VVERBOSE`
will print detailed information.

    $ VERBOSE QUEUE=file_serve php resque.php
    $ VVERBOSE QUEUE=file_serve php resque.php

### Priorities and Queue Lists ###

Similarly, priority and queue list functionality works exactly
the same as the Ruby workers. Multiple queues should be separated with
a comma, and the order that they're supplied in is the order that they're
checked in.

As per the original example:

	$ QUEUE=file_serve,warm_cache php resque.php

The `file_serve` queue will always be checked for new jobs on each
iteration before the `warm_cache` queue is checked.

### Running All Queues ###

All queues are supported in the same manner and processed in alphabetical
order:

    $ QUEUE=* php resque.php

### Running Multiple Workers ###

Multiple workers ca be launched and automatically worked by supplying
the `COUNT` environment variable:

	$ COUNT=5 php resque.php

### Forking ###

Similarly to the Ruby versions, supported platforms will immediately
fork after picking up a job. The forked child will exit as soon as
the job finishes.

The difference with php-resque is that if a forked child does not
exit nicely (PHP error or such), php-resque will automatically fail
the job.

### Signals ###

Signals also work on supported platforms exactly as in the Ruby
version of Resque:

* `QUIT` - Wait for child to finish processing then exit
* `TERM` / `INT` - Immediately kill child then exit
* `USR1` - Immediately kill child but don't exit
* `USR2` - Pause worker, no new jobs will be processed
* `CONT` - Resume worker.

### Process Titles/Statuses ###

The Ruby version of Resque has a nifty feature whereby the process
title of the worker is updated to indicate what the worker is doing,
and any forked children also set their process title with the job
being run. This helps identify running processes on the server and
their resque status.

**PHP does not have this functionality by default.**

A PECL module (<http://pecl.php.net/package/proctitle>) exists that
adds this funcitonality to PHP, so if you'd like process titles updated,
install the PECL module as well. php-resque will detect and use it.

### Errors and troubleshooting ###

**php.ini settings**

If you don't have E in variables_order you can specify @-c ./@ as an 
option to the PHP cli program. This reverts values to the defaults which 
include E.

    $ QUEUE=file_serve php -c ./ resque.php

