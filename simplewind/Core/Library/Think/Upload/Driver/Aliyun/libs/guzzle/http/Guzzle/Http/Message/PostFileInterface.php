<?php
namespace Guzzle\Http\Message;

use Guzzle\Common\Exception\InvalidArgumentException;

interface PostFileInterface
{
	public function setFieldName($name);

	public function getFieldName();

	public function setFilename($path);

	public function getFilename();

	public function setContentType($type);

	public function getContentType();

	public function getCurlValue();
} 