<?php
namespace Think\Storage\Driver;

use Think\Storage;

class File extends Storage
{
	private $contents = array();

	public function __construct()
	{
	}

	public function read($filename, $type = '')
	{
		return $this->get($filename, 'content', $type);
	}

	public function put($filename, $content, $type = '')
	{
		$dir = dirname($filename);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		if (false === file_put_contents($filename, $content)) {
			E(L('_STORAGE_WRITE_ERROR_') . ':' . $filename);
		} else {
			$this->contents[$filename] = $content;
			return true;
		}
	}

	public function append($filename, $content, $type = '')
	{
		if (is_file($filename)) {
			$content = $this->read($filename, $type) . $content;
		}
		return $this->put($filename, $content, $type);
	}

	public function load($_filename, $vars = null)
	{
		if (!is_null($vars)) {
			extract($vars, EXTR_OVERWRITE);
		}
		include $_filename;
	}

	public function has($filename, $type = '')
	{
		return is_file($filename);
	}

	public function unlink($filename, $type = '')
	{
		unset($this->contents[$filename]);
		return is_file($filename) ? unlink($filename) : false;
	}

	public function get($filename, $name, $type = '')
	{
		if (!isset($this->contents[$filename])) {
			if (!is_file($filename)) return false;
			$this->contents[$filename] = file_get_contents($filename);
		}
		$content = $this->contents[$filename];
		$info = array('mtime' => filemtime($filename), 'content' => $content);
		return $info[$name];
	}
} 