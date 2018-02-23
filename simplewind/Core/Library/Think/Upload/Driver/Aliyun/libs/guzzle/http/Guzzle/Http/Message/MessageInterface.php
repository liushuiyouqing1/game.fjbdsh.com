<?php
namespace Guzzle\Http\Message;
interface MessageInterface
{
	public function getParams();

	public function addHeader($header, $value);

	public function addHeaders(array $headers);

	public function getHeader($header);

	public function getHeaders();

	public function hasHeader($header);

	public function removeHeader($header);

	public function setHeader($header, $value);

	public function setHeaders(array $headers);

	public function getHeaderLines();

	public function getRawHeaders();
} 