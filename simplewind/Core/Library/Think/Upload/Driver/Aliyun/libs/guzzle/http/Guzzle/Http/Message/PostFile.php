<?php
namespace Guzzle\Http\Message;

use Guzzle\Common\Version;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Mimetypes;

class PostFile implements PostFileInterface
{
	protected $fieldName;
	protected $contentType;
	protected $filename;

	public function __construct($fieldName, $filename, $contentType = null)
	{
		$this->fieldName = $fieldName;
		$this->setFilename($filename);
		$this->contentType = $contentType ?: $this->guessContentType();
	}

	public function setFieldName($name)
	{
		$this->fieldName = $name;
		return $this;
	}

	public function getFieldName()
	{
		return $this->fieldName;
	}

	public function setFilename($filename)
	{
		if (strpos($filename, '@') === 0) {
			$filename = substr($filename, 1);
		}
		if (!is_readable($filename)) {
			throw new InvalidArgumentException("Unable to open {$filename} for reading");
		}
		$this->filename = $filename;
		return $this;
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function setContentType($type)
	{
		$this->contentType = $type;
		return $this;
	}

	public function getContentType()
	{
		return $this->contentType;
	}

	public function getCurlValue()
	{
		if (function_exists('curl_file_create')) {
			return curl_file_create($this->filename, $this->contentType, basename($this->filename));
		}
		$value = "@{$this->filename};filename=" . basename($this->filename);
		if ($this->contentType) {
			$value .= ';type=' . $this->contentType;
		}
		return $value;
	}

	public function getCurlString()
	{
		Version::warn(__METHOD__ . ' is deprecated. Use getCurlValue()');
		return $this->getCurlValue();
	}

	protected function guessContentType()
	{
		return Mimetypes::getInstance()->fromFilename($this->filename) ?: 'application/octet-stream';
	}
} 