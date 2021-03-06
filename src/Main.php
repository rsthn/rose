<?php
/*
**	Rose Framework Initializer
**
**	Copyright (c) 2010-2021, RedStar Technologies, All rights reserved.
**	https://rsthn.com/
**
**	THIS LIBRARY IS PROVIDED BY REDSTAR TECHNOLOGIES "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
**	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A 
**	PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL REDSTAR TECHNOLOGIES BE LIABLE FOR ANY
**	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
**	NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; 
**	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
**	STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
**	USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace Rose;

use Rose\Errors\Error;
use Rose\Errors\FalseError;

use Rose\Configuration;
use Rose\Map;
use Rose\DateTime;
use Rose\Gateway;
use Rose\Text;
use Rose\IO\Path;
use Rose\IO\Directory;

/*
**	Prints a tracing message into the log file.
*/
function trace ($string, $out=null)
{
	static $paths = null;

	if (!$out) $out = 'logs/system.log';

	if (!$paths)
		$paths = new Map();

	$path = Path::dirname($out);
	if (!$paths->has($path))
	{
		if (!Path::exists($path))
			Directory::create($path, true);

		$paths->set($path, true);
	}

	$fp = @fopen ($out, 'a+t');
	if (!$fp) return;

	fwrite ($fp, $string);
	fwrite ($fp, "\n");

	fclose ($fp);
}

/*
**	Returns the class name of the given object.
*/
function typeOf ($object, $detailed=false)
{
	if (is_object ($object))
	{
		if ($object instanceof \Closure)
			return 'function';

		return get_class($object);
	}

	if ($detailed)
	{
		if (is_null($object))
			return 'null';

		if (is_string($object))
			return 'string';

		if (is_bool($object))
			return 'bool';

		if (is_array($object))
			return 'array';

		if (is_int($object))
			return 'int';

		if (is_numeric($object))
			return 'number';

		if (is_callable($object))
			return 'function';
	}

	return 'primitive';
}

/*
**	Returns true if the object is an string.
*/
function isString ($object)
{
	return is_string ($object);
}

/*
**	Returns the boolean value of the given argument.
*/
function bool ($value)
{
	return $value === 'true' || ($value !== 'false' && !!$value);
}

/*
**	Raises a warning.
*/
function raiseWarning ($message)
{
	trigger_error ($message, E_USER_WARNING);
}

/*
**	Raises an error.
*/
function raiseError ($message)
{
	trigger_error ($message, E_USER_ERROR);
}

/*
**	Returns true if the object is an instance of the given class.
*/
function isSubTypeOf ($object, $className)
{
	return $object instanceof $className;
}

/*
**	Returns the current time in milliseconds.
*/
function mstime ()
{
	$t = explode (' ', microtime ());
	return (int) (($t[0] + $t[1]) * 1000);
}

/*
**	Global error handler.
*/
function error_handler ($errno, $error, $file, $line)
{
	throw new Error ($error . sprintf (' (%s %u)', $file, $line));
}

/*
**	Fatal error handler.
*/
function fatal_handler()
{
	global $lastException;

    $error = error_get_last();

	if ($error != null && ($error['type'] == E_NOTICE))
		$error = null;

	if ($error == null && $lastException == null)
		return;

	ob_end_clean();

	echo '<html>';
	echo '<body style="background: #070710; padding: 24px;">';
	echo '<pre style="font-size: 12px; line-height: 1em; color: #fff; background: #070710; padding: 16px 24px;">';

	if ($error != null)
	{
		echo '<div style="padding: 2px; color: #0cf; font-size: 1.3em;">' . sprintf('Fatal Error %04u', $error['type']) . '</div>';
		echo '<div style="padding: 2px; color: #ccc;">' . basename($error['file']) . ':' . $error['line'] . '</div>';

		$msg = trim(Regex::_getString('/.*?:(.+) in/', $error['message'], 1));
		if ($msg)
		{
			echo '<div style="color: #fa0; margin-top: 16px; margin-bottom: 16px; padding: 2px; font-weight: normal; font-size: 1.3em; line-height: 1.25em; white-space: normal;">' . $msg . '</div>';

			$a = Regex::_matchAll('/#.+? (.+?)\(([0-9]+)\): ([^:(-]+)(->|::)?(.*)\(/', $msg, true);
			$n = $a->shift()->length;

			$stackTrace = array();

			for ($i = 0; $i < $n; $i++)
			{
				if ($a->get(3)->get($i) == '')
					$stackTrace[] = ['file' => $a->get(0)->get($i), 'line' => $a->get(1)->get($i), 'function' => $a->get(2)->get($i)];
				else
					$stackTrace[] = ['file' => $a->get(0)->get($i), 'line' => $a->get(1)->get($i), 'class' => $a->get(2)->get($i), 'function' => $a->get(4)->get($i)];
			}
		}
		else
			echo '<div style="color: #fa0; margin-top: 16px; margin-bottom: 16px; padding: 2px; font-weight: normal; font-size: 1.3em; line-height: 1.25em; white-space: normal;">' . $error['message'] . '</div>';
	}

	if ($lastException != null)
	{
		echo '<div style="padding: 2px; color: #0cf; font-size: 1.3em;">' . typeOf($lastException) . '</div>';
		echo '<div style="padding: 2px; color: #ccc;">' . basename($lastException->getFile()) . ':' . $lastException->getLine() . '</div>';

		echo '<div style="color: #fa0; margin-top: 16px; margin-bottom: 16px; padding: 2px; font-weight: normal; font-size: 1.3em; line-height: 1.25em; white-space: normal;">' . $lastException->getMessage() . '</div>';

		$stackTrace = $lastException->getTrace();
	}

	if ($stackTrace != null)
	{
		foreach ($stackTrace as $err)
		{
			echo '<div style="margin-top: 4px; padding: 2px; color: #0c7;">';

			if (isset($err['class']))
				echo '<span style="color: #c0f;">(' . $err['class'] . ') </span>';

			echo '<b>'.$err['function'].'</b>';

			echo ' (';
			if (isset($err['args']))
			{
				for ($i = 0; $i < count($err['args']); $i++)
				{
					echo typeOf($err['args'][$i], true);

					if ($i != count($err['args']) - 1)
						echo ', ';
				}
			}
			echo ')';

			if (isset($err['file']))
			{
				echo '<span style="color: #ccc;">';
				echo ' ' . basename($err['file']) . ':' . $err['line'] . ' ';
				echo '</span>';
			}

			echo '</div>';
		}
	}

	echo '<div style="color: #676770; margin-top: 24px;">';
	echo '@rsthn/rose ' . json_decode(file_get_contents(dirname(__FILE__).'/../composer.json'))->version;
	echo '</div>';

	echo '</pre>';
	echo '</body>';
	echo '</html>';
}

/*
**	Framework entry point.
*/
class Main
{
	/*
	**	Project's core directory.
	*/
	public static $CORE_DIR;

	/*
	**	Constant that indicates if the framework has been loaded and initialized. Used as a dummy var to force autoload of the Main class by using `Main::$loaded`.
	*/
	public static $loaded = true;

	/*
	**	Sets the global definitions and PHP configuration. Automatically called when loading this class.
	*/
	static function defs ()
	{
		// Configure PHP environment.
		gc_disable();
		ignore_user_abort(false);
		umask(0);
		error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED);
		mt_srand (((double)microtime ()) * 10000);
		set_time_limit (300);

		// Set global error handlers and disable PHP error output.
		set_error_handler ('Rose\\error_handler', E_STRICT | E_WARNING | E_USER_ERROR | E_USER_WARNING);
		register_shutdown_function ('Rose\\fatal_handler');
		ini_set ('display_errors', '0');

		// Set global project core directory (use 'resources' for legacy systems, and 'rcore' for Rose 3.1+ systems).
		if (file_exists('resources/'))
			self::$CORE_DIR = 'resources';
		else
			self::$CORE_DIR = 'rcore';
	}

	/*
	**	Initializes the primary framework classes for CLI operation.
	*/
	static function cli ()
	{
		try {
			Gateway::getInstance()->init();
		}
		catch (\Exception $e)
		{
			$lastException = $e;
		}

		register_shutdown_function (function() {
			Gateway::getInstance()->close();
		});
	}

	/*
	**	Initializes the primary framework classes and passes control to the Gateway. If $callback is not null, it will be executed
	**	after Gateway's main().
	*/
	static function initialize ($callback=null)
	{
		// Load specially encoded request if "req64" parameter is set.
		if (isset($_REQUEST['req64']))
		{
			parse_str (base64_decode ($_REQUEST['req64']), $tmp);
			$_REQUEST = array_merge($_REQUEST, $tmp);
		}

		$ms_start = mstime ();

		global $lastException;
		$lastException = null;

		try
		{
			Gateway::getInstance()->init();
	
			try {
				Gateway::getInstance()->main();
			}
			catch (FalseError $e) {
			}

			if ($callback != null)
				$callback();

			Gateway::getInstance()->close();
		}
		catch (\Exception $e)
		{
			$lastException = $e;
		}

		$ms_end = mstime();

		if (Configuration::getInstance()->Gateway->access_log == 'true')
			trace (sprintf ('%s   %7.2f MB   %6d ms   %s   %s', (string)(new DateTime()), memory_get_peak_usage()/1048576, $ms_end-$ms_start, $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI']), 'logs/access.log');
	}
};

Main::defs();
