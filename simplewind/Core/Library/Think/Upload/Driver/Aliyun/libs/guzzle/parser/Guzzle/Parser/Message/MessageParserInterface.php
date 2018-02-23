<?php
namespace Guzzle\Parser\Message;
interface MessageParserInterface
{
	public function parseRequest($message);

	public function parseResponse($message);
} 