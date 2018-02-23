<?php
namespace Guzzle\Common;
class Version
{
	const VERSION = '3.7.1';
	public static $emitWarnings = false;

	public static function warn($message)
	{
		if (self::$emitWarnings) {
			trigger_error('Deprecation warning: ' . $message, E_USER_DEPRECATED);
		}
	}
} 