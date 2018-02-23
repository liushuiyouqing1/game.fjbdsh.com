<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

interface CanCacheStrategyInterface
{
	public function canCacheRequest(RequestInterface $request);

	public function canCacheResponse(Response $response);
} 