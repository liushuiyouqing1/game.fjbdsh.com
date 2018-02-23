<?php
namespace Guzzle\Stream;

use Guzzle\Http\Message\RequestInterface;

interface StreamRequestFactoryInterface
{
	public function fromRequest(RequestInterface $request, $context = array(), array $params = array());
} 