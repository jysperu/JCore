<?php
class APP implements ArrayAccess
{
	public static function instance ()
	{
		static $instance, $inited = false;
		isset($instance) or $instance = new self();
		$inited or $inited = $instance->init();
		return $instance;
	}

	//===================================================================
	// Variables
	//===================================================================
	
	/**
	 * Variable para almacenar todas las variables usables por la clase
	 * @protected
	 */
	protected $variables = [];
	
	/**
	 * Ejecutado cuando se actualiza la variable charset
	 * @return void
	 */
	protected function _charset_updated()
	{
		$charset =& $this->variables['charset'];
		
		## Convirtiendolo a mayúsculas
		$charset = mb_strtoupper($charset);
		
		## Estableciendo los charsets a todo lo que corresponde
		ini_set('default_charset', $charset);
		ini_set('php.internal_encoding', $charset);
		
		@ini_set('mbstring.internal_encoding', $charset);
		mb_substitute_character('none');
		
		@ini_set('iconv.internal_encoding', $charset);
	}
	
	/**
	 * Ejecutado cuando se actualiza la variable timezone
	 * @return void
	 */
	protected function _timezone_updated()
	{
		$timezone =& $this->variables['timezone'];
		
		## Estableciendo los charsets a todo lo que corresponde
		date_default_timezone_set($timezone);
		
		global $CONs;
		
		if (is_empty($CONs))
		{
			return;
		}
		
		foreach($CONs as $conection)
		{
			@mysqli_query($conection, 'SET time_zone = ' . qp_esc(getUTC()));
		}
	}
	
	public function init()
	{
		/**
		 * Obteniendo la codificación de caracteres
		 */
		$this->variables['charset'] =& config('charset');
		$this->_charset_updated();
		
		/**
		 * UTF8_ENABLED
		 *
		 * Variable que permite conocer si la codificación UTF8 está habilitado
		 *
		 * @global
		 */
		define('UTF8_ENABLED', defined('PREG_BAD_UTF8_ERROR') && APP()->charset === 'UTF-8');
		
		/**
		 * Obteniendo la zona horaria
		 */
		$this->variables['timezone'] =& config('timezone');
		$this->_timezone_updated();

		/**
		 * Conectar Primera Base Datos
		 */
		sql_start();

		global $CON;
		if ($CON)
		{
			foreach(['init', 'prepare', 'loaded'] as $kwrd)
			{
				foreach(['', '_' . $this->http_verb] as $hvrb)
				{
					$_stop = action_apply('db_' . $kwrd . $hvrb, $this);
					if ($_stop)
					{
						break 2;
					}
				}
			}
		}

		$this->Router = Router::instance();

		/**
		 * Identificando los Métodos de Request autorizados
		 */
		$allowed_http_methods = (array)config('http_methods_allowed');
		
		$allowed_http_methods = array_map('mb_strtoupper', $allowed_http_methods); ## Convirtiendo todos a mayúsculas

		Router::$http_methods = array_merge(Router::$http_methods, $allowed_http_methods); ## Agregando posibles faltantes
		Router::$http_methods = array_unique(Router::$http_methods); ## Eliminando duplicados

		in_array(url('request_method'), $allowed_http_methods) or
		RSP()
		-> error('HTTP Method `' . $method . '` not allowed')
		-> http_code(405, 'HTTP Method `' . $method . '` not allowed')
		-> exit()
		;

		foreach(['init', 'prepare', 'loaded'] as $kwrd)
		{
			foreach(['', '_' . $this->http_verb] as $hvrb)
			{
				$_stop = action_apply('app_' . $kwrd . $hvrb, $this);
				if ($_stop)
				{
					break 2;
				}
			}
		}
	}
}
