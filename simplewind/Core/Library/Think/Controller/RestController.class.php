<?php
namespace Think\Controller;

use Think\Controller;
use Think\App;

class RestController extends Controller
{
	protected $_method = '';
	protected $_type = '';
	protected $allowMethod = array('get', 'post', 'put', 'delete');
	protected $defaultMethod = 'get';
	protected $allowType = array('html', 'xml', 'json', 'rss');
	protected $defaultType = 'html';
	protected $allowOutputType = array('xml' => 'application/xml', 'json' => 'application/json', 'html' => 'text/html',);

	public function __construct()
	{
		if ('' == __EXT__) {
			$this->_type = $this->getAcceptType();
		} elseif (!in_array(__EXT__, $this->allowType)) {
			$this->_type = $this->defaultType;
		} else {
			$this->_type = __EXT__;
		}
		$method = strtolower(REQUEST_METHOD);
		if (!in_array($method, $this->allowMethod)) {
			$method = $this->defaultMethod;
		}
		$this->_method = $method;
		parent::__construct();
	}

	public function __call($method, $args)
	{
		if (0 === strcasecmp($method, ACTION_NAME . C('ACTION_SUFFIX'))) {
			if (method_exists($this, $method . '_' . $this->_method . '_' . $this->_type)) {
				$fun = $method . '_' . $this->_method . '_' . $this->_type;
				App::invokeAction($this, $fun);
			} elseif ($this->_method == $this->defaultMethod && method_exists($this, $method . '_' . $this->_type)) {
				$fun = $method . '_' . $this->_type;
				App::invokeAction($this, $fun);
			} elseif ($this->_type == $this->defaultType && method_exists($this, $method . '_' . $this->_method)) {
				$fun = $method . '_' . $this->_method;
				App::invokeAction($this, $fun);
			} elseif (method_exists($this, '_empty')) {
				$this->_empty($method, $args);
			} elseif (file_exists_case($this->view->parseTemplate())) {
				$this->display();
			} else {
				E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
			}
		}
	}

	protected function getAcceptType()
	{
		$type = array('xml' => 'application/xml,text/xml,application/x-xml', 'json' => 'application/json,text/x-json,application/jsonrequest,text/json', 'js' => 'text/javascript,application/javascript,application/x-javascript', 'css' => 'text/css', 'rss' => 'application/rss+xml', 'yaml' => 'application/x-yaml,text/yaml', 'atom' => 'application/atom+xml', 'pdf' => 'application/pdf', 'text' => 'text/plain', 'png' => 'image/png', 'jpg' => 'image/jpg,image/jpeg,image/pjpeg', 'gif' => 'image/gif', 'csv' => 'text/csv', 'html' => 'text/html,application/xhtml+xml,*/*');
		foreach ($type as $key => $val) {
			$array = explode(',', $val);
			foreach ($array as $k => $v) {
				if (stristr($_SERVER['HTTP_ACCEPT'], $v)) {
					return $key;
				}
			}
		}
		return false;
	}

	protected function sendHttpStatus($code)
	{
		static $_status = array(100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Moved Temporarily ', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 509 => 'Bandwidth Limit Exceeded');
		if (isset($_status[$code])) {
			header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
			header('Status:' . $code . ' ' . $_status[$code]);
		}
	}

	protected function encodeData($data, $type = '')
	{
		if (empty($data)) return '';
		if ('json' == $type) {
			$data = json_encode($data);
		} elseif ('xml' == $type) {
			$data = xml_encode($data);
		} elseif ('php' == $type) {
			$data = serialize($data);
		}
		$this->setContentType($type);
		return $data;
	}

	public function setContentType($type, $charset = '')
	{
		if (headers_sent()) return;
		if (empty($charset)) $charset = C('DEFAULT_CHARSET');
		$type = strtolower($type);
		if (isset($this->allowOutputType[$type])) header('Content-Type: ' . $this->allowOutputType[$type] . '; charset=' . $charset);
	}

	protected function response($data, $type = '', $code = 200)
	{
		$this->sendHttpStatus($code);
		exit($this->encodeData($data, strtolower($type)));
	}
} 