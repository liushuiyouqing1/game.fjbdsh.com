<?php
namespace Aliyun\OSS\Models;
class AccessControlPolicy
{
	private $owner;
	private $grants;

	public function setGrants($grants)
	{
		$this->grants = $grants;
	}

	public function getGrants()
	{
		return $this->grants;
	}

	public function setOwner($owner)
	{
		$this->owner = $owner;
	}

	public function getOwner()
	{
		return $this->owner;
	}
} 