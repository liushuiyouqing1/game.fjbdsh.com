<?php
namespace Guzzle\Http;

use Guzzle\Stream\StreamInterface;

interface EntityBodyInterface extends StreamInterface
{
	public function setRewindFunction($callable);

	public function compress($filter = 'zlib.deflate');

	public function uncompress($filter = 'zlib.inflate');

	public function getContentLength();

	public function getContentType();

	public function getContentMd5($rawOutput = false, $base64Encode = false);

	public function getContentEncoding();
} 