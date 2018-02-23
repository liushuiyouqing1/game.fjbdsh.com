<?php
namespace Think;
abstract class Controller
{
	protected $view = null;
	protected $config = array();

	public function __construct()
	{
		Hook::listen('action_begin', $this->config);
		$this->view = Think::instance('Think\View');
		if (method_exists($this, '_initialize')) $this->_initialize();
	}

	protected function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
	{
		$this->view->display($templateFile, $charset, $contentType, $content, $prefix);
	}

	protected function show($content, $charset = '', $contentType = '', $prefix = '')
	{
		$this->view->display('', $charset, $contentType, $content, $prefix);
	}

	protected function fetch($templateFile = '', $content = '', $prefix = '')
	{
		return $this->view->fetch($templateFile, $content, $prefix);
	}

	protected function buildHtml($htmlfile = '', $htmlpath = '', $templateFile = '')
	{
		$content = $this->fetch($templateFile);
		$htmlpath = !empty($htmlpath) ? $htmlpath : HTML_PATH;
		$htmlfile = $htmlpath . $htmlfile . C('HTML_FILE_SUFFIX');
		Storage::put($htmlfile, $content, 'html');
		return $content;
	}

	protected function theme($theme)
	{
		$this->view->theme($theme);
		return $this;
	}

	protected function assign($name, $value = '')
	{
		$this->view->assign($name, $value);
		return $this;
	}

	public function __set($name, $value)
	{
		$this->assign($name, $value);
	}

	public function get($name = '')
	{
		return $this->view->get($name);
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __isset($name)
	{
		return $this->get($name);
	}

	public function __call($method, $args)
	{
		if (0 === strcasecmp($method, ACTION_NAME . C('ACTION_SUFFIX'))) {
			if (method_exists($this, '_empty')) {
				$this->_empty($method, $args);
			} elseif (file_exists_case($this->view->parseTemplate())) {
				$this->display();
			} else {
				E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
			}
		} else {
			E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
			return;
		}
	}

	protected function error($message = '', $jumpUrl = '', $ajax = false)
	{
		$this->dispatchJump($message, 0, $jumpUrl, $ajax);
	}

	protected function success($message = '', $jumpUrl = '', $ajax = false)
	{
		$this->dispatchJump($message, 1, $jumpUrl, $ajax);
	}

	protected function ajaxReturn($data, $type = '', $json_option = 0)
	{
		if (empty($type)) $type = C('DEFAULT_AJAX_RETURN');
		switch (strtoupper($type)) {
			case 'JSON' :
				header('Content-Type:application/json; charset=utf-8');
				exit(json_encode($data, $json_option));
			case 'XML' :
				header('Content-Type:text/xml; charset=utf-8');
				exit(xml_encode($data));
			case 'JSONP':
				header('Content-Type:application/json; charset=utf-8');
				$handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
				exit($handler . '(' . json_encode($data, $json_option) . ');');
			case 'EVAL' :
				header('Content-Type:text/html; charset=utf-8');
				exit($data);
			default :
				Hook::listen('ajax_return', $data);
		}
	}

	protected function redirect($url, $params = array(), $delay = 0, $msg = '')
	{
		$url = U($url, $params);
		redirect($url, $delay, $msg);
	}

	private function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false)
	{
		if (true === $ajax || IS_AJAX) {
			$data = is_array($ajax) ? $ajax : array();
			$data['info'] = $message;
			$data['status'] = $status;
			$data['url'] = $jumpUrl;
			$this->ajaxReturn($data);
		}
		if (is_int($ajax)) $this->assign('waitSecond', $ajax);
		if (!empty($jumpUrl)) $this->assign('jumpUrl', $jumpUrl);
		$this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
		if ($this->get('closeWin')) $this->assign('jumpUrl', 'javascript:window.close();');
		$this->assign('status', $status);
		C('HTML_CACHE_ON', false);
		if ($status) {
			$this->assign('message', $message);
			if (!isset($this->waitSecond)) $this->assign('waitSecond', '1');
			if (!isset($this->jumpUrl)) $this->assign("jumpUrl", $_SERVER["HTTP_REFERER"]);
			$this->display(C('TMPL_ACTION_SUCCESS'));
		} else {
			$this->assign('error', $message);
			if (!isset($this->waitSecond)) $this->assign('waitSecond', '3');
			if (!isset($this->jumpUrl)) $this->assign('jumpUrl', "javascript:history.back(-1);");
			$this->display(C('TMPL_ACTION_ERROR'));
			exit;
		}
	}

	public function __destruct()
	{
		Hook::listen('action_end');
	}
}

class_alias('Think\Controller', 'Think\Action'); 