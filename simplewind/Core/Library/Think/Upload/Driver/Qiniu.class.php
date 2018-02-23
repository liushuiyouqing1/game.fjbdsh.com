<?php
namespace Think\Upload\Driver;

use Think\Upload\Driver\Qiniu\QiniuStorage;

class Qiniu
{
	private $rootPath;
	private $error = '';
	private $config = array('secretKey' => '', 'accessKey' => '', 'domain' => '', 'bucket' => '', 'timeout' => 300,);

	public function __construct($config)
	{
		$this->config = array_merge($this->config, $config);
		$this->qiniu = new QiniuStorage($config);
	}

	public function checkRootPath($rootpath)
	{
		$this->rootPath = trim($rootpath, './') . '/';
		return true;
	}

	public function checkSavePath($savepath)
	{
		return true;
	}

	public function mkdir($savepath)
	{
		return true;
	}

	public function save(&$file, $replace = true)
	{
		$file['name'] = $file['savepath'] . $file['savename'];
		$key = $file['name'];
		$upfile = array('name' => 'file', 'fileName' => $key, 'fileBody' => file_get_contents($file['tmp_name']));
		$result = $this->qiniu->upload($this->config, $upfile);
		$url = $this->qiniu->downlink($key);
		$file['url'] = $url;
		return false === $result ? false : true;
	}

	public function getError()
	{
		return $this->qiniu->errorStr;
	}
} 