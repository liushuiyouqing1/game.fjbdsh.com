<?php
namespace Think\Log\Driver;
class Sae
{
	protected $config = array('log_time_format' => ' c ',);

	public function __construct($config = array())
	{
		$this->config = array_merge($this->config, $config);
	}

	public function write($log, $destination = '')
	{
		static $is_debug = null;
		$now = date($this->config['log_time_format']);
		$logstr = "[{$now}] " . $_SERVER['REMOTE_ADDR'] . ' ' . $_SERVER['REQUEST_URI'] . "\r\n{$log}\r\n";
		if (is_null($is_debug)) {
			preg_replace('@(\w+)\=([^;]*)@e', '$appSettings[\'\\1\']="\\2";', $_SERVER['HTTP_APPCOOKIE']);
			$is_debug = in_array($_SERVER['HTTP_APPVERSION'], explode(',', $appSettings['debug'])) ? true : false;
		}
		if ($is_debug) {
			sae_set_display_errors(false);
		}
		sae_debug($logstr);
		if ($is_debug) {
			sae_set_display_errors(true);
		}
	}
} 