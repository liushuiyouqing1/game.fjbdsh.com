<?php
namespace Behavior;

use Think\Storage;

class WriteHtmlCacheBehavior
{
	public function run(&$content)
	{
		if (C('HTML_CACHE_ON') && defined('HTML_FILE_NAME') && !preg_match('/Status.*[345]{1}\d{2}/i', implode(' ', headers_list())) && !preg_match('/(-[a-z0-9]{2}){3,}/i', HTML_FILE_NAME)) {
			Storage::put(HTML_FILE_NAME, $content, 'html');
		}
	}
}