<?php
namespace Guzzle\Http;

use Guzzle\Common\Exception\RuntimeException;

class CachingEntityBody extends AbstractEntityBodyDecorator
{
	protected $remoteStream;
	protected $skipReadBytes = 0;

	public function __construct(EntityBodyInterface $body)
	{
		$this->remoteStream = $body;
		$this->body = new EntityBody(fopen('php://temp', 'r+'));
	}

	public function __toString()
	{
		$pos = $this->ftell();
		$this->rewind();
		$str = '';
		while (!$this->isConsumed()) {
			$str .= $this->read(16384);
		}
		$this->seek($pos);
		return $str;
	}

	public function getSize()
	{
		return max($this->body->getSize(), $this->remoteStream->getSize());
	}

	public function seek($offset, $whence = SEEK_SET)
	{
		if ($whence == SEEK_SET) {
			$byte = $offset;
		} elseif ($whence == SEEK_CUR) {
			$byte = $offset + $this->ftell();
		} else {
			throw new RuntimeException(__CLASS__ . ' supports only SEEK_SET and SEEK_CUR seek operations');
		}
		if ($byte > $this->body->getSize()) {
			throw new RuntimeException("Cannot seek to byte {$byte} when the buffered stream only contains {$this->body->getSize()} bytes");
		}
		return $this->body->seek($byte);
	}

	public function rewind()
	{
		return $this->seek(0);
	}

	public function setRewindFunction($callable)
	{
		throw new RuntimeException(__CLASS__ . ' does not support custom stream rewind functions');
	}

	public function read($length)
	{
		$data = $this->body->read($length);
		$remaining = $length - strlen($data);
		if ($remaining) {
			$remoteData = $this->remoteStream->read($remaining + $this->skipReadBytes);
			if ($this->skipReadBytes) {
				$len = strlen($remoteData);
				$remoteData = substr($remoteData, $this->skipReadBytes);
				$this->skipReadBytes = max(0, $this->skipReadBytes - $len);
			}
			$data .= $remoteData;
			$this->body->write($remoteData);
		}
		return $data;
	}

	public function write($string)
	{
		$overflow = (strlen($string) + $this->ftell()) - $this->remoteStream->ftell();
		if ($overflow > 0) {
			$this->skipReadBytes += $overflow;
		}
		return $this->body->write($string);
	}

	public function readLine($maxLength = null)
	{
		$buffer = '';
		$size = 0;
		while (!$this->isConsumed()) {
			$byte = $this->read(1);
			$buffer .= $byte;
			if ($byte == PHP_EOL || ++$size == $maxLength - 1) {
				break;
			}
		}
		return $buffer;
	}

	public function isConsumed()
	{
		return $this->body->isConsumed() && $this->remoteStream->isConsumed();
	}

	public function close()
	{
		return $this->remoteStream->close() && $this->body->close();
	}

	public function setStream($stream, $size = 0)
	{
		$this->remoteStream->setStream($stream, $size);
	}

	public function getContentType()
	{
		return $this->remoteStream->getContentType();
	}

	public function getContentEncoding()
	{
		return $this->remoteStream->getContentEncoding();
	}

	public function getMetaData($key = null)
	{
		return $this->remoteStream->getMetaData($key);
	}

	public function getStream()
	{
		return $this->remoteStream->getStream();
	}

	public function getWrapper()
	{
		return $this->remoteStream->getWrapper();
	}

	public function getWrapperData()
	{
		return $this->remoteStream->getWrapperData();
	}

	public function getStreamType()
	{
		return $this->remoteStream->getStreamType();
	}

	public function getUri()
	{
		return $this->remoteStream->getUri();
	}

	public function getCustomData($key)
	{
		return $this->remoteStream->getCustomData($key);
	}

	public function setCustomData($key, $value)
	{
		$this->remoteStream->setCustomData($key, $value);
		return $this;
	}
} 