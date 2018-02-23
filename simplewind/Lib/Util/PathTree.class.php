<?php

class PathTree
{
	public $arr = array();
	public $icon = array('│', '├', '└');
	public $nbsp = "&nbsp;";

	public function init($arr = array())
	{
		$this->arr = $arr;
		return is_array($arr);
	}

	public function get_tree()
	{
		$array = $this->arr;
		foreach ($array as $key => $r) {
			$level = count(explode("-", $r["path"])) - 1;
			$array[$key]["level"] = $level;
			$array[$key]["spacer"] = $this->get_spacer($level - 1);
		}
		return $array;
	}

	public function get_spacer($count)
	{
		$spacer = "";
		for ($i = 0; $i < $count; $i++) {
			$spacer .= $this->nbsp;
		}
		return $spacer;
	}
}