<?php
namespace Think\Template\Driver;
class Smart
{
	public function fetch($templateFile, $var)
	{
		$templateFile = substr($templateFile, strlen(THEME_PATH));
		vendor('SmartTemplate.class#smarttemplate');
		$tpl = new \SmartTemplate($templateFile);
		$tpl->caching = C('TMPL_CACHE_ON');
		$tpl->template_dir = THEME_PATH;
		$tpl->compile_dir = CACHE_PATH;
		$tpl->cache_dir = TEMP_PATH;
		if (C('TMPL_ENGINE_CONFIG')) {
			$config = C('TMPL_ENGINE_CONFIG');
			foreach ($config as $key => $val) {
				$tpl->{$key} = $val;
			}
		}
		$tpl->assign($var);
		$tpl->output();
	}
}