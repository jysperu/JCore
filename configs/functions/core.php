<?php
/**
 * core.php
 * Funciones para eventos de codigo
 *
 * @filesource
 */

/**
 * DIRECTORY_SEPARATOR
 *
 * Separador de Directorios para el sistema operativo de ejecución
 *
 * @global
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * ENVIRONMENT - AMBIENTE DE DESARROLLO
 *
 * Permite manejar distintas configuraciones dependientemente de 
 * la etapa o fase en la que se encuentre la aplicación (proyecto)
 *
 * **Posibles valores:**
 * *	desarrollo
 * *	pruebas
 * *	produccion
 *
 * @global
 */
defined('ENVIRONMENT') or define('ENVIRONMENT', 'produccion');

/**
 * DIRECTORIOS BASES
 *
 * Array de los directorios base que buscará las estructuras de archivos
 *
 * @internal
 */
isset($BASES_path) or $BASES_path = [];

/**
 * $JC_filters
 * Variable que almacena todas las funciones aplicables para los filtros
 * @internal
 */
$JC_filters = [];

/**
 * $JC_filters_defs
 * Variable que almacena todas las funciones aplicables para los filtros 
 * por defecto cuando no se hayan asignado alguno
 * @internal
 */
$JC_filters_defs = [];

/**
 * $JC_actions
 * Variable que almacena todas las funciones aplicables para los actions
 * @internal
 */
$JC_actions = [];

/**
 * $JC_actions_defs
 * Variable que almacena todas las funciones aplicables para los actions
 * por defecto cuando no se hayan asignado alguno
 * @internal
 */
$JC_actions_defs = [];

if ( ! function_exists('is_cli'))
{
	/**
	 * is_cli()
	 * Identifica si el REQUEST ha sido hecho desde comando de linea
	 *
	 * @return bool
	 */
	function is_cli()
	{
		return (PHP_SAPI === 'cli' OR defined('STDIN'));
	}
}

if ( ! function_exists('is_localhost'))
{
	/**
	 * is_localhost()
	 * Identificar si la aplicación está corriendo en modo local
	 *
	 * Se puede cambiar el valor durante la ejecución
	 *
	 * @param bool|NULL $set Si es Bool entonces asigna al valor mediante ejecución
	 * @return bool
	 */
	function &is_localhost(bool $set = NULL)
	{
		static $is_localhost = []; ## No puede ser referenciado si es BOOL

		count($is_localhost) === 0 and
		$is_localhost[0] = (bool)preg_match('/^(192\.168\.[0-9]{1,3}\.[0-9]{1,3}|127\.[0]{1,3}\.[0]{1,3}\.[0]{0,2}1)$/', $_SERVER['SERVER_ADDR']);

		is_bool($set) and 
		$is_localhost[0] = $set;

		return $is_localhost[0];
	}
}

if ( ! function_exists('is_php'))
{
	/**
	 * is_php()
	 * Determina si la versión de PHP es igual o mayor que el parametro
	 *
	 * @param string $version Versión a validar
	 * @return bool TRUE si la versión actual es $version o mayor
	 */
	function is_php(string $version)
	{
		static $_is_php = [];
		isset ($_is_php[$version]) or $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
		return $_is_php[$version];
	}
}

if ( ! function_exists('display_errors'))
{
	/**
	 * display_errors()
	 * Identificar si la aplicación debe mostrar los errores o los logs
	 * 
	 * Se puede cambiar el valor durante la ejecución
	 *
	 * @param bool|NULL $set Si es Bool entonces asigna al valor mediante ejecución
	 * @return bool
	 */
	function &display_errors(bool $set = NULL)
	{
		static $display_errors = []; ## No puede ser referenciado si es BOOL
		
		count($display_errors) === 0 and
			$display_errors[0] = (bool)str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', @ini_get('display_errors'));
		
		is_bool($set) and 
			$display_errors[0] = $set;
		
		return $display_errors[0];
	}
}

if ( ! function_exists('protect_server_dirs'))
{
	/**
	 * protect_server_dirs()
	 * Proteje los directorios base y los reemplaza por vacío o un parametro indicado
	 *
	 * @since 1.1 Se cambio la carga de directorios en la variable $_dirs a los de la variable $BASES_path
	 * @since 1.0
	 *
	 * @param string $str Contenido que probablemente contiene rutas a proteger
	 * @return string
	 */
	function protect_server_dirs(string $str)
	{
		static $_dirs = [];

		global $BASES_path;

		$add_basespath = count($_dirs) === 0;

		defined('ROOTPATH') and ! isset($_dirs[ROOTPATH]) and $_dirs[ROOTPATH] = DS . 'ROOTPATH';
		defined('APPPATH')  and ! isset($_dirs[APPPATH])  and $_dirs[APPPATH]  = DS . 'APPPATH';
		defined('ABSPATH')  and ! isset($_dirs[ABSPATH])  and $_dirs[ABSPATH]  = DS . 'ABSPATH';
		defined('HOMEPATH') and ! isset($_dirs[HOMEPATH]) and $_dirs[HOMEPATH] = DS . 'HOMEPATH';

		$add_basespath and 
		$_dirs = array_merge(array_combine($BASES_path, array_map(function($path){
			return DS . basename($path);
		}, $BASES_path)), $_dirs);

		return strtr($str, $_dirs);
	}
}

if ( ! function_exists('config'))
{
	/**
	 * config()
	 *
	 * Obtiene y retorna la configuración.
	 *
	 * La función lee los archivos de configuración generales tales como los de JCore 
	 * y los que se encuentran en la carpeta 'config' de APPPATH (directorio de la aplicación)
	 *
	 * @param	string 	$get		permite obtener una configuración específica, 
	 * 								si es NULL entonces devolverá toda la configuración.
	 * @param	array 	$replace	reemplaza algunas opciones de la variable $config leida
	 * @param	boolean	$force		si es FALSE, entonces validará que el valor a "reemplazar"
	 *								no exista previamente (solo inserta no reemplaza)
	 * @return	mixed
	 */
	function &config($get = NULL, Array $replace = [], bool $force = FALSE)
	{
		static $config = [];

		if (count($config) === 0)
		{
			$_files = load_file ('configs/config', FALSE, TRUE, TRUE);
			foreach($_files as $_file)
			{
				require_once $_file;
			}

			$_files = load_file ('configs/' .ENVIRONMENT. '/config', FALSE, TRUE, TRUE);
			foreach($_files as $_file)
			{
				require_once $_file;
			}
		}

		foreach ($replace as $key => $val)
		{
			if ( ! $force and isset($config[$key]))
			{
				continue;
			}

			$config[$key] = $val;
		}

		if ($get === 'array' or is_null($get))
		{
			return $config;
		}

		isset($config[$get]) or 
		$config[$get] = NULL;

		return $config[$get];
	}
}

if ( ! function_exists('filter_add'))
{
	/**
	 * filter_add()
	 * Agrega funciones programadas para filtrar variables
	 *
	 * @param string	$key		Hook
	 * @param callable	$function	Función a ejecutar
	 * @param int		$priority	Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return bool
	 */
	function filter_add ($key, $function, $priority = 50)
	{
		global $JC_filters;
		
		$lista =& $JC_filters;
		
		if (empty($key))
		{
			return FALSE;
		}
		
		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;
		
		$lista[$key][$priority][] = $function;
		return TRUE;
	}
}

if ( ! function_exists('non_filtered'))
{
	/**
	 * non_filtered()
	 * Agrega funciones programadas para filtrar variables
 	 * por defecto cuando no se hayan asignado alguno
	 *
	 * @param string	$key		Hook
	 * @param callable	$function	Función a ejecutar
	 * @param int		$priority	Prioridad (Orden) a ejecutar la función cuando es llamado el Hook
	 * @return bool
	 */
	function non_filtered ($key, $function, $priority = 50)
	{
		global $JC_filters_defs;
		
		$lista =& $JC_filters_defs;
		
		if (empty($key))
		{
			return FALSE;
		}
		
		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;
		
		$lista[$key][$priority][] = $function;
		return TRUE;
	}
}

if ( ! function_exists('filter_apply'))
{
	/**
	 * filter_apply()
	 * Ejecuta funciones para validar o cambiar una variable
	 *
	 * @since 0.2 Se ha agregado las funciones por defecto cuando
	 * @since 0.1
	 *
	 * @param	string	$key	Hook
	 * @param	mixed	&...$params	Parametros a enviar en las funciones del Hook (Referenced)
	 * @return	mixed	$params[0] || NULL
	 */
	function filter_apply ($key, &...$params)
	{
		global $JC_filters;
		$lista =& $JC_filters;
		
		if (empty($key))
		{
			throw new Exception ('Hook es requerido');
		}
		
		count($params) === 0 and $params[0] = NULL;
		
		if ( ! isset($lista[$key]) OR count($lista[$key]) === 0)
		{
			global $JC_filters_defs;

			$lista_defs =& $JC_filters_defs;

			if ( ! isset($lista_defs[$key]) OR count($lista_defs[$key]) === 0)
			{
				return $params[0];
			}

			$functions = $lista_defs[$key];
		}
		else
		{
			$functions = $lista[$key];
		}
		
		krsort($functions);
		
		$params_0 = $params[0]; ## Valor a retornar
		foreach($functions as $priority => $funcs){
			foreach($funcs as $func){
				$return = call_user_func_array($func, $params);
				
				if ( ! is_null($return) and $params_0 === $params[0])
				{
					## El parametro 0 no ha cambiado por referencia 
					## y en cambio la función ha retornado un valor no NULO 
					## por lo tanto le asigna el valor retornado
					$params[0] = $return;
				}
				
				$params_0 = $params[0]; ## Valor a retornar
			}
		}
		
		return $params_0;
	}
}

if ( ! function_exists('action_add'))
{
	/**
	 * action_add()
	 * Agrega funciones programadas
	 *
	 * @param string	$key		Hook
	 * @param callable	$function	Función a ejecutar
	 * @param int		$priority	Prioridad (orden) a ejecutar la función
	 * @return bool
	 */
	function action_add ($key, $function, $priority = 50)
	{
		global $JC_actions;
		
		$lista =& $JC_actions;
		
		if (empty($key))
		{
			return FALSE;
		}
		
		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;
		
		$lista[$key][$priority][] = $function;
		return TRUE;
	}
}

if ( ! function_exists('non_actioned'))
{
	/**
	 * non_actioned()
	 * Agrega funciones programadas
 	 * por defecto cuando no se hayan asignado alguno
	 *
	 * @param string	$key		Hook
	 * @param callable	$function	Función a ejecutar
	 * @param int		$priority	Prioridad (orden) a ejecutar la función
	 * @return bool
	 */
	function non_actioned ($key, $function, $priority = 50)
	{
		global $JC_actions_defs;
		
		$lista =& $JC_actions_defs;
		
		if (empty($key))
		{
			return FALSE;
		}
		
		is_numeric($priority) OR $priority = 50;
		$priority = (int)$priority;
		
		$lista[$key][$priority][] = $function;
		return TRUE;
	}
}

if ( ! function_exists('action_apply'))
{
	/**
	 * action_apply()
	 * Ejecuta las funciones programadas
	 *
	 * @since 0.2.2 Se ha agregado las funciones por defecto cuando
	 * @since 0.2.1 Se ha cambiado el $RESULT por defecto de FALSE a NULL
	 * @since 0.1
	 *
	 * @param string	$key	Hook
	 * @param	mixed	&...$params	Parametros a enviar en las funciones del Hook (Referenced)
	 * @return bool
	 */
	function action_apply ($key, ...$params)
	{
		global $JC_actions;
		$lista =& $JC_actions;
		
		empty($key) and user_error('Hook es requerido');
		
		$RESULT = NULL;
		
		if ( ! isset($lista[$key]) OR count($lista[$key]) === 0)
		{
			global $JC_actions_defs;

			$lista_defs =& $JC_actions_defs;

			if ( ! isset($lista_defs[$key]) OR count($lista_defs[$key]) === 0)
			{
				return $RESULT;
			}

			$functions = $lista_defs[$key];
		}
		else
		{
			$functions = $lista[$key];
		}
		
		krsort($functions);
		
		foreach($functions as $priority => $funcs){
			foreach($funcs as $func){
				$RESULT = call_user_func_array($func, $params);
			}
		}
		
		return $RESULT;
	}
}

if ( ! function_exists('logger'))
{
	/**
	 * logger()
	 * Función que guarda los logs
	 *
	 * @param BasicException|Exception|TypeError|Error|string 	$message	El mensaje reportado
	 * @param int|null 		$code		(Optional) El código del error
	 * @param string|null	$severity	(Optional) La severidad del error
	 * @param array|null 	$meta		(Optional) Los metas del error
	 * @param string|null 	$filepath	(Optional) El archivo donde se produjo el error
	 * @param int|null 		$line		(Optional) La linea del archivo donde se produjo el error
	 * @param array|null 	$trace		(Optional) La ruta que tomó la ejecución hasta llegar al error
	 * @return void
	 */
	function logger ($message, $code = NULL, $severity = NULL, $meta = NULL, $filepath = NULL, $line = NULL, $trace = NULL, $show = TRUE)
	{
		static $_count = 0;
		$_count ++;
		
		$_count > 10 and
		exit('<br /><b>Fatal Error:</b> Se han producido demasiados errores de manera continua.');
		
		/**
		 * Listado de Levels de Errores
		 * @static
		 * @global
		 */
		static $error_levels = 
		[
			E_ERROR			    =>	'Error',				
			E_WARNING		    =>	'Warning',				
			E_PARSE			    =>	'Parsing Error',		
			E_NOTICE		    =>	'Notice',				

			E_CORE_ERROR		=>	'Core Error',		
			E_CORE_WARNING		=>	'Core Warning',		

			E_COMPILE_ERROR		=>	'Compile Error',	
			E_COMPILE_WARNING	=>	'Compile Warning',	

			E_USER_ERROR		=>	'User Error',		
			E_USER_DEPRECATED	=>	'User Deprecated',	
			E_USER_WARNING		=>	'User Warning',		
			E_USER_NOTICE		=>	'User Notice',		

			E_STRICT		    =>	'Runtime Notice'		
		];
		
		// Reordenamiento de parametros enviados
		is_bool ($filepath) and $show = $filepath and $filepath = NULL;
		is_bool ($code)     and $show = $code and $code = NULL;
		is_array($severity) and is_null($meta) and $meta = $severity and $severity = NULL;
		is_null ($code)     and $code = 0;
		is_null ($meta)     and $meta = [];
		is_array($meta)     or  $meta = (array)$meta;
		
		// Datos de FechaHora
		$meta['timestamp'] = [
			'time' => time(),
			'microtime' => microtime(),
			'microtimeF' => microtime(true),
			'datetime' => date('Y-m-d H:i:s'),
			'fecha' => date('Y-m-d'),
			'hora' => date('H:i:s'),
		];
		
		function_exists('date2') and
		$meta['timestamp']['fechaLL'] = date2('LL');
		
		defined('APP_loaded') and
		$meta['APP_loaded'] = APP_loaded;
		
		defined('RQS_loaded') and
		$meta['RQS_loaded'] = RQS_loaded;
		
		defined('RSP_loaded') and
		$meta['RSP_loaded'] = RSP_loaded;
		
		if (defined('BMK_loaded'))
		{
			$meta['BMK_loaded'] = BMK_loaded;
			$meta['BMK_totaltime'] = BenchMark::instance() -> between('total_execution_time_start');
		}
		
		if (defined('OPB_loaded'))
		{
			$meta['OPB_loaded'] = OPB_loaded;
			$meta['OPB_content'] = OutputBuffering::instance() -> stop() -> getContents();
		}
		
		$SER = [];
		foreach($_SERVER as $x => $y)
		{
			if (preg_match('/^((GATEWAY|HTTP|QUERY|REMOTE|REQUEST|SCRIPT|CONTENT)\_|REDIRECT_URL|REDIRECT_STATUS|PHP_SELF|SERVER\_(ADDR|NAME|PORT|PROTOCOL))/i', $x))
			{
				$SER[$x] = $y;
			}
		}
		
		$meta['server'] = $SER;
		
		defined('disp') and
		$meta['disp'] =  disp;
		
		isset($_SESSION['stat']) and
		$meta['stat'] = $_SESSION['stat'];

		// URL info
		try
		{
			$meta['url'] = url('array');
		}
		catch (\BasicException $e){}
		catch (\Exception $e){}
		catch (\TypeError $e){}
		catch (\Error $e){}
		
		// IP info
		try
		{
			$meta['ip_address'] = ip_address('array');
		}
		catch (\BasicException $e){}
		catch (\Exception $e){}
		catch (\TypeError $e){}
		catch (\Error $e){}
		
		// REQUEST info
		try
		{
			$meta['request'] = request('array');
		}
		catch (\BasicException $e){}
		catch (\Exception $e){}
		catch (\TypeError $e){}
		catch (\Error $e){}

		// Reinformación de la data
		if ($message instanceof BasicException)
		{
			$exception = $message;
			
			$meta = array_merge($exception->getMeta(), $meta);
			is_null($severity) and $severity = 'BasicException';
			
			$meta['class'] = get_class($exception);
		}
		elseif ($message instanceof Exception)
		{
			$exception = $message;
			
			is_null($severity) and $severity = 'Exception';
			
			$meta['class'] = get_class($exception);
		}
		elseif ($message instanceof TypeError)
		{
			$exception = $message;
			
			is_null($severity) and $severity = 'Error';
			
			$meta['class'] = get_class($exception);
		}
		elseif ($message instanceof Error)
		{
			$exception = $message;
			
			is_null($severity) and $severity = 'Error';
			
			$meta['class'] = get_class($exception);
		}
		
		if (isset($exception))
		{
			$message  = $exception->getMessage();
			
			is_null($filepath) and $filepath = $exception->getFile();
			is_null($line)     and $line     = $exception->getLine();
			is_null($trace)    and $trace    = $exception->getTrace();
			
			$code == 0         and $code     = $exception->getCode();
		}

		is_null($severity) and $severity = E_USER_NOTICE;
		
		$severity = isset($error_levels[$severity]) ? $error_levels[$severity] : $severity;
		
		is_null($message) and $message = '[NULL]';
		
		if ($message === 'Only variable references should be returned by reference' and $code === E_NOTICE)
		{
			// Mensaje muy fastidioso
			return;
		}
		
		// Detectar la ruta del error
		if (is_null($trace))
		{
			$trace = debug_backtrace(false);
			
			if ($trace[0]['function'] === __FUNCTION__)
			{
				array_shift($trace);
			}
			
			if (in_array($trace[0]['function'], ['_exception_handler', '_error_handler']))
			{
				array_shift($trace);
			}
		}
		
		if (isset($trace[0]))
		{
			is_null($filepath) and $filepath = $trace[0]['file'];
			is_null($line) and $line = $trace[0]['line'];

			isset($trace[0]['class']) and ! isset($meta['class']) and $meta['class'] = $trace[0]['class'];
			isset($trace[0]['function']) and ! isset($meta['function']) and $meta['function'] = $trace[0]['function'];
		}



		echo '<h1>ERROR:</h1><pre>';
		print_r([
			$message, 
			$code, 
			$severity, 
			$meta, 
			$filepath, 
			$line, 
			$trace, 
			$show
		]);
		die();
	}
}

if ( ! function_exists('print_array'))
{
	/**
	 * print_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML
	 *
	 * @use display_errors
	 * @use is_localhost
	 * @use logger
	 * @use protect_server_dirs
	 *
	 * @param	...array
	 * @return	void
	 */
	function print_array(...$array)
	{
		if (function_exists('display_errors') and function_exists('is_localhost') and function_exists('logger') and 
			! display_errors() and ! is_localhost())
		{
			logger('Está mostrando información de Desarrollador con la opción `display_errors` desactivada', FALSE);
		}

		$r = '';

		$trace = debug_backtrace(false);
		if (isset($trace[0]) && isset($trace[0]['file']) && $trace[0]['file'] === __FILE__)
		{
			array_shift($trace);
		}

		$file_line = '';
		if (isset($trace[0]))
		{
			$file_line = $trace[0]['file'] . ' #' . $trace[0]['line'];
			
			function_exists('protect_server_dirs') and
			$file_line = protect_server_dirs($file_line);
			
			$file_line = '<small style="color: #ccc;display: block;margin: 0;">' . $file_line . '</small><br>';
		}

		if (count($array) === 0)
		{
			$r.= '<small style="color: #888">[SIN PARAMETROS]</small>';
		}
		else
		foreach ($array as $ind => $_arr)
		{
			if (is_null($_arr))
			{
				$_arr = '<small style="color: #888">[NULO]</small>';
			}
			elseif (is_string($_arr) and empty($_arr))
			{
				$_arr = '<small style="color: #888">[VACÍO]</small>';
			}
			elseif (is_bool($_arr))
			{
				$_arr = '<small style="color: #888">['.($_arr?'TRUE':'FALSE').']</small>';
			}
			elseif (is_array($_arr) and function_exists('array_html'))
			{
				$_arr = array_html($_arr);
			}
			else
			{
				$_arr = htmlentities(print_r($_arr, true));
			}
			
			$r.= ($ind > 0 ? '<hr style="border: none;border-top: dashed #ebebeb '.($ind % 2 === 0 ? '1' : '').'.5px;margin: 12px 0;">' : '') . $_arr;
		}

		echo '<pre style="display: block;text-align: left;color: #444;background: white;position: relative;z-index: 99999999999;margin: 5px 5px 15px;padding: 0px 10px 10px;border: solid 1px #ebebeb;box-shadow: 4px 4px 4px rgba(235, 235, 235, .5);">' . $file_line . $r . '</pre>' . PHP_EOL;
	}
}

if ( ! function_exists('die_array'))
{
	/**
	 * die_array()
	 * Muestra los contenidos enviados en el parametro para mostrarlos en HTML y finaliza los segmentos
	 *
	 * @use print_array
	 *
	 * @param	...array
	 * @return	void
	 */
	function die_array(...$array)
	{
		call_user_func_array('print_array', $array);
		die();
	}
}

if ( ! function_exists('_error_handler'))
{
	/**
	 * _error_handler()
	 * Función a ejecutar al producirse un error durante la aplicación
	 *
	 * @use logger
	 * @use is_cli
	 *
	 * @param string
	 * @param string
	 * @param string
	 * @param int
	 *
	 * @return	void
	 */
	function _error_handler($severity, $message, $filepath, $line)
	{
		// Se valida si es error o solo una alerta
		$_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

		if ($_error and ! is_cli())
		{
			// Ya que es un error, se retorna un status 500 Internal Server Error
			http_response_code(500);
		}

		if (($severity & error_reporting()) !== $severity)
		{
			// No se desea reportar
			return;
		}

		// Se envía los datos a una función especial llamada logger definida por el usuario
		logger($message, 
			   $severity, 
			   $severity, 
			   [], 
			   $filepath, 
			   $line);

		if ($_error)
		{
			// Ya que es un error, finaliza el proceso
			exit(1);
		}
	}
}

if ( ! function_exists('_exception_handler'))
{
	/**
	 * _exception_handler()
	 * Función a ejecutar cuando se produzca una exception
	 *
	 * @use logger
	 * @use is_cli
	 *
	 * @param	Exception	$exception
	 *
	 * @return	void
	 */
	function _exception_handler($exception)
	{
		// Ya que es una exception, se retorna un status 500 Internal Server Error
		if ( ! is_cli())
		{
			http_response_code(500);
		}
		
		// Se envía los datos a una función especial llamada logger definida por el usuario
		logger($exception);

		// Ya que es una exception, finaliza el proceso
		exit(1);
	}
}

if ( ! function_exists('_shutdown_handler'))
{
	/**
	 * _shutdown_handler()
	 * Función a ejecutar antes de finalizar el procesamiento de la aplicación
	 *
	 * @use _error_handler
	 * @use action_apply
	 *
	 * @return void
	 */
	function _shutdown_handler()
	{
		// Validar si se produjo la finalización por un error
		$last_error = error_get_last();

		if ( isset($last_error) &&
			($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING)))
		{
			_error_handler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
		}

		// Ejecutando funciones programadas
		action_apply ('do_when_end');
		action_apply ('shutdown');

		flush();
	}
}

if ( ! function_exists('_autoload'))
{
	/**
	 * _autoload()
	 * Función a ejecutar para leer una clase que aún no ha sido declarada
	 * 
	 * Las clases con namespace 	"Request" 		son buscados dentro de la carpeta 		"/request"
	 * Las clases con namespace 	"Response" 		son buscados dentro de la carpeta 		"/response"
	 * Las clases con namespace 	"Object" 		son buscados dentro de la carpeta 		"/objects"
	 *
	 * Las clases con namespace "Response" y sufijo "Structure" son buscados dentro de la carpeta 		"/response/structure"
	 *  	\Response\BasicStructure
	 *  	\Response\Structure\Basic
	 *
	 * Se busca en las carpetas configs/classes.
	 *
	 * Las clases con sufijo 		"Exception" 	también son buscados dentro de la carpeta 		"/configs/classes/exceptions"
	 * Las clases con sufijo 		"Object" 		también son buscados dentro de la carpeta 		"/objects"
	 *
	 * Las clase "Object" también es buscado dentro de la carpeta 		"/objects"
	 *
	 * @param string $main_class
	 * @return void
	 */
	function _autoload($main_class)
	{
		static $bcs = '\\';
		/**
		 * $class_structure
		 * Convirtiendo la clase como array
		 */
		$class_structure = explode($bcs, $main_class);
		
		/**
		 * $start_ws
		 * Identificar si han llamado a la clase como \ (backslash)
		 */
		$start_ws = FALSE;
		
		empty($class_structure[0]) and 
		$start_ws = TRUE and 
		array_shift($class_structure);
		
		/**
		 * $main_dir
		 * 
		 */
		$main_dir = '';
		
		/**
		 * $alter_dir
		 * 
		 */
		$alter_dir = '';
		
		/**
		 * $alter_class
		 * 
		 */
		$alter_class = '';
		
		if (count($class_structure) > 1)
		{
			$_namespace = array_shift($class_structure);
			
			$_class = array_shift($class_structure);
			
			switch($_namespace)
			{
				case 'Request':case 'Response':
					$main_dir = DS . mb_strtolower($_namespace);
					break;
				case 'Object':
					$main_dir = DS . mb_strtolower($_namespace) . 's';
					break;
			}
			
			if ($_namespace === 'Response' and preg_match('/(.+)Structure$/', $_class))
			{
				$main_dir.= DS . 'structure';
				$alter_class = $bcs . 'Response' . $bcs . 'Structure' . $bcs . preg_replace('/Structure$/', '', $_class);
				
				count($class_structure) > 0 and
				$alter_class .= $bcs . implode($bcs, $class_structure);
			}
			
			array_unshift($class_structure, $_class);
			array_unshift($class_structure, $_namespace);
		}
		
		empty($main_dir) and
		$main_dir = DS . 'configs' . DS . 'classes';
		
		$_class = array_shift($class_structure);
		
		if (preg_match('/(.+)Exception/', $_class))
		{
			$alter_class = DS . 'configs' . DS . 'classes' . DS . 'exceptions';
		}
		
		if (preg_match('/(.+)Object/', $_class) or $_class === 'Object')
		{
			$alter_class = DS . 'objects';
		}
		
		array_unshift($class_structure, $_class);
		
		global $BASES_path;
		
		$main_class_file = strtr($main_class, $bcs, DS) . '.php';
		
		$alter_class_file = '';
		
		! empty($alter_class) and
		$alter_class_file = strtr($alter_class, $bcs, DS) . '.php' and
		$alter_class = $main_class;
		
		foreach($BASES_path as $_path)
		{
			if ($_file = $_path . $main_dir . DS . ENVIRONMENT . DS . $main_class_file and file_exists($_file))
			{
				if (class_exists($main_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ( ! empty($alter_dir) and $_file = $_path . $alter_dir . DS . ENVIRONMENT . DS . $main_class_file and file_exists($_file))
			{
				if (class_exists($main_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ( ! empty($alter_class_file) and $_file = $_path . $main_dir . DS . ENVIRONMENT . DS . $alter_class_file and file_exists($_file))
			{
				if (class_exists($alter_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ( ! empty($alter_class_file) and  ! empty($alter_dir) and $_file = $_path . $alter_dir . DS . ENVIRONMENT . DS . $alter_class_file and file_exists($_file))
			{
				if (class_exists($alter_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ($_file = $_path . $main_dir . DS . $main_class_file and file_exists($_file))
			{
				if (class_exists($main_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ( ! empty($alter_dir) and $_file = $_path . $alter_dir . DS . $main_class_file and file_exists($_file))
			{
				if (class_exists($main_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ( ! empty($alter_class_file) and $_file = $_path . $main_dir . DS . $alter_class_file and file_exists($_file))
			{
				if (class_exists($alter_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
			
			if ( ! empty($alter_class_file) and  ! empty($alter_dir) and $_file = $_path . $alter_dir . DS . $alter_class_file and file_exists($_file))
			{
				if (class_exists($alter_class, FALSE) === FALSE and class_exists($alter_class, FALSE) === FALSE)
				{
					require_once $_file;
				}
			}
		}
		
		class_exists($main_class, FALSE) === FALSE and class_exists($alter_class , FALSE) === TRUE and class_alias($alter_class , $main_class);
	}
}

if ( ! function_exists('request'))
{
	/**
	 * request()
	 * Obtiene los request ($_GET $_POST)
	 *
	 * @param	string	$get
	 * @return	mixed
	 */
	function &request($get = 'array', $default = NULL, $put_default_if_empty = TRUE)
	{
		static $datos = [];
		
		if (count($datos) === 0)
		{
			try
			{
				$PInput = (array)json_decode(file_get_contents('php://input'), true);
			}
			catch(Exception $e)
			{
				$PInput = [];
			}

			$datos = array_merge(
				$_REQUEST,
				$_GET,
				$_POST,
				$PInput
			);

			$path = explode('/', url('path'));
			foreach($path as $_p)
			{
				if (preg_match('/(.+)(:|=)(.*)/i', $_p, $matches))
				{
					$datos[$matches[1]] = $matches[3];
				}
			}
		}
		
		if ($get === 'array')
		{
			return $datos;
		}
		
		$get = (array)$get;
		
		$return = $datos;
		foreach($get as $_get)
		{
			if ( ! isset($return[$_get]))
			{
				$return = $default;
				break;
			}
			
			if (is_empty($return[$_get]) and $put_default_if_empty)
			{
				$return = $default;
				break;
			}
			
			$return = $return[$_get];
		}
		
		return $return;
	}
}

if ( ! function_exists('url'))
{
	/**
	 * url()
	 * Obtiene la estructura y datos importantes de la URL
	 *
	 * @param	string	$get
	 * @return	mixed
	 */
	function &url($get = 'base')
	{
		static $datos = [];
		
		if (count($datos) === 0)
		{
			$file = __FILE__;
			
			//Archivo index que se ha leído originalmente
			$script_name = $_SERVER['SCRIPT_NAME'];
			
			//Variable indica si el index.php controlador esta dentro de una subcarpeta de donde se va a leer
			defined('SUBPATH') or define('SUBPATH', DS);
			
			//Si el archivo index está dentro de una carpeta desde la raiz (/)
			//No reemplaza la variable SUBPATH
			$uri_subpath = rtrim(str_replace('\\', '/', str_replace(basename($script_name), '', $script_name)), '/');
			$datos['uri_subpath'] = $uri_subpath;

			//Devuelve si usa https (boolean)
			$datos['https'] = FALSE;
			if (
				( ! empty($_SERVER['HTTPS']) && mb_strtolower($_SERVER['HTTPS']) !== 'off') ||
				(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && mb_strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
				( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && mb_strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') ||
				(isset($_SERVER['REQUEST_SCHEME']) and $_SERVER['REQUEST_SCHEME'] === 'https')
			)
			{
				$datos['https'] = TRUE;
			}

			isset($_SERVER['REQUEST_SCHEME']) or $_SERVER['REQUEST_SCHEME'] = 'http' . ($datos['https'] ? 's' : '');

			$_parsed = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
			$_parsed = parse_url($_parsed);
			
			//Devuelve 'http' o 'https' (string)
			$datos['scheme'] = $_parsed['scheme'];
			
			//Devuelve el host (string)
			$datos['host'] = $_parsed['host'];
			
			//Devuelve el port (int)
			$datos['port'] = $_parsed['port'];
			
			isset($_parsed['user']) and $datos['user'] = $_parsed['user'];
			isset($_parsed['pass']) and $datos['pass'] = $_parsed['pass'];
			
			$datos['path'] = isset($_parsed['path']) ? $_parsed['path'] : '/';
			empty($uri_subpath) or $datos['path'] = str_replace($uri_subpath, '', $datos['path']);
			
			$datos['query'] = isset($_parsed['query']) ? $_parsed['query'] : '';
			$datos['fragment'] = isset($_parsed['fragment']) ? $_parsed['fragment'] : '';
			
			//Devuelve el port en formato enlace (string)		:8082	para el caso del port 80 o 443 retorna vacío
			$datos['port-link'] = (new class($datos) implements JsonSerializable {
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$port_link = '';
					if ($this->datos['port'] <> 80 and $this->datos['port'] <> 443)
					{
						$port_link = ':' . $this->datos['port'];
					}
					return $port_link;
				}
				
				public function __debugInfo()
				{
					return [
						'port' => $this->datos['port'],
						'port-link' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve si usa WWW (boolean)
			$datos['www'] = (bool)preg_match('/^www\./', $datos['host']);
			
			//Devuelve el base host (string)
			$datos['host-base'] = (new class($datos) implements JsonSerializable{
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$host_base = explode('.', $this->datos['host']);
					
					while (count($host_base) > 2)
					{
						array_shift($host_base);
					}
					
					$host_base = implode('.', $host_base);
					
					return $host_base;
				}
				
				public function __debugInfo()
				{
					return [
						'host' => $this->datos['host'],
						'host-base' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve el host mas el port (string)			intranet.net:8082
			$datos['host-link'] = (new class($datos) implements JsonSerializable{
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$host_link = $this->datos['host'] . $this->datos['port-link'];
					return $host_link;
				}
				
				public function __debugInfo()
				{
					return [
						'host' => $this->datos['host'],
						'port-link' => (string)$this->datos['port-link'],
						'host-link' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve el host sin puntos o guiones	(string)	intranetnet
			$datos['host-clean'] = (new class($datos) implements JsonSerializable{
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$host_clean = preg_replace('/[^a-z0-9]/i', '', $this->datos['host']);
					return $host_clean;
				}
				
				public function __debugInfo()
				{
					return [
						'host' => $this->datos['host'],
						'host-clean' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve el scheme mas el host-link (string)	https://intranet.net:8082
			$datos['host-uri'] = (new class($datos) implements JsonSerializable{
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$host_uri = $this->datos['scheme'] . '://' . $this->datos['host-link'];
					return $host_uri;
				}
				
				public function __debugInfo()
				{
					return [
						'scheme' => $this->datos['scheme'],
						'host-link' => (string)$this->datos['host-link'],
						'host-uri' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve la URL base hasta la aplicación
			$datos['base'] = (new class($datos, $uri_subpath) implements JsonSerializable{
				private $datos;
				private $uri_subpath;
				
				public function __construct(&$datos, $uri_subpath)
				{
					$this->datos =& $datos;
					$this->uri_subpath = $uri_subpath;
				}
				
				public function __toString()
				{
					$base = $this->datos['host-uri'] . $this->uri_subpath;
					return $base;
				}
				
				public function __debugInfo()
				{
					return [
						'host-uri' => (string)$this->datos['host-uri'],
						'uri_subpath' => $this->uri_subpath,
						'base' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['subpath'] = rtrim(str_replace('\\', '/', SUBPATH), '/');
			
			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['abs'] = (new class($datos, $uri_subpath) implements JsonSerializable{
				private $datos;
				private $uri_subpath;
				private $subpath;
				
				public function __construct(&$datos, $uri_subpath)
				{
					$this->datos =& $datos;
					$this->uri_subpath = $uri_subpath;
				}
				
				public function __toString()
				{
					$abs = $this->datos['host-uri'] . $this->uri_subpath . $this->datos['subpath'];
					return $abs;
				}
				
				public function __debugInfo()
				{
					return [
						'host-uri' => (string)$this->datos['host-uri'],
						'uri_subpath' => $this->uri_subpath,
						'subpath' => $this->datos['subpath'],
						'abs' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve la URL base hasta el alojamiento real de la aplicación
			$datos['host-abs'] = (new class($datos, $uri_subpath) implements JsonSerializable{
				private $datos;
				private $uri_subpath;
				private $subpath;
				
				public function __construct(&$datos, $uri_subpath)
				{
					$this->datos =& $datos;
					$this->uri_subpath = $uri_subpath;
				}
				
				public function __toString()
				{
					$abs = str_replace('www.', '', $this->datos['host']) . $this->uri_subpath;
					return $abs;
				}
				
				public function __debugInfo()
				{
					return [
						'host' => (string)$this->datos['host'],
						'uri_subpath' => $this->uri_subpath,
						'host-abs' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve la URL completa incluido el PATH obtenido
			$datos['full'] = (new class($datos) implements JsonSerializable{
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$full = $this->datos['base'] . $this->datos['path'];
					
					return $full;
				}
				
				public function __debugInfo()
				{
					return [
						'base' => (string)$this->datos['base'],
						'path' => $this->datos['path'],
						'full' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve la URL completa incluyendo los parametros QUERY si es que hay
			$datos['full-wq'] = (new class($datos) implements JsonSerializable{
				private $datos;
				
				public function __construct(&$datos)
				{
					$this->datos =& $datos;
				}
				
				public function __toString()
				{
					$full_wq = $this->datos['full'] . ( ! empty($this->datos['query']) ? '?' : '' ) . $this->datos['query'];
					
					return $full_wq;
				}
				
				public function __debugInfo()
				{
					return [
						'full' => (string)$this->datos['full'],
						'query' => $this->datos['query'],
						'full-wq' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Devuelve la ruta de la aplicación como directorio del cookie
			$datos['cookie-base'] = $uri_subpath . '/';
			
			//Devuelve la ruta de la aplicación como directorio del cookie hasta la carpeta de la ruta actual
			$datos['cookie-full'] = (new class($datos, $uri_subpath) implements JsonSerializable{
				private $datos;
				private $uri_subpath;
				
				public function __construct(&$datos, $uri_subpath)
				{
					$this->datos =& $datos;
					$this->uri_subpath = $uri_subpath;
				}
				
				public function __toString()
				{
					$cookie_full = $this->uri_subpath . rtrim($this->datos['path'], '/') . '/';
					return $cookie_full;
				}
				
				public function __debugInfo()
				{
					return [
						'uri_subpath' => $this->uri_subpath,
						'path' => $this->datos['path'],
						'cookie-full' => $this->__toString()
					];
				}

				public function jsonSerialize() {
					return $this->__toString();
				}
			});
			
			//Obtiene todos los datos enviados
			$datos['request'] =& request('array');
			
			//Request Method
			$datos['request_method'] = mb_strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'cli');
			
//			$datos['array'] =& $datos;
		}

		if ($get === 'array')
		{
			return $datos;
		}

		isset($datos[$get]) or $datos[$get] = NULL;
		return $datos[$get];
	}
}


if ( ! function_exists('APP'))
{
	/**
	 * APP()
	 * Retorna la instancia del APP
	 * @return	APP
	 */
	function APP()
	{
		return APP::instance();
	}
}