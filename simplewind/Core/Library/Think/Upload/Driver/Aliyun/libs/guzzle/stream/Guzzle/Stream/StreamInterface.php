<?php
namespace Guzzle\Stream;
interface StreamInterface
{
	public function __toString();

	public function close();

	public function getMetaData($key = null);

	public function getStream();

	public function setStream($stream, $size = null);

	public function detachStream();

	public function getWrapper();

	public function getWrapperData();

	public function getStreamType();

	public function getUri();

	public function getSize();

	public function isReadable();

	public function isRepeatable();

	public function isWritable();

	public function isConsumed();

	public function feof();

	public function isLocal();

	public function isSeekable();

	public function setSize($size);

	public function seek($offset, $whence = SEEK_SET);

	public function read($length);

	public function write($string);

	public function ftell();

	public function rewind();

	public function readLine($maxLength = null);

	public function setCustomData($key, $value);

	public function getCustomData($key);
} 