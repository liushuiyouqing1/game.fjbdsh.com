<?php
namespace Aliyun\OSS\Models;

use Aliyun\Common\Utilities\DateUtils;
use Aliyun\OSS\Utilities\OSSHeaders;

class OSSObject
{
	private $key;
	private $bucketName;
	private $objectContent = null;
	private $metadata = array();
	private $userMetadata = array();

	public function setBucketName($bucketName)
	{
		$this->bucketName = $bucketName;
	}

	public function getBucketName()
	{
		return $this->bucketName;
	}

	public function setKey($key)
	{
		$this->key = $key;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getMetadata()
	{
		return $this->metadata;
	}

	public function addMetadata($key, $value)
	{
		$this->metadata[$key] = $value;
	}

	public function setObjectContent($objectContent)
	{
		$this->objectContent = $objectContent;
	}

	public function getObjectContent()
	{
		return $this->objectContent;
	}

	public function getUserMetadata()
	{
		return $this->userMetadata;
	}

	public function addUserMetadata($key, $value)
	{
		$this->userMetadata[$key] = $value;
	}

	public function getLastModified()
	{
		if (!isset($this->metadata[OSSHeaders::LAST_MODIFIED])) {
			return null;
		}
		return $this->metadata[OSSHeaders::LAST_MODIFIED];
	}

	public function getContentLength()
	{
		$contentLengthKey = OSSHeaders::CONTENT_LENGTH;
		if (isset($this->metadata[$contentLengthKey])) {
			return (int)$this->metadata[$contentLengthKey];
		}
		$contentLengthKey = strtolower($contentLengthKey);
		if (isset($this->metadata[$contentLengthKey])) {
			return (int)$this->metadata[$contentLengthKey];
		}
		return (int)0;
	}

	public function getContentType()
	{
		if (!isset($this->metadata[OSSHeaders::CONTENT_TYPE])) {
			return null;
		}
		return $this->metadata[OSSHeaders::CONTENT_TYPE];
	}

	public function getContentEncoding()
	{
		if (!isset($this->metadata[OSSHeaders::CONTENT_ENCODING])) {
			return null;
		}
		return $this->metadata[OSSHeaders::CONTENT_ENCODING];
	}

	public function getContentLanguage()
	{
		if (!isset($this->metadata[OSSHeaders::CONTENT_LANGUAGE])) {
			return null;
		}
		return $this->metadata[OSSHeaders::CONTENT_LANGUAGE];
	}

	public function getExpires()
	{
		if (!isset($this->metadata[OSSHeaders::EXPIRES])) {
			return null;
		}
		return DateUtils::parseDate($this->metadata[OSSHeaders::EXPIRES]);
	}

	public function getCacheControl()
	{
		if (!isset($this->metadata[OSSHeaders::CACHE_CONTROL])) {
			return null;
		}
		return $this->metadata[OSSHeaders::CACHE_CONTROL];
	}

	public function getContentDisposition()
	{
		if (!isset($this->metadata[OSSHeaders::CONTENT_DISPOSITION])) {
			return null;
		}
		return $this->metadata[OSSHeaders::CONTENT_DISPOSITION];
	}

	public function getETag()
	{
		if (!isset($this->metadata[OSSHeaders::ETAG])) {
			return null;
		}
		return $this->metadata[OSSHeaders::ETAG];
	}

	public function __toString()
	{
		return stream_get_contents($this->objectContent, -1, 0);
	}

	public function __destruct()
	{
		if (is_resource($this->objectContent)) {
			fclose($this->objectContent);
		}
	}
} 