<?php
namespace Think\Template\Driver;
class Lite
{
	public function fetch($templateFile, $var)
	{
		vendor("TemplateLite.class#template");
		$templateFile = substr($templateFile, strlen(THEME_PATH));
		$tpl = new \Template_Lite();
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
		$tpl->display($templateFile);
	}
}