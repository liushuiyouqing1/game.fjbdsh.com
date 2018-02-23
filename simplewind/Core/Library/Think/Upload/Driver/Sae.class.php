<?php
namespace Think\Upload\Driver;
class Sae
{
	private $domain = '';
	private $rootPath = '';
	private $error = '';

	public function __construct($config = null)
	{
		if (is_array($config) && !empty($config['domain'])) {
			$this->domain = strtolower($config['domain']);
		}
	}

	public function checkRootPath($rootpath)
	{
		$rootpath = trim($rootpath, './');
		if (!$this->domain) {
			$rootpath = explode('/', $rootpath);
			$this->domain = strtolower(array_shift($rootpath));
			$rootpath = implode('/', $rootpath);
		}
		$this->rootPath = $rootpath;
		$st = new \SaeStorage();
		if (false === $st->getDomainCapacity($this->domain)) {
			$this->error = '您好像没有建立Storage的domain[' . $this->domain . ']';
			return false;
		}
		return true;
	}

	public function checkSavePath($savepath)
	{
		return true;
	}

	public function save(&$file, $replace = true)
	{
		$filename = ltrim($this->rootPath . '/' . $file['savepath'] . $file['savename'], '/');
		$st = new \SaeStorage();
		if (!$replace && $st->fileExists($this->domain, $filename)) {
			$this->error = '存在同名文件' . $file['savename'];
			return false;
		}
		if (!$st->upload($this->domain, $filename, $file['tmp_name'])) {
			$this->error = '文件上传保存错误！[' . $st->errno() . ']:' . $st->errmsg();
			return false;
		} else {
			$file['url'] = $st->getUrl($this->domain, $filename);
		}
		return true;
	}

	public function mkdir()
	{
		return true;
	}

	public function getError()
	{
		return $this->error;
	}
} 