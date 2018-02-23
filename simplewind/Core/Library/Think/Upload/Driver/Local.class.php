<?php
namespace Think\Upload\Driver;
class Local
{
	private $rootPath;
	private $error = '';

	public function __construct($config = null)
	{
	}

	public function checkRootPath($rootpath)
	{
		if (!(is_dir($rootpath) && is_writable($rootpath))) {
			$this->error = '上传根目录不存在！请尝试手动创建:' . $rootpath;
			return false;
		}
		$this->rootPath = $rootpath;
		return true;
	}

	public function checkSavePath($savepath)
	{
		if (!$this->mkdir($savepath)) {
			return false;
		} else {
			if (!is_writable($this->rootPath . $savepath)) {
				$this->error = '上传目录 ' . $savepath . ' 不可写！';
				return false;
			} else {
				return true;
			}
		}
	}

	public function save($file, $replace = true)
	{
		$filename = $this->rootPath . $file['savepath'] . $file['savename'];
		if (!$replace && is_file($filename)) {
			$this->error = '存在同名文件' . $file['savename'];
			return false;
		}
		if (!move_uploaded_file($file['tmp_name'], $filename)) {
			$this->error = '文件上传保存错误！';
			return false;
		}
		return true;
	}

	public function mkdir($savepath)
	{
		$dir = $this->rootPath . $savepath;
		if (is_dir($dir)) {
			return true;
		}
		if (mkdir($dir, 0777, true)) {
			return true;
		} else {
			$this->error = "目录 {$savepath} 创建失败！";
			return false;
		}
	}

	public function getError()
	{
		return $this->error;
	}
} 