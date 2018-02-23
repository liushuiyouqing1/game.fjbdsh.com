<?php
namespace Aliyun\OSS\Models;
class ObjectListing
{
	private $objectSummarys = array();
	private $commonPrefixes = array();
	private $bucketName;
	private $nextMarker;
	private $isTruncated;
	private $prefix;
	private $marker;
	private $maxKeys;
	private $delimiter;

	public function setBucketName($bucketName)
	{
		$this->bucketName = $bucketName;
	}

	public function getBucketName()
	{
		return $this->bucketName;
	}

	public function setCommonPrefixes($commonPrefixes)
	{
		$this->commonPrefixes = $commonPrefixes;
	}

	public function getCommonPrefixes()
	{
		return $this->commonPrefixes;
	}

	public function setDelimiter($delimiter)
	{
		$this->delimiter = $delimiter;
	}

	public function getDelimiter()
	{
		return $this->delimiter;
	}

	public function setIsTruncated($isTruncated)
	{
		$this->isTruncated = $isTruncated;
	}

	public function getIsTruncated()
	{
		return $this->isTruncated;
	}

	public function setMarker($marker)
	{
		$this->marker = $marker;
	}

	public function getMarker()
	{
		return $this->marker;
	}

	public function setMaxKeys($maxKeys)
	{
		$this->maxKeys = $maxKeys;
	}

	public function getMaxKeys()
	{
		return $this->maxKeys;
	}

	public function setNextMarker($nextMarker)
	{
		$this->nextMarker = $nextMarker;
	}

	public function getNextMarker()
	{
		return $this->nextMarker;
	}

	public function setObjectSummarys($objectSummarys)
	{
		$this->objectSummarys = $objectSummarys;
	}

	public function getObjectSummarys()
	{
		return $this->objectSummarys;
	}

	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}
} 