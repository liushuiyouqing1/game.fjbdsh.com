<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

class DefaultCanCacheStrategy implements CanCacheStrategyInterface
{
	public function canCacheRequest(RequestInterface $request)
	{
		if ($request->getMethod() != RequestInterface::GET && $request->getMethod() != RequestInterface::HEAD) {
			return false;
		}
		if ($request->hasHeader('Cache-Control') && $request->getHeader('Cache-Control')->hasDirective('no-store')) {
			return false;
		}
		return true;
	}

	public function canCacheResponse(Response $response)
	{
		return $response->isSuccessful() && $response->canCache();
	}
} 