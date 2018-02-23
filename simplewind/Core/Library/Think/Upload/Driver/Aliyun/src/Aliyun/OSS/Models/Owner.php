<?php
namespace Aliyun\OSS\Models;
class Owner
{
	private $displayName;
	private $id;

	public function setDisplayName($displayName)
	{
		$this->displayName = $displayName;
	}

	public function getDisplayName()
	{
		return $this->displayName;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}
}