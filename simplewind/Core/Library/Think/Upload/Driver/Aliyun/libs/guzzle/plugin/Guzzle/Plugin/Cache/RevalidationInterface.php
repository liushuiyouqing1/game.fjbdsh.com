<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

interface RevalidationInterface
{
	public function revalidate(RequestInterface $request, Response $response);

	public function shouldRevalidate(RequestInterface $request, Response $response);
} 