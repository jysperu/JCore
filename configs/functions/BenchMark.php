<?php
/**
 * BenchMark.php
 * Funciones BranchTimer
 *
 * @filesource
 */

defined('EXECTIMESTART') or define('EXECTIMESTART', microtime(TRUE));

if ( ! class_exists('BenchMark')):

class BenchMark implements ArrayAccess, Iterator
{
	//-------------------------------------------
	// Statics
	//-------------------------------------------
	static function &instance()
	{
		static $instance;

		isset($instance) or $instance = new self();

		return $instance;
	}

	//-------------------------------------------
	// Variables
	//-------------------------------------------
	protected $points = [];
	private $position = 0;

	//-------------------------------------------
	// Constructor
	//-------------------------------------------
	protected function __construct()
	{
		$this->position = 0;
		$this->points['total_execution_time_start'] = EXECTIMESTART;

		defined('BMK_loaded') or
		define('BMK_loaded', TRUE);
	}

	//-------------------------------------------
	// Funciones
	//-------------------------------------------
	public function mark ($key)
	{
		$this->points[$key] = microtime(TRUE);
		
		function_exists('action_apply') and
		action_apply($key, microtime(TRUE));
	}

	public function between ($first = NULL, $second = NULL, $decimals = 4)
	{
		if (is_null($first))
		{
			return '{elapsed_time}';
		}

		if ( ! isset($this->points[$first]))
		{
			return '';
		}

		$first_time = $this->points[$first];

		if ( ! is_null($second) and ! isset($this->points[$second]))
		{
			$this->points[$second] = microtime(TRUE);
			$second_time = $this->points[$second];
		}
		elseif ( ! is_null($second))
		{
			$second_time = $this->points[$second];
		}
		else
		{
			$second_time = microtime(TRUE);
		}

		if ($second_time > $first_time)
		{
			$t = $second_time;
			$second_time = $first_time;
			$first_time = $t;
			unset($t);
		}

		return number_format($first_time - $second_time, $decimals);
	}

	//-------------------------------------------
	// Array Access
	//-------------------------------------------
	public function offsetExists ($offset)
	{
		return isset($this->points[$offset]);
	}

	public function offsetGet ($offset)
	{
		return $this->points[$offset];
	}

	public function offsetSet ($offset, $value)
	{
		$this->points[$offset] = $value;
	}

	public function offsetUnset ($offset)
	{
		unset ($this->points[$offset]);
	}

	//-------------------------------------------
	// Iterator
	//-------------------------------------------
	public function rewind() 
	{
		$this->position = 0;
	}

	public function current() 
	{
		$keys = array_keys($this->points);
		return $keys[$this->position];
	}

	public function key() 
	{
		return $this->position;
	}

	public function next() 
	{
		++$this->position;
	}

	public function valid() 
	{
		$keys = array_keys($this->points);
		return isset($keys[$this->position]);
	}
}

endif;

if ( ! function_exists('mark'))
{
	/**
	 * mark()
	 * Función que utiliza la clase BenchMark
	 *
	 * @return void
	 */
	function mark($key)
	{
		BenchMark::instance()
		-> mark($key)
		;
	}
}