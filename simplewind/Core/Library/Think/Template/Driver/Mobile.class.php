<?php
namespace Think\Template\Driver;
class Mobile
{
	public function fetch($templateFile, $var)
	{
		$templateFile = substr($templateFile, strlen(THEME_PATH));
		$var['_think_template_path'] = $templateFile;
		exit(json_encode($var));
	}
} 