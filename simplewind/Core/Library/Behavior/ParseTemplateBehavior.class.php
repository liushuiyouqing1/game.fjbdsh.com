<?php
namespace Behavior;

use Think\Storage;
use Think\Think;

class ParseTemplateBehavior
{
	public function run(&$_data)
	{
		$engine = strtolower(C('TMPL_ENGINE_TYPE'));
		$_content = empty($_data['content']) ? $_data['file'] : $_data['content'];
		$_data['prefix'] = !empty($_data['prefix']) ? $_data['prefix'] : C('TMPL_CACHE_PREFIX');
		if ('think' == $engine) {
			if ((!empty($_data['content']) && $this->checkContentCache($_data['content'], $_data['prefix'])) || $this->checkCache($_data['file'], $_data['prefix'])) {
				Storage::load(C('CACHE_PATH') . $_data['prefix'] . md5($_content) . C('TMPL_CACHFILE_SUFFIX'), $_data['var']);
			} else {
				$tpl = Think::instance('Think\\Template');
				$tpl->fetch($_content, $_data['var'], $_data['prefix']);
			}
		} else {
			if (strpos($engine, '\\')) {
				$class = $engine;
			} else {
				$class = 'Think\\Template\\Driver\\' . ucwords($engine);
			}
			if (class_exists($class)) {
				$tpl = new $class;
				$tpl->fetch($_content, $_data['var']);
			} else {
				E(L('_NOT_SUPPORT_') . ': ' . $class);
			}
		}
	}

	protected function checkCache($tmplTemplateFile, $prefix = '')
	{
		if (!C('TMPL_CACHE_ON')) return false;
		$tmplCacheFile = C('CACHE_PATH') . $prefix . md5($tmplTemplateFile) . C('TMPL_CACHFILE_SUFFIX');
		if (!Storage::has($tmplCacheFile)) {
			return false;
		} elseif (filemtime($tmplTemplateFile) > Storage::get($tmplCacheFile, 'mtime')) {
			return false;
		} elseif (C('TMPL_CACHE_TIME') != 0 && time() > Storage::get($tmplCacheFile, 'mtime') + C('TMPL_CACHE_TIME')) {
			return false;
		}
		if (C('LAYOUT_ON')) {
			$layoutFile = THEME_PATH . C('LAYOUT_NAME') . C('TMPL_TEMPLATE_SUFFIX');
			if (filemtime($layoutFile) > Storage::get($tmplCacheFile, 'mtime')) {
				return false;
			}
		}
		return true;
	}

	protected function checkContentCache($tmplContent, $prefix = '')
	{
		if (Storage::has(C('CACHE_PATH') . $prefix . md5($tmplContent) . C('TMPL_CACHFILE_SUFFIX'))) {
			return true;
		} else {
			return false;
		}
	}
} 