<?php
/**
 * config.php
 * Archivo de Configuración Básico
 *
 * @filesource
 */

/**
 * charset
 * Charset por Defecto
 * Def: UTF-8
 */
$config['charset'] = 'UTF-8';

$config['timezone'] = 'America/Lima';

$config['lang'] = NULL;

$config['db']['host'] = 'localhost';
$config['db']['user'] = 'root';
$config['db']['pswd'] = 'mysql';
$config['db']['name'] = NULL;

$config['www'] = NULL;

$config['https'] = NULL;

$config['http_methods_allowed'] = ['GET', 'POST'];

$config['request_default_method'] = 'index';

$config['home_default_response_class'] = 'Inicio';

$config['error404_default_response_class'] = 'Error404';
