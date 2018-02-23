<?php
namespace Think\Storage\Driver;

use Think\Storage;

class Sae extends Storage
{
	private $mc;
	private $kvs = array();
	private $htmls = array();
	private $contents = array();

	public function __construct()
	{
		if (!function_exists('memcache_init')) {
			header('Content-Type:text/html;charset=utf-8');
			exit('请在SAE平台上运行代码。');
		}
		$this->mc = @memcache_init();
		if (!$this->mc) {
			header('Content-Type:text/html;charset=utf-8');
			exit('您未开通Memcache服务，请在SAE管理平台初始化Memcache服务');
		}
	}

	private function getKv()
	{
		static $kv;
		if (!$kv) {
			$kv = new \SaeKV();
			if (!$kv->init()) E('您没有初始化KVDB，请在SAE管理平台初始化KVDB服务');
		}
		return $kv;
	}

	public function read($filename, $type = '')
	{
		switch (strtolower($type)) {
			case 'f':
				$kv = $this->getKv();
				if (!isset($this->kvs[$filename])) {
					$this->kvs[$filename] = $kv->get($filename);
				}
				return $this->kvs[$filename];
			default:
				return $this->get($filename, 'content', $type);
		}
	}

	public function put($filename, $content, $type = '')
	{
		switch (strtolower($type)) {
			case 'f':
				$kv = $this->getKv();
				$this->kvs[$filename] = $content;
				return $kv->set($filename, $content);
			case 'html':
				$kv = $this->getKv();
				$content = time() . $content;
				$this->htmls[$filename] = $content;
				return $kv->set($filename, $content);
			default:
				$content = time() . $content;
				if (!$this->mc->set($filename, $content, MEMCACHE_COMPRESSED, 0)) {
					E(L('_STORAGE_WRITE_ERROR_') . ':' . $filename);
				} else {
					$this->contents[$filename] = $content;
					return true;
				}
		}
	}

	public function append($filename, $content, $type = '')
	{
		if ($old_content = $this->read($filename, $type)) {
			$content = $old_content . $content;
		}
		return $this->put($filename, $content, $type);
	}

	public function load($_filename, $vars = null)
	{
		if (!is_null($vars)) extract($vars, EXTR_OVERWRITE);
		eval('?>' . $this->read($_filename));
	}

	public function has($filename, $type = '')
	{
		if ($this->read($filename, $type)) {
			return true;
		} else {
			return false;
		}
	}

	public function unlink($filename, $type = '')
	{
		switch (strtolower($type)) {
			case 'f':
				$kv = $this->getKv();
				unset($this->kvs[$filename]);
				return $kv->delete($filename);
			case 'html':
				$kv = $this->getKv();
				unset($this->htmls[$filename]);
				return $kv->delete($filename);
			default:
				unset($this->contents[$filename]);
				return $this->mc->delete($filename);
		}
	}

	public function get($filename, $name, $type = '')
	{
		switch (strtolower($type)) {
			case 'html':
				if (!isset($this->htmls[$filename])) {
					$kv = $this->getKv();
					$this->htmls[$filename] = $kv->get($filename);
				}
				$content = $this->htmls[$filename];
				break;
			default:
				if (!isset($this->contents[$filename])) {
					$this->contents[$filename] = $this->mc->get($filename);
				}
				$content = $this->contents[$filename];
		}
		if (false === $content) {
			return false;
		}
		$info = array('mtime' => substr($content, 0, 10), 'content' => substr($content, 10));
		return $info[$name];
	}
}