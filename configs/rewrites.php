<?php

$rules['/me(/.*)?'] = function()
{
	if ( ! APP()->Logged)
	{
		return '/Acceder';
	}
	
	return '/Usuario/'  . APP()->Usuario->id . '$1';
};

$rules['/(.*)\.php'] = function()
{
	return '/$1';
};