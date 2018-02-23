<?php
namespace Behavior;
class CheckLangBehavior
{
	public function run(&$params)
	{
		$this->checkLanguage();
	}

	private function checkLanguage()
	{
		if (!C('LANG_SWITCH_ON', null, false)) {
			return;
		}
		$langSet = C('DEFAULT_LANG');
		$varLang = C('VAR_LANGUAGE', null, 'l');
		$langList = C('LANG_LIST', null, 'zh-cn');
		if (C('LANG_AUTO_DETECT', null, true)) {
			if (isset($_GET[$varLang])) {
				$langSet = $_GET[$varLang];
				cookie('think_language', $langSet, 3600);
			} elseif (cookie('think_language')) {
				$langSet = cookie('think_language');
			} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
				$langSet = $matches[1];
				cookie('think_language', $langSet, 3600);
			}
			if (false === stripos($langList, $langSet)) {
				$langSet = C('DEFAULT_LANG');
			}
		}
		define('LANG_SET', strtolower($langSet));
		$file = THINK_PATH . 'Lang/' . LANG_SET . '.php';
		if (LANG_SET != C('DEFAULT_LANG') && is_file($file)) L(include $file);
		$file = LANG_PATH . LANG_SET . '.php';
		if (is_file($file)) L(include $file);
		$file = MODULE_PATH . 'Lang/' . LANG_SET . '.php';
		if (is_file($file)) L(include $file);
		$file = MODULE_PATH . 'Lang/' . LANG_SET . '/' . strtolower(CONTROLLER_NAME) . '.php';
		if (is_file($file)) L(include $file);
	}
} 