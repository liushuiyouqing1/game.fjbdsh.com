<?php
namespace Guzzle\Http;

use Guzzle\Common\Version;
use Guzzle\Stream\Stream;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Mimetypes;

class EntityBody extends Stream implements EntityBodyInterface
{
	protected $contentEncoding = false;
	protected $rewindFunction;

	public static function factory($resource = '', $size = null)
	{
		if ($resource instanceof EntityBodyInterface) {
			return $resource;
		}
		switch (gettype($resource)) {
			case 'string':
				return self::fromString($resource);
			case 'resource':
				return new static($resource, $size);
			case 'object':
				if (method_exists($resource, '__toString')) {
					return self::fromString((string)$resource);
				}
				break;
			case 'array':
				return self::fromString(http_build_query($resource));
		}
		throw new InvalidArgumentException('Invalid resource type');
	}

	public function setRewindFunction($callable)
	{
		if (!is_callable($callable)) {
			throw new InvalidArgumentException('Must specify a callable');
		}
		$this->rewindFunction = $callable;
		return $this;
	}

	public function rewind()
	{
		return $this->rewindFunction ? call_user_func($this->rewindFunction, $this) : parent::rewind();
	}

	public static function fromString($string)
	{
		$stream = fopen('php://temp', 'r+');
		if ($string !== '') {
			fwrite($stream, $string);
			rewind($stream);
		}
		return new static($stream);
	}

	public function compress($filter = 'zlib.deflate')
	{
		$result = $this->handleCompression($filter);
		$this->contentEncoding = $result ? $filter : false;
		return $result;
	}

	public function uncompress($filter = 'zlib.inflate')
	{
		$offsetStart = 0;
		if ($filter == 'zlib.inflate') {
			if (!$this->isReadable() || ($this->isConsumed() && !$this->isSeekable())) {
				return false;
			}
			if (stream_get_contents($this->stream, 3, 0) === "\x1f\x8b\x08") {
				$offsetStart = 10;
			}
		}
		$this->contentEncoding = false;
		return $this->handleCompression($filter, $offsetStart);
	}

	public function getContentLength()
	{
		return $this->getSize();
	}

	public function getContentType()
	{
		return $this->getUri() ? Mimetypes::getInstance()->fromFilename($this->getUri()) : null;
	}

	public function getContentMd5($rawOutput = false, $base64Encode = false)
	{
		if ($hash = self::getHash($this, 'md5', $rawOutput)) {
			return $hash && $base64Encode ? base64_encode($hash) : $hash;
		} else {
			return false;
		}
	}

	public static function calculateMd5(EntityBodyInterface $body, $rawOutput = false, $base64Encode = false)
	{
		Version::warn(__CLASS__ . ' is deprecated. Use getContentMd5()');
		return $body->getContentMd5($rawOutput, $base64Encode);
	}

	public function setStreamFilterContentEncoding($streamFilterContentEncoding)
	{
		$this->contentEncoding = $streamFilterContentEncoding;
		return $this;
	}

	public function getContentEncoding()
	{
		return strtr($this->contentEncoding, array('zlib.deflate' => 'gzip', 'bzip2.compress' => 'compress')) ?: false;
	}

	protected function handleCompression($filter, $offsetStart = 0)
	{
		if (!$this->isReadable() || ($this->isConsumed() && !$this->isSeekable())) {
			return false;
		}
		$handle = fopen('php://temp', 'r+');
		$filter = @stream_filter_append($handle, $filter, STREAM_FILTER_WRITE);
		if (!$filter) {
			return false;
		}
		$this->seek($offsetStart);
		while ($data = fread($this->stream, 8096)) {
			fwrite($handle, $data);
		}
		fclose($this->stream);
		$this->stream = $handle;
		stream_filter_remove($filter);
		$stat = fstat($this->stream);
		$this->size = $stat['size'];
		$this->rebuildCache();
		$this->seek(0);
		$this->rewindFunction = null;
		return true;
	}
} 