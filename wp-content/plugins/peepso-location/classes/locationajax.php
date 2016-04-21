<?php

class PeepSoLocationAjax implements PeepSoAjaxCallback
{
	private static $_instance = NULL;
	private static $_peepsolocation = NULL;

	private function __construct()
	{
		self::$_peepsolocation = PeepSoLocation::get_instance();
	}

	public static function get_instance()
	{
		if (self::$_instance === NULL)
			self::$_instance = new self();
		return (self::$_instance);
	}

}

// EOF
