<?php
namespace Aliyun\OSS\Models;

use Aliyun\Common\Utilities\AssertUtils;

class Bucket
{
	private $name;
	private $owner;
	private $creationDate;

	public function __construct($name)
	{
		$this->setName($name);
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		AssertUtils::assertString($name, 'name');
		$this->name = $name;
	}

	public function getOwner()
	{
		return $this->owner;
	}

	public function setOwner($owner)
	{
		$this->owner = $owner;
	}

	public function getCreationDate()
	{
		return $this->creationDate;
	}

	public function setCreationDate(\DateTime $creationDate)
	{
		$this->creationDate = $creationDate;
	}
} 