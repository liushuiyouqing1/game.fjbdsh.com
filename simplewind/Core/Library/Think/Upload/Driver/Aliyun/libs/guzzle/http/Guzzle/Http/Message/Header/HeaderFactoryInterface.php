<?php
namespace Guzzle\Http\Message\Header;
interface HeaderFactoryInterface
{
	public function createHeader($header, $value = null);
} 