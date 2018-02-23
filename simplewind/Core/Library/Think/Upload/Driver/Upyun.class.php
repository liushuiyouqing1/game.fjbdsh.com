<?php
namespace Think\Upload\Driver;
class Upyun
{
	private $rootPath;
	private $error = '';
	private $config = array('host' => '', 'username' => '', 'password' => '', 'bucket' => '', 'timeout' => 90,);

	public function __construct($config)
	{
		$this->config = array_merge($this->config, $config);
		$this->config['password'] = md5($this->config['password']);
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

	public function save($file, $replace = true)
	{
		$header['Content-Type'] = $file['type'];
		$header['Content-MD5'] = $file['md5'];
		$header['Mkdir'] = 'true';
		$resource = fopen($file['tmp_name'], 'r');
		$save = $this->rootPath . $file['savepath'] . $file['savename'];
		$data = $this->request($save, 'PUT', $header, $resource);
		return false === $data ? false : true;
	}

	public function getError()
	{
		return $this->error;
	}

	private function request($path, $method, $headers = null, $body = null)
	{
		$uri = "/{$this->config['bucket']}/{$path}";
		$ch = curl_init($this->config['host'] . $uri);
		$_headers = array('Expect:');
		if (!is_null($headers) && is_array($headers)) {
			foreach ($headers as $k => $v) {
				array_push($_headers, "{$k}: {$v}");
			}
		}
		$length = 0;
		$date = gmdate('D, d M Y H:i:s \G\M\T');
		if (!is_null($body)) {
			if (is_resource($body)) {
				fseek($body, 0, SEEK_END);
				$length = ftell($body);
				fseek($body, 0);
				array_push($_headers, "Content-Length: {$length}");
				curl_setopt($ch, CURLOPT_INFILE, $body);
				curl_setopt($ch, CURLOPT_INFILESIZE, $length);
			} else {
				$length = @strlen($body);
				array_push($_headers, "Content-Length: {$length}");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			}
		} else {
			array_push($_headers, "Content-Length: {$length}");
		}
		array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
		array_push($_headers, "Date: {$date}");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if ($method == 'PUT' || $method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
		} else {
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		if ($method == 'HEAD') {
			curl_setopt($ch, CURLOPT_NOBODY, true);
		}
		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		list($header, $body) = explode("\r\n\r\n", $response, 2);
		if ($status == 200) {
			if ($method == 'GET') {
				return $body;
			} else {
				$data = $this->response($header);
				return count($data) > 0 ? $data : true;
			}
		} else {
			$this->error($header);
			return false;
		}
	}

	private function response($text)
	{
		$headers = explode("\r\n", $text);
		$items = array();
		foreach ($headers as $header) {
			$header = trim($header);
			if (strpos($header, 'x-upyun') !== False) {
				list($k, $v) = explode(':', $header);
				$items[trim($k)] = in_array(substr($k, 8, 5), array('width', 'heigh', 'frame')) ? intval($v) : trim($v);
			}
		}
		return $items;
	}

	private function sign($method, $uri, $date, $length)
	{
		$sign = "{$method}&{$uri}&{$date}&{$length}&{$this->config['password']}";
		return 'UpYun ' . $this->config['username'] . ':' . md5($sign);
	}

	private function error($header)
	{
		list($status, $stash) = explode("\r\n", $header, 2);
		list($v, $code, $message) = explode(" ", $status, 3);
		$message = is_null($message) ? 'File Not Found' : "[{$status}]:{$message}";
		$this->error = $message;
	}
} 