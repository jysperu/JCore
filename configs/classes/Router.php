<?php
class Router
{
	public static function instance ()
	{
		static $instance, $inited = false;
		isset($instance) or $instance = new self();
		$inited or $inited = $instance->init();
		return $instance;
	}
	
	/**
	 * $http_methods
	 * Listado de Métodos de llamadas
	 *
	 * **Lista de Métodos**
	 * * **GET**: Sirve para leer un contenido
	 * * **POST**: Sirve para crear un contenido
	 * * **PUT**: Sirve para actualizar o reemplazar un contenido
	 * * **PATCH**: Sirve para actualizar o modificar un contenido
	 * * **DELETE**: Sirve para eliminar un contenido
	 *
	 * **Lista de Códigos de Respuestas**
	 * * **200**: OK, estándar para peticiones correctas
	 * * **201**: Created, se ha creado un nuevo contenido correctamente
	 * * **202**: Accepted, se ha aceptado el request y probablemente se este procesando una acción.
	 * * **204**: No Content, se ha procesado el request correctamente pero devuelve contenido vacío
	 * * **205**: Reset Content, se ha procesado el request correctamente pero devuelve contenido vacío
	 *							y ademas, el navegador tiene que inicializar la página desde la que se realizó la petición.
	 * * **206**: Parcial Content, se ha procesado el request correctamente pero devuelve contenido en partes
	 * * **400**: Bad Request, el request tiene errores
	 * * **401**: Unauthorized, la autenticación ha fallado
	 * * **403**: Forbidden, el request es correcto pero no tiene privilegios (indistinto a si esta o no autenticado)
	 * * **404**: Not Found, el request es correcto pero no se encuentra un procesador
	 * * **405**: Method Not Allowed, El request no debería ser llamado con el http_method enviado
	 * * **409**: Conflict, hay conflicto con el estado actual del contenido
	 * * **410**: Gone, igual que 404 pero mas rapido en los buscadores 
	 *				(el contenido no esta disponible y no lo estará de nuevo)
	 *
	 * @static
	 * @global
	 */
	public static $http_methods = [//~ ENTIRE COLLECTION        ~ SPECIFIC ITEM
		'GET',   // Read           		~ 200 (OK)                 ~ 200 (OK), 404 (Not Found)
		'POST',  // Create         		~ 201 (Created)            ~ 		 , 404 (Not Found), 409 (Conflict)
		'PUT',   // Update/Replace 		~ 405 (Method Not Allowed) ~ 200 (OK), 204 (No Content), 404 (Not Found)
		'PATCH', // Update/Modify  		~ 405 (Method Not Allowed) ~ 200 (OK), 204 (No Content), 404 (Not Found)
		'DELETE' // Delete         		~ 405 (Method Not Allowed) ~ 200 (OK), 404 (Not Found)
	];
	
	/**
	 * Variable para almacenar las reglas para reescribir la URI
	 * @protected
	 */
	protected $_uri_rewrites = [];

	/**
	 * Variable para almacenar el método HTTP usado
	 * @protected
	 */
	protected $http_verb;

	/**
	 * Variable para almacenar el URI a procesar
	 * @protected
	 */
	protected $uri_historial = [];

	/**
	 * Variable para almacenar el URI procesado
	 * @protected
	 */
	protected $uri;

	/**
	 * Variable para almacenar la versión de la URI procesada
	 * @protected
	 */
	protected $uri_parts = [
		'version' => '1.0',
		'lang' => NULL,
	];

	public function init ()
	{
		$this->_uri_rewrites = (function(){
			$rules = [];
			
			$archivos = load_file('configs/rewrites', FALSE, TRUE, TRUE);
			foreach($archivos as $archivo)
			{
				include $archivo;
			}
			
			return $rules;
		})();

		## El método como es llamado el REQUEST
		$this->http_verb = url('request_method');

		## El URI ha procesar
		$this->uri = NULL;

		foreach(['init', 'prepare', 'loaded'] as $kwrd)
		{
			foreach(['', '_' . $this->http_verb] as $hvrb)
			{
				$_stop = action_apply('router_' . $kwrd . $hvrb, $this);
				if ($_stop)
				{
					break 2;
				}
			}
		}

		foreach(['_' . $this->http_verb, ''] as $hvrb)
		{
			$_stop = action_apply('authentication' . $hvrb, $this);
			if ($_stop)
			{
				break;
			}
		}
	}
	
	public function process ()
	{
		
	}
}