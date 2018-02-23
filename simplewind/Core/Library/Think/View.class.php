<?php
namespace Think;
class View
{
	protected $tVar = array();
	protected $theme = '';

	public function assign($name, $value = '')
	{
		if (is_array($name)) {
			$this->tVar = array_merge($this->tVar, $name);
		} else {
			$this->tVar[$name] = $value;
		}
	}

	public function get($name = '')
	{
		if ('' === $name) {
			return $this->tVar;
		}
		return isset($this->tVar[$name]) ? $this->tVar[$name] : false;
	}

	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
	{
		G('viewStartTime');
		Hook::listen('view_begin', $templateFile);
		$content = $this->fetch($templateFile, $content, $prefix);
		$this->render($content, $charset, $contentType);
		Hook::listen('view_end');
	}

	private function render($content, $charset = '', $contentType = '')
	{
		if (empty($charset)) $charset = C('DEFAULT_CHARSET');
		if (empty($contentType)) $contentType = C('TMPL_CONTENT_TYPE');
		header('Content-Type:' . $contentType . '; charset=' . $charset);
		header('Cache-control: ' . C('HTTP_CACHE_CONTROL'));
		header('X-Powered-By:ThinkCMF');
		echo $content;
	}

	public function fetch($templateFile = '', $content = '', $prefix = '')
	{
		if (empty($content)) {
			$templateFile = $this->parseTemplate($templateFile);
			if (!is_file($templateFile)) E(L('_TEMPLATE_NOT_EXIST_') . ':' . $templateFile);
		} else {
			defined('THEME_PATH') or define('THEME_PATH', $this->getThemePath());
		}
		ob_start();
		ob_implicit_flush(0);
		if ('php' == strtolower(C('TMPL_ENGINE_TYPE'))) {
			$_content = $content;
			extract($this->tVar, EXTR_OVERWRITE);
			empty($_content) ? include $templateFile : eval('?>' . $_content);
		} else {
			$params = array('var' => $this->tVar, 'file' => $templateFile, 'content' => $content, 'prefix' => $prefix);
			Hook::listen('view_parse', $params);
		}
		$content = ob_get_clean();
		Hook::listen('view_filter', $content);
		return $content;
	}

	public function parseTemplate($template = '')
	{
		if (is_file($template)) {
			return $template;
		}
		$depr = C('TMPL_FILE_DEPR');
		$template = str_replace(':', $depr, $template);
		$module = MODULE_NAME;
		if (strpos($template, '@')) {
			list($module, $template) = explode('@', $template);
		}
		defined('THEME_PATH') or define('THEME_PATH', $this->getThemePath($module));
		if ('' == $template) {
			$template = CONTROLLER_NAME . $depr . ACTION_NAME;
		} elseif (false === strpos($template, $depr)) {
			$template = CONTROLLER_NAME . $depr . $template;
		}
		$file = THEME_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
		if (C('TMPL_LOAD_DEFAULTTHEME') && THEME_NAME != C('DEFAULT_THEME') && !is_file($file)) {
			$file = dirname(THEME_PATH) . '/' . C('DEFAULT_THEME') . '/' . $template . C('TMPL_TEMPLATE_SUFFIX');
		}
		return $file;
	}

	protected function getThemePath($module = MODULE_NAME)
	{
		$theme = $this->getTemplateTheme();
		$tmplPath = C('VIEW_PATH');
		if (!$tmplPath) {
			$tmplPath = defined('TMPL_PATH') ? TMPL_PATH . $module . '/' : APP_PATH . $module . '/' . C('DEFAULT_V_LAYER') . '/';
		}
		return $tmplPath . $theme;
	}

	public function theme($theme)
	{
		$this->theme = $theme;
		return $this;
	}

	private function getTemplateTheme()
	{
		if ($this->theme) {
			$theme = $this->theme;
		} else {
			$theme = C('DEFAULT_THEME');
			if (C('TMPL_DETECT_THEME')) {
				$t = C('VAR_TEMPLATE');
				if (isset($_GET[$t])) {
					$theme = $_GET[$t];
				} elseif (cookie('think_template')) {
					$theme = cookie('think_template');
				}
				if (!in_array($theme, explode(',', C('THEME_LIST')))) {
					$theme = C('DEFAULT_THEME');
				}
				cookie('think_template', $theme, 864000);
			}
		}
		defined('THEME_NAME') || define('THEME_NAME', $theme);
		return $theme ? $theme . '/' : '';
	}
}