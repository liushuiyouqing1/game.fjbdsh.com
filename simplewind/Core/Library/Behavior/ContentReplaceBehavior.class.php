<?php
namespace Behavior;
class ContentReplaceBehavior
{
	public function run(&$content)
	{
		$content = $this->templateContentReplace($content);
	}

	protected function templateContentReplace($content)
	{
		$replace = array('__ROOT__' => __ROOT__, '__APP__' => __APP__, '__MODULE__' => __MODULE__, '__ACTION__' => __ACTION__, '__SELF__' => htmlentities(__SELF__), '__CONTROLLER__' => __CONTROLLER__, '__URL__' => __CONTROLLER__, '__PUBLIC__' => __ROOT__ . '/public',);
		if (is_array(C('TMPL_PARSE_STRING'))) $replace = array_merge($replace, C('TMPL_PARSE_STRING'));
		$content = str_replace(array_keys($replace), array_values($replace), $content);
		return $content;
	}
}