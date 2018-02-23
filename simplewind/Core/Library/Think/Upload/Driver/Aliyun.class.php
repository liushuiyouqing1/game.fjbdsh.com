<?php
namespace Think\Upload\Driver;

use \Aliyun\OSS\OSSClient;

require_once dirname(__FILE__) . '/Aliyun/aliyun.php';

class Aliyun
{
	private $config = array('AccessKeyId' => '', 'AccessKeySecret' => '', 'domain' => '', 'Bucket' => '', 'Endpoint' => '',);
	private $error = '';

	public function __construct($config)
	{
		$this->config = array_merge($this->config, $config);
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
		$key = $file['savepath'] . $file['savename'];
		$content = fopen($file['tmp_name'], 'r');
		$size = $file['size'];
		$client = $this->client();
		$save = $client->putObject(array('Bucket' => $this->config['Bucket'], 'Key' => $key, 'Content' => $content, 'ContentLength' => $size,));
		$file['url'] = "http://{$this->config['domain']}/{$key}";
		if ($save) {
			return OSS . $key;
		} else {
			return false;
		}
	}

	public function getError()
	{
		return $this->client->errorStr;
	}

	function client()
	{
		$client = OSSClient::factory(array('Endpoint' => $this->config['Endpoint'], 'AccessKeyId' => $this->config['AccessKeyId'], 'AccessKeySecret' => $this->config['AccessKeySecret'],));
		return $client;
	}
}