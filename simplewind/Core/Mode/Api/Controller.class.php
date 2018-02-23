<?php
namespace Think;
abstract class Controller
{
	public function __construct()
	{
		if (method_exists($this, '_initialize')) $this->_initialize();
	}

	public function __call($method, $args)
	{
		if (0 === strcasecmp($method, ACTION_NAME . C('ACTION_SUFFIX'))) {
			if (method_exists($this, '_empty')) {
				$this->_empty($method, $args);
			} else {
				E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
			}
		} else {
			E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
			return;
		}
	}

	protected function ajaxReturn($data, $type = '')
	{
		if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
		switch (strtoupper($type)) {
			case 'JSON' :
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode($data));
			case 'XML' :
				header('Content-Type:text/xml; charset=utf-8');
				exit(xml_encode($data));
			case 'JSONP':
				header('Content-Type:application/json; charset=utf-8');
				$handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
				exit($handler . '(' . json_encode($data) . ');');
			case 'EVAL' :
				header('Content-Type:text/html; charset=utf-8');
				exit($data);
		}
	}

	protected function redirect($url, $params = array(), $delay = 0, $msg = '')
	{
		$url = U($url, $params);
		redirect($url, $delay, $msg);
	}
}