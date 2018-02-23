<?php
namespace plugins\Comment;

use Common\Lib\Plugin;

class CommentPlugin extends Plugin
{
	public $info = array('name' => 'Comment', 'title' => '系统评论插件', 'description' => '系统评论插件', 'status' => 1, 'author' => 'ThinkCMF', 'version' => '1.0');
	public $has_admin = 0;

	public function install()
	{
		return true;
	}

	public function uninstall()
	{
		return true;
	}

	public function comment($param)
	{
		echo Comments($param['post_table'], $param['post_id'], array('post_title' => $param['post_title']));
	}
}