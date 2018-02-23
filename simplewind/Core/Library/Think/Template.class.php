<?php
namespace Think;
class Template
{
	protected $tagLib = array();
	protected $templateFile = '';
	public $tVar = array();
	public $config = array();
	private $literal = array();
	private $block = array();

	public function __construct()
	{
		$this->config['cache_path'] = C('CACHE_PATH');
		$this->config['template_suffix'] = C('TMPL_TEMPLATE_SUFFIX');
		$this->config['cache_suffix'] = C('TMPL_CACHFILE_SUFFIX');
		$this->config['tmpl_cache'] = C('TMPL_CACHE_ON');
		$this->config['cache_time'] = C('TMPL_CACHE_TIME');
		$this->config['taglib_begin'] = $this->stripPreg(C('TAGLIB_BEGIN'));
		$this->config['taglib_end'] = $this->stripPreg(C('TAGLIB_END'));
		$this->config['tmpl_begin'] = $this->stripPreg(C('TMPL_L_DELIM'));
		$this->config['tmpl_end'] = $this->stripPreg(C('TMPL_R_DELIM'));
		$this->config['default_tmpl'] = C('TEMPLATE_NAME');
		$this->config['layout_item'] = C('TMPL_LAYOUT_ITEM');
	}

	private function stripPreg($str)
	{
		return str_replace(array('{', '}', '(', ')', '|', '[', ']', '-', '+', '*', '.', '^', '?'), array('\{', '\}', '\(', '\)', '\|', '\[', '\]', '\-', '\+', '\*', '\.', '\^', '\?'), $str);
	}

	public function get($name)
	{
		if (isset($this->tVar[$name])) return $this->tVar[$name]; else return false;
	}

	public function set($name, $value)
	{
		$this->tVar[$name] = $value;
	}

	public function fetch($templateFile, $templateVar, $prefix = '')
	{
		$this->tVar = $templateVar;
		$templateCacheFile = $this->loadTemplate($templateFile, $prefix);
		Storage::load($templateCacheFile, $this->tVar, null, 'tpl');
	}

	public function loadTemplate($templateFile, $prefix = '')
	{
		if (is_file($templateFile)) {
			$this->templateFile = $templateFile;
			$tmplContent = file_get_contents($templateFile);
		} else {
			$tmplContent = $templateFile;
		}
		$tmplCacheFile = $this->config['cache_path'] . $prefix . md5($templateFile) . $this->config['cache_suffix'];
		if (C('LAYOUT_ON')) {
			if (false !== strpos($tmplContent, '{__NOLAYOUT__}')) {
				$tmplContent = str_replace('{__NOLAYOUT__}', '', $tmplContent);
			} else {
				$layoutFile = THEME_PATH . C('LAYOUT_NAME') . $this->config['template_suffix'];
				if (!is_file($layoutFile)) {
					E(L('_TEMPLATE_NOT_EXIST_') . ':' . $layoutFile);
				}
				$tmplContent = str_replace($this->config['layout_item'], $tmplContent, file_get_contents($layoutFile));
			}
		}
		$tmplContent = $this->compiler($tmplContent);
		Storage::put($tmplCacheFile, trim($tmplContent), 'tpl');
		return $tmplCacheFile;
	}

	protected function compiler($tmplContent)
	{
		$tmplContent = $this->parse($tmplContent);
		$tmplContent = preg_replace_callback('/<!--###literal(\d+)###-->/is', array($this, 'restoreLiteral'), $tmplContent);
		$tmplContent = '<?php if (!defined(\'THINK_PATH\')) exit();?>' . $tmplContent;
		$tmplContent = str_replace('?><?php', '', $tmplContent);
		Hook::listen('template_filter', $tmplContent);
		return strip_whitespace($tmplContent);
	}

	public function parse($content)
	{
		if (empty($content)) return '';
		$begin = $this->config['taglib_begin'];
		$end = $this->config['taglib_end'];
		$content = $this->parseExtend($content);
		$content = $this->parseInclude($content);
		$content = $this->parsePhp($content);
		$content = preg_replace_callback('/' . $begin . 'literal' . $end . '(.*?)' . $begin . '\/literal' . $end . '/is', array($this, 'parseLiteral'), $content);
		if (C('TAGLIB_LOAD')) {
			$this->getIncludeTagLib($content);
			if (!empty($this->tagLib)) {
				foreach ($this->tagLib as $tagLibName) {
					$this->parseTagLib($tagLibName, $content);
				}
			}
		}
		if (C('TAGLIB_PRE_LOAD')) {
			$tagLibs = explode(',', C('TAGLIB_PRE_LOAD'));
			foreach ($tagLibs as $tag) {
				$this->parseTagLib($tag, $content);
			}
		}
		$tagLibs = explode(',', C('TAGLIB_BUILD_IN'));
		foreach ($tagLibs as $tag) {
			$this->parseTagLib($tag, $content, true);
		}
		$content = preg_replace_callback('/(' . $this->config['tmpl_begin'] . ')([^\d\w\s' . $this->config['tmpl_begin'] . $this->config['tmpl_end'] . '].+?)(' . $this->config['tmpl_end'] . ')/is', array($this, 'parseTag'), $content);
		return $content;
	}

	protected function parsePhp($content)
	{
		if (ini_get('short_open_tag')) {
			$content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
		}
		if (C('TMPL_DENY_PHP') && false !== strpos($content, '<?php')) {
			E(L('_NOT_ALLOW_PHP_'));
		}
		return $content;
	}

	protected function parseLayout($content)
	{
		$find = preg_match('/' . $this->config['taglib_begin'] . 'layout\s(.+?)\s*?\/' . $this->config['taglib_end'] . '/is', $content, $matches);
		if ($find) {
			$content = str_replace($matches[0], '', $content);
			$array = $this->parseXmlAttrs($matches[1]);
			if (!C('LAYOUT_ON') || C('LAYOUT_NAME') != $array['name']) {
				$layoutFile = THEME_PATH . $array['name'] . $this->config['template_suffix'];
				$replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
				$content = str_replace($replace, $content, file_get_contents($layoutFile));
			}
		} else {
			$content = str_replace('{__NOLAYOUT__}', '', $content);
		}
		return $content;
	}

	protected function parseInclude($content, $extend = true)
	{
		if ($extend) $content = $this->parseExtend($content);
		$content = $this->parseLayout($content);
		$find = preg_match_all('/' . $this->config['taglib_begin'] . 'include\s(.+?)\s*?\/' . $this->config['taglib_end'] . '/is', $content, $matches);
		if ($find) {
			for ($i = 0; $i < $find; $i++) {
				$include = $matches[1][$i];
				$array = $this->parseXmlAttrs($include);
				$file = $array['file'];
				unset($array['file']);
				$content = str_replace($matches[0][$i], $this->parseIncludeItem($file, $array, $extend), $content);
			}
		}
		return $content;
	}

	protected function parseExtend($content)
	{
		$begin = $this->config['taglib_begin'];
		$end = $this->config['taglib_end'];
		$find = preg_match('/' . $begin . 'extend\s(.+?)\s*?\/' . $end . '/is', $content, $matches);
		if ($find) {
			$content = str_replace($matches[0], '', $content);
			preg_replace_callback('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/is', array($this, 'parseBlock'), $content);
			$array = $this->parseXmlAttrs($matches[1]);
			$content = $this->parseTemplateName($array['name']);
			$content = $this->parseInclude($content, false);
			$content = $this->replaceBlock($content);
		} else {
			$content = preg_replace_callback('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/is', function ($match) {
				return stripslashes($match[2]);
			}, $content);
		}
		return $content;
	}

	private function parseXmlAttrs($attrs)
	{
		$xml = '<tpl><tag ' . $attrs . ' /></tpl>';
		$xml = simplexml_load_string($xml);
		if (!$xml) E(L('_XML_TAG_ERROR_'));
		$xml = (array)($xml->tag->attributes());
		$array = array_change_key_case($xml['@attributes']);
		return $array;
	}

	private function parseLiteral($content)
	{
		if (is_array($content)) $content = $content[1];
		if (trim($content) == '') return '';
		$i = count($this->literal);
		$parseStr = "<!--###literal{$i}###-->";
		$this->literal[$i] = $content;
		return $parseStr;
	}

	private function restoreLiteral($tag)
	{
		if (is_array($tag)) $tag = $tag[1];
		$parseStr = $this->literal[$tag];
		unset($this->literal[$tag]);
		return $parseStr;
	}

	private function parseBlock($name, $content = '')
	{
		if (is_array($name)) {
			$content = $name[2];
			$name = $name[1];
		}
		$this->block[$name] = $content;
		return '';
	}

	private function replaceBlock($content)
	{
		static $parse = 0;
		$begin = $this->config['taglib_begin'];
		$end = $this->config['taglib_end'];
		$reg = '/(' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . ')(.*?)' . $begin . '\/block' . $end . '/is';
		if (is_string($content)) {
			do {
				$content = preg_replace_callback($reg, array($this, 'replaceBlock'), $content);
			} while ($parse && $parse--);
			return $content;
		} elseif (is_array($content)) {
			if (preg_match('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '/is', $content[3])) {
				$parse = 1;
				$content[3] = preg_replace_callback($reg, array($this, 'replaceBlock'), "{$content[3]}{$begin}/block{$end}");
				return $content[1] . $content[3];
			} else {
				$name = $content[2];
				$content = $content[3];
				$content = isset($this->block[$name]) ? $this->block[$name] : $content;
				return $content;
			}
		}
	}

	public function getIncludeTagLib(& $content)
	{
		$find = preg_match('/' . $this->config['taglib_begin'] . 'taglib\s(.+?)(\s*?)\/' . $this->config['taglib_end'] . '\W/is', $content, $matches);
		if ($find) {
			$content = str_replace($matches[0], '', $content);
			$array = $this->parseXmlAttrs($matches[1]);
			$this->tagLib = explode(',', $array['name']);
		}
		return;
	}

	public function parseTagLib($tagLib, &$content, $hide = false)
	{
		$begin = $this->config['taglib_begin'];
		$end = $this->config['taglib_end'];
		if (strpos($tagLib, '\\')) {
			$className = $tagLib;
			$tagLib = substr($tagLib, strrpos($tagLib, '\\') + 1);
		} else {
			$className = 'Think\\Template\TagLib\\' . ucwords($tagLib);
		}
		$tLib = \Think\Think::instance($className);
		$that = $this;
		foreach ($tLib->getTags() as $name => $val) {
			$tags = array($name);
			if (isset($val['alias'])) {
				$tags = explode(',', $val['alias']);
				$tags[] = $name;
			}
			$level = isset($val['level']) ? $val['level'] : 1;
			$closeTag = isset($val['close']) ? $val['close'] : true;
			foreach ($tags as $tag) {
				$parseTag = !$hide ? $tagLib . ':' . $tag : $tag;
				if (!method_exists($tLib, '_' . $tag)) {
					$tag = $name;
				}
				$n1 = empty($val['attr']) ? '(\s*?)' : '\s([^' . $end . ']*)';
				$this->tempVar = array($tagLib, $tag);
				if (!$closeTag) {
					$patterns = '/' . $begin . $parseTag . $n1 . '\/(\s*?)' . $end . '/is';
					$content = preg_replace_callback($patterns, function ($matches) use ($tLib, $tag, $that) {
						return $that->parseXmlTag($tLib, $tag, $matches[1], $matches[2]);
					}, $content);
				} else {
					$patterns = '/' . $begin . $parseTag . $n1 . $end . '(.*?)' . $begin . '\/' . $parseTag . '(\s*?)' . $end . '/is';
					for ($i = 0; $i < $level; $i++) {
						$content = preg_replace_callback($patterns, function ($matches) use ($tLib, $tag, $that) {
							return $that->parseXmlTag($tLib, $tag, $matches[1], $matches[2]);
						}, $content);
					}
				}
			}
		}
	}

	public function parseXmlTag($tagLib, $tag, $attr, $content)
	{
		if (ini_get('magic_quotes_sybase')) $attr = str_replace('\"', '\'', $attr);
		$parse = '_' . $tag;
		$content = trim($content);
		$tags = $tagLib->parseXmlAttr($attr, $tag);
		return $tagLib->$parse($tags, $content);
	}

	public function parseTag($tagStr)
	{
		if (is_array($tagStr)) $tagStr = $tagStr[2];
		$tagStr = stripslashes($tagStr);
		$flag = substr($tagStr, 0, 1);
		$flag2 = substr($tagStr, 1, 1);
		$name = substr($tagStr, 1);
		if ('$' == $flag && '.' != $flag2 && '(' != $flag2) {
			return $this->parseVar($name);
		} elseif ('-' == $flag || '+' == $flag) {
			return '<?php echo ' . $flag . $name . ';?>';
		} elseif (':' == $flag) {
			return '<?php echo ' . $name . ';?>';
		} elseif ('~' == $flag) {
			return '<?php ' . $name . ';?>';
		} elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr(rtrim($tagStr), -2) == '*/')) {
			return '';
		}
		return C('TMPL_L_DELIM') . $tagStr . C('TMPL_R_DELIM');
	}

	public function parseVar($varStr)
	{
		$varStr = trim($varStr);
		static $_varParseList = array();
		if (isset($_varParseList[$varStr])) return $_varParseList[$varStr];
		$parseStr = '';
		$varExists = true;
		if (!empty($varStr)) {
			$varArray = explode('|', $varStr);
			$var = array_shift($varArray);
			if ('Think.' == substr($var, 0, 6)) {
				$name = $this->parseThinkVar($var);
			} elseif (false !== strpos($var, '.')) {
				$vars = explode('.', $var);
				$var = array_shift($vars);
				switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
					case 'array':
						$name = '$' . $var;
						foreach ($vars as $key => $val) $name .= '["' . $val . '"]';
						break;
					case 'obj':
						$name = '$' . $var;
						foreach ($vars as $key => $val) $name .= '->' . $val;
						break;
					default:
						$name = 'is_array($' . $var . ')?$' . $var . '["' . $vars[0] . '"]:$' . $var . '->' . $vars[0];
				}
			} elseif (false !== strpos($var, '[')) {
				$name = "$" . $var;
				preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
				$var = $match[1];
			} elseif (false !== strpos($var, ':') && false === strpos($var, '(') && false === strpos($var, '::') && false === strpos($var, '?')) {
				$vars = explode(':', $var);
				$var = str_replace(':', '->', $var);
				$name = "$" . $var;
				$var = $vars[0];
			} else {
				$name = "$$var";
			}
			if (count($varArray) > 0) $name = $this->parseVarFunction($name, $varArray);
			$parseStr = '<?php echo (' . $name . '); ?>';
		}
		$_varParseList[$varStr] = $parseStr;
		return $parseStr;
	}

	public function parseVarFunction($name, $varArray)
	{
		$length = count($varArray);
		$template_deny_funs = explode(',', C('TMPL_DENY_FUNC_LIST'));
		for ($i = 0; $i < $length; $i++) {
			$args = explode('=', $varArray[$i], 2);
			$fun = trim($args[0]);
			switch ($fun) {
				case 'default':
					$name = '(isset(' . $name . ') && (' . $name . ' !== ""))?(' . $name . '):' . $args[1];
					break;
				default:
					if (!in_array($fun, $template_deny_funs)) {
						if (isset($args[1])) {
							if (strstr($args[1], '###')) {
								$args[1] = str_replace('###', $name, $args[1]);
								$name = "$fun($args[1])";
							} else {
								$name = "$fun($name,$args[1])";
							}
						} else if (!empty($args[0])) {
							$name = "$fun($name)";
						}
					}
			}
		}
		return $name;
	}

	public function parseThinkVar($varStr)
	{
		$vars = explode('.', $varStr);
		$vars[1] = strtoupper(trim($vars[1]));
		$parseStr = '';
		if (count($vars) >= 3) {
			$vars[2] = trim($vars[2]);
			switch ($vars[1]) {
				case 'SERVER':
					$parseStr = '$_SERVER[\'' . strtoupper($vars[2]) . '\']';
					break;
				case 'GET':
					$parseStr = '$_GET[\'' . $vars[2] . '\']';
					break;
				case 'POST':
					$parseStr = '$_POST[\'' . $vars[2] . '\']';
					break;
				case 'COOKIE':
					if (isset($vars[3])) {
						$parseStr = '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
					} else {
						$parseStr = 'cookie(\'' . $vars[2] . '\')';
					}
					break;
				case 'SESSION':
					if (isset($vars[3])) {
						$parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
					} else {
						$parseStr = 'session(\'' . $vars[2] . '\')';
					}
					break;
				case 'ENV':
					$parseStr = '$_ENV[\'' . strtoupper($vars[2]) . '\']';
					break;
				case 'REQUEST':
					$parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
					break;
				case 'CONST':
					$parseStr = strtoupper($vars[2]);
					break;
				case 'LANG':
					$parseStr = 'L("' . $vars[2] . '")';
					break;
				case 'CONFIG':
					if (isset($vars[3])) {
						$vars[2] .= '.' . $vars[3];
					}
					$parseStr = 'C("' . $vars[2] . '")';
					break;
				default:
					break;
			}
		} else if (count($vars) == 2) {
			switch ($vars[1]) {
				case 'NOW':
					$parseStr = "date('Y-m-d g:i a',time())";
					break;
				case 'VERSION':
					$parseStr = 'THINK_VERSION';
					break;
				case 'TEMPLATE':
					$parseStr = "'" . $this->templateFile . "'";
					break;
				case 'LDELIM':
					$parseStr = 'C("TMPL_L_DELIM")';
					break;
				case 'RDELIM':
					$parseStr = 'C("TMPL_R_DELIM")';
					break;
				default:
					if (defined($vars[1])) $parseStr = $vars[1];
			}
		}
		return $parseStr;
	}

	private function parseIncludeItem($tmplPublicName, $vars = array(), $extend)
	{
		$parseStr = $this->parseTemplateName($tmplPublicName);
		foreach ($vars as $key => $val) {
			$parseStr = str_replace('[' . $key . ']', $val, $parseStr);
		}
		return $this->parseInclude($parseStr, $extend);
	}

	private function parseTemplateName($templateName)
	{
		if (substr($templateName, 0, 1) == '$') $templateName = $this->get(substr($templateName, 1));
		$array = explode(',', $templateName);
		$parseStr = '';
		foreach ($array as $templateName) {
			if (empty($templateName)) continue;
			if (false === strpos($templateName, $this->config['template_suffix'])) {
				$templateName = T($templateName);
			}
			$parseStr .= file_get_contents($templateName);
		}
		return $parseStr;
	}

	private function parseTcTemplateName($templateName)
	{
		if (substr($templateName, 0, 1) == '$') $templateName = $this->get(substr($templateName, 1));
		$array = explode(',', $templateName);
		$parseStr = '';
		foreach ($array as $templateName) {
			if (empty($templateName)) continue;
			if (false === file_exists_case($templateName)) {
				$templateName = str_replace(':', "/", $templateName);
				defined("SP_TMPL_PATH") ? SP_TMPL_PATH : define("SP_TMPL_PATH", "tpl/");
				$templateName = sp_add_template_file_suffix(SP_TMPL_PATH . SP_CURRENT_THEME . "/" . $templateName);
				$templateName = str_replace("//", "/", $templateName);
			}
			$parseStr .= file_get_contents($templateName);
		}
		return $parseStr;
	}
} 