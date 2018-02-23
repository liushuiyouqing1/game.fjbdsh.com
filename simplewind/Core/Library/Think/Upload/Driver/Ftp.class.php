<?php
namespace Think\Upload\Driver;
class Ftp
{
	private $rootPath;
	private $error = '';
	private $link;
	private $config = array('host' => '', 'port' => 21, 'timeout' => 90, 'username' => '', 'password' => '',);

	public function __construct($config)
	{
		$this->config = array_merge($this->config, $config);
		if (!$this->login()) {
			E($this->error);
		}
	}

	public function checkRootPath($rootpath)
	{
		$this->rootPath = ftp_pwd($this->link) . '/' . ltrim($rootpath, '/');
		if (!@ftp_chdir($this->link, $this->rootPath)) {
			$this->error = '上传根目录不存在！';
			return false;
		}
		return true;
	}

	public function checkSavePath($savepath)
	{
		if (!$this->mkdir($savepath)) {
			return false;
		} else {
			return true;
		}
	}

	public function save($file, $replace = true)
	{
		$filename = $this->rootPath . $file['savepath'] . $file['savename'];
		if (!ftp_put($this->link, $filename, $file['tmp_name'], FTP_BINARY)) {
			$this->error = '文件上传保存错误！';
			return false;
		}
		return true;
	}

	public function mkdir($savepath)
	{
		$dir = $this->rootPath . $savepath;
		if (ftp_chdir($this->link, $dir)) {
			return true;
		}
		if (ftp_mkdir($this->link, $dir)) {
			return true;
		} elseif ($this->mkdir(dirname($savepath)) && ftp_mkdir($this->link, $dir)) {
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

	private function login()
	{
		extract($this->config);
		$this->link = ftp_connect($host, $port, $timeout);
		if ($this->link) {
			if (ftp_login($this->link, $username, $password)) {
				return true;
			} else {
				$this->error = "无法登录到FTP服务器：username - {$username}";
			}
		} else {
			$this->error = "无法连接到FTP服务器：{$host}";
		}
		return false;
	}

	public function __destruct()
	{
		ftp_close($this->link);
	}
} 