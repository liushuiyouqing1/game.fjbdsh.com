<?php
namespace Behavior;
class TokenBuildBehavior
{
	public function run(&$content)
	{
		if (C('TOKEN_ON')) {
			list($tokenName, $tokenKey, $tokenValue) = $this->getToken();
			$input_token = '<input type="hidden" name="' . $tokenName . '" value="' . $tokenKey . '_' . $tokenValue . '" />';
			$meta_token = '<meta name="' . $tokenName . '" content="' . $tokenKey . '_' . $tokenValue . '" />';
			if (strpos($content, '{__TOKEN__}')) {
				$content = str_replace('{__TOKEN__}', $input_token, $content);
			} elseif (preg_match('/<\/form(\s*)>/is', $content, $match)) {
				$content = str_replace($match[0], $input_token . $match[0], $content);
			}
			$content = str_ireplace('</head>', $meta_token . '</head>', $content);
		} else {
			$content = str_replace('{__TOKEN__}', '', $content);
		}
	}

	private function getToken()
	{
		$tokenName = C('TOKEN_NAME', null, '__hash__');
		$tokenType = C('TOKEN_TYPE', null, 'md5');
		if (!isset($_SESSION[$tokenName])) {
			$_SESSION[$tokenName] = array();
		}
		$tokenKey = md5($_SERVER['REQUEST_URI']);
		if (isset($_SESSION[$tokenName][$tokenKey])) {
			$tokenValue = $_SESSION[$tokenName][$tokenKey];
		} else {
			$tokenValue = is_callable($tokenType) ? $tokenType(microtime(true)) : md5(microtime(true));
			$_SESSION[$tokenName][$tokenKey] = $tokenValue;
			if (IS_AJAX && C('TOKEN_RESET', null, true)) header($tokenName . ': ' . $tokenKey . '_' . $tokenValue);
		}
		return array($tokenName, $tokenKey, $tokenValue);
	}
}