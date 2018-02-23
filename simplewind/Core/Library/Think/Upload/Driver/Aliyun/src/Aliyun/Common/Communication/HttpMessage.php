<?php
namespace Aliyun\Common\Communication;

use Aliyun\Common\Utilities\AssertUtils;

abstract class HttpMessage
{
	protected $headers = array();
	protected $content = null;
	protected $offset;
	protected $contentMeta;

	public function getHeaders()
	{
		return $this->headers;
	}

	public function getHeader($name)
	{
		AssertUtils::assertString($name, 'HttpHeaderName');
		if (!isset($this->headers[$name])) {
			return null;
		}
		return $this->headers[$name];
	}

	public function addHeader($header, $value)
	{
		AssertUtils::assertString($header, 'HttpHeaderName');
		AssertUtils::assertString($value, 'HttpHeaderValue');
		$this->headers[$header] = $value;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function setContent($content)
	{
		if ($content == null) return;
		if (!is_resource($content) && !is_string($content)) {
			throw new \InvalidArgumentException('Http content must be a string or resource.');
		}
		$offset = 0;
		if (is_resource($content)) {
			$offset = ftell($content);
			$this->contentMeta = stream_get_meta_data($content);
		}
		$this->offset = $offset;
		$this->content = $content;
	}

	public function getOffset()
	{
		return $this->offset;
	}

	public function seekable()
	{
		if (is_string($this->content) || $this->content === null) return true;
		return $this->contentMeta['seekable'];
	}

	public function rewind()
	{
		if (is_string($this->content) || $this->content === null) {
			return true;
		}
		if (!$this->seekable()) {
			return false;
		}
		return fseek($this->content, $this->offset) == 0;
	}

	public function close()
	{
		if (is_resource($this->content)) {
			return fclose($this->content);
		}
		return false;
	}
} 