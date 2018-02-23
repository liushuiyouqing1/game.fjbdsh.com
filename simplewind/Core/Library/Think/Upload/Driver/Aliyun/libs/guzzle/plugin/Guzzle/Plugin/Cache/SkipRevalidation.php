<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

class SkipRevalidation extends DefaultRevalidation
{
	public function __construct()
	{
	}

	public function revalidate(RequestInterface $request, Response $response)
	{
		return true;
	}
} 