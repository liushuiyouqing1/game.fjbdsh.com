<?php
namespace Think\Log\Driver;
class File
{
	protected $config = array('log_time_format' => ' c ', 'log_file_size' => 2097152, 'log_path' => '',);

	public function __construct($config = array())
	{
		$this->config = array_merge($this->config, $config);
	}

	public function write($log, $destination = '')
	{
		$now = date($this->config['log_time_format']);
		if (empty($destination)) {
			$destination = $this->config['log_path'] . date('y_m_d') . '.log';
		}
		$log_dir = dirname($destination);
		if (!is_dir($log_dir)) {
			mkdir($log_dir, 0755, true);
		}
		if (is_file($destination) && floor($this->config['log_file_size']) <= filesize($destination)) {
			rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
		}
		error_log("[{$now}] " . $_SERVER['REMOTE_ADDR'] . ' ' . $_SERVER['REQUEST_URI'] . "\r\n{$log}\r\n", 3, $destination);
	}
} 