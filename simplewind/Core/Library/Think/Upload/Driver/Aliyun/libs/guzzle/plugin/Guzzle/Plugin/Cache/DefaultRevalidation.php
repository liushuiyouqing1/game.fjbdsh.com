<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\BadResponseException;

class DefaultRevalidation implements RevalidationInterface
{
	protected $storage;
	protected $canCache;

	public function __construct(CacheStorageInterface $cache, CanCacheStrategyInterface $canCache = null)
	{
		$this->storage = $cache;
		$this->canCache = $canCache ?: new DefaultCanCacheStrategy();
	}

	public function revalidate(RequestInterface $request, Response $response)
	{
		try {
			$revalidate = $this->createRevalidationRequest($request, $response);
			$validateResponse = $revalidate->send();
			if ($validateResponse->getStatusCode() == 200) {
				return $this->handle200Response($request, $validateResponse);
			} elseif ($validateResponse->getStatusCode() == 304) {
				return $this->handle304Response($request, $validateResponse, $response);
			}
		} catch (BadResponseException $e) {
			$this->handleBadResponse($e);
		}
		return false;
	}

	public function shouldRevalidate(RequestInterface $request, Response $response)
	{
		if ($request->getMethod() != RequestInterface::GET) {
			return false;
		}
		$reqCache = $request->getHeader('Cache-Control');
		$resCache = $response->getHeader('Cache-Control');
		$revalidate = $request->getHeader('Pragma') == 'no-cache' || ($reqCache && ($reqCache->hasDirective('no-cache') || $reqCache->hasDirective('must-revalidate'))) || ($resCache && ($resCache->hasDirective('no-cache') || $resCache->hasDirective('must-revalidate')));
		if (!$revalidate && !$reqCache && $response->hasHeader('ETag')) {
			$revalidate = true;
		}
		return $revalidate;
	}

	protected function handleBadResponse(BadResponseException $e)
	{
		if ($e->getResponse()->getStatusCode() == 404) {
			$this->storage->delete($e->getRequest());
			throw $e;
		}
	}

	protected function createRevalidationRequest(RequestInterface $request, Response $response)
	{
		$revalidate = clone $request;
		$revalidate->removeHeader('Pragma')->removeHeader('Cache-Control')->setHeader('If-Modified-Since', $response->getLastModified() ?: $response->getDate());
		if ($response->getEtag()) {
			$revalidate->setHeader('If-None-Match', '"' . $response->getEtag() . '"');
		}
		$dispatcher = $revalidate->getEventDispatcher();
		foreach ($dispatcher->getListeners() as $eventName => $listeners) {
			foreach ($listeners as $listener) {
				if ($listener[0] instanceof CachePlugin) {
					$dispatcher->removeListener($eventName, $listener);
				}
			}
		}
		return $revalidate;
	}

	protected function handle200Response(RequestInterface $request, Response $validateResponse)
	{
		$request->setResponse($validateResponse);
		if ($this->canCache->canCacheResponse($validateResponse)) {
			$this->storage->cache($request, $validateResponse);
		}
		return false;
	}

	protected function handle304Response(RequestInterface $request, Response $validateResponse, Response $response)
	{
		static $replaceHeaders = array('Date', 'Expires', 'Cache-Control', 'ETag', 'Last-Modified');
		if ($validateResponse->getEtag() != $response->getEtag()) {
			return false;
		}
		$modified = false;
		foreach ($replaceHeaders as $name) {
			if ($validateResponse->hasHeader($name)) {
				$modified = true;
				$response->setHeader($name, $validateResponse->getHeader($name));
			}
		}
		if ($modified && $this->canCache->canCacheResponse($response)) {
			$this->storage->cache($request, $response);
		}
		return true;
	}
} 