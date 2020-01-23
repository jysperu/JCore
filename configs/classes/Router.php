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
	
	public function init ()
	{
		
	}
	
	public function process ()
	{
		
	}
}