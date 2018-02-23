<?php
namespace Aliyun\OSS\Models;
class MultipartUpload
{
	private $key;
	private $uploadId;
	private $initiated;

	public function setInitiated($initiated)
	{
		$this->initiated = $initiated;
	}

	public function getInitiated()
	{
		return $this->initiated;
	}

	public function setKey($key)
	{
		$this->key = $key;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function setUploadId($uploadId)
	{
		$this->uploadId = $uploadId;
	}

	public function getUploadId()
	{
		return $this->uploadId;
	}
}