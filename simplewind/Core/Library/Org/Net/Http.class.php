<?php
namespace Org\Net;
class Http
{
	static public function curlDownload($remote, $local)
	{
		$cp = curl_init($remote);
		$fp = fopen($local, "w");
		curl_setopt($cp, CURLOPT_FILE, $fp);
		curl_setopt($cp, CURLOPT_HEADER, 0);
		curl_exec($cp);
		curl_close($cp);
		fclose($fp);
	}

	static public function fsockopenDownload($url, $conf = array())
	{
		$return = '';
		if (!is_array($conf)) return $return;
		$matches = parse_url($url);
		!isset($matches['host']) && $matches['host'] = '';
		!isset($matches['path']) && $matches['path'] = '';
		!isset($matches['query']) && $matches['query'] = '';
		!isset($matches['port']) && $matches['port'] = '';
		$host = $matches['host'];
		$path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
		$port = !empty($matches['port']) ? $matches['port'] : 80;
		$conf_arr = array('limit' => 0, 'post' => '', 'cookie' => '', 'ip' => '', 'timeout' => 15, 'block' => TRUE,);
		foreach (array_merge($conf_arr, $conf) as $k => $v) ${$k} = $v;
		if ($post) {
			if (is_array($post)) {
				$post = http_build_query($post);
			}
			$out = "POST $path HTTP/1.0\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= 'Content-Length: ' . strlen($post) . "\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cache-Control: no-cache\r\n";
			$out .= "Cookie: $cookie\r\n\r\n";
			$out .= $post;
		} else {
			$out = "GET $path HTTP/1.0\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cookie: $cookie\r\n\r\n";
		}
		$fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
		if (!$fp) {
			return '';
		} else {
			stream_set_blocking($fp, $block);
			stream_set_timeout($fp, $timeout);
			@fwrite($fp, $out);
			$status = stream_get_meta_data($fp);
			if (!$status['timed_out']) {
				while (!feof($fp)) {
					if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
						break;
					}
				}
				$stop = false;
				while (!feof($fp) && !$stop) {
					$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
					$return .= $data;
					if ($limit) {
						$limit -= strlen($data);
						$stop = $limit <= 0;
					}
				}
			}
			@fclose($fp);
			return $return;
		}
	}

	static public function download($filename, $showname = '', $content = '', $expire = 180)
	{
		if (is_file($filename)) {
			$length = filesize($filename);
		} elseif (is_file(UPLOAD_PATH . $filename)) {
			$filename = UPLOAD_PATH . $filename;
			$length = filesize($filename);
		} elseif ($content != '') {
			$length = strlen($content);
		} else {
			E($filename . L('下载文件不存在！'));
		}
		if (empty($showname)) {
			$showname = $filename;
		}
		$showname = basename($showname);
		if (!empty($filename)) {
			$finfo = new \finfo(FILEINFO_MIME);
			$type = $finfo->file($filename);
		} else {
			$type = "application/octet-stream";
		}
		header("Pragma: public");
		header("Cache-control: max-age=" . $expire);
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
		header("Content-Disposition: attachment; filename=" . $showname);
		header("Content-Length: " . $length);
		header("Content-type: " . $type);
		header('Content-Encoding: none');
		header("Content-Transfer-Encoding: binary");
		if ($content == '') {
			readfile($filename);
		} else {
			echo($content);
		}
		exit();
	}

	static function getHeaderInfo($header = '', $echo = true)
	{
		ob_start();
		$headers = getallheaders();
		if (!empty($header)) {
			$info = $headers[$header];
			echo($header . ':' . $info . "\n");;
		} else {
			foreach ($headers as $key => $val) {
				echo("$key:$val\n");
			}
		}
		$output = ob_get_clean();
		if ($echo) {
			echo(nl2br($output));
		} else {
			return $output;
		}
	}

	static function sendHttpStatus($code)
	{
		static $_status = array(100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 509 => 'Bandwidth Limit Exceeded');
		if (isset($_status[$code])) {
			header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
		}
	}
}