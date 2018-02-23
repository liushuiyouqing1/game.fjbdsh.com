<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Cache\CacheAdapterFactory;
use Guzzle\Cache\CacheAdapterInterface;
use Guzzle\Common\Event;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Version;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\Exception\CurlException;
use Doctrine\Common\Cache\ArrayCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CachePlugin implements EventSubscriberInterface
{
	protected $revalidation;
	protected $canCache;
	protected $storage;
	protected $autoPurge;

	public function __construct($options = null)
	{
		if (!is_array($options)) {
			if ($options instanceof CacheAdapterInterface) {
				$options = array('storage' => new DefaultCacheStorage($options));
			} elseif ($options instanceof CacheStorageInterface) {
				$options = array('storage' => $options);
			} elseif ($options) {
				$options = array('storage' => new DefaultCacheStorage(CacheAdapterFactory::fromCache($options)));
			} elseif (!class_exists('Doctrine\Common\Cache\ArrayCache')) {
				throw new InvalidArgumentException('No cache was provided and Doctrine is not installed');
			}
		}
		$this->autoPurge = isset($options['auto_purge']) ? $options['auto_purge'] : false;
		$this->storage = isset($options['storage']) ? $options['storage'] : new DefaultCacheStorage(new DoctrineCacheAdapter(new ArrayCache()));
		if (!isset($options['can_cache'])) {
			$this->canCache = new DefaultCanCacheStrategy();
		} else {
			$this->canCache = is_callable($options['can_cache']) ? new CallbackCanCacheStrategy($options['can_cache']) : $options['can_cache'];
		}
		$this->revalidation = isset($options['revalidation']) ? $options['revalidation'] : new DefaultRevalidation($this->storage, $this->canCache);
	}

	public static function getSubscribedEvents()
	{
		return array('request.before_send' => array('onRequestBeforeSend', -255), 'request.sent' => array('onRequestSent', 255), 'request.error' => array('onRequestError', 0), 'request.exception' => array('onRequestException', 0),);
	}

	public function onRequestBeforeSend(Event $event)
	{
		$request = $event['request'];
		$request->addHeader('Via', sprintf('%s GuzzleCache/%s', $request->getProtocolVersion(), Version::VERSION));
		if (!$this->canCache->canCacheRequest($request)) {
			switch ($request->getMethod()) {
				case 'PURGE':
					$this->purge($request);
					$request->setResponse(new Response(200, array(), 'purged'));
					break;
				case 'PUT':
				case 'POST':
				case 'DELETE':
				case 'PATCH':
					if ($this->autoPurge) {
						$this->purge($request);
					}
			}
			return;
		}
		if ($response = $this->storage->fetch($request)) {
			$params = $request->getParams();
			$params['cache.lookup'] = true;
			$response->setHeader('Age', time() - strtotime($response->getDate() ?: $response->getLastModified() ?: 'now'));
			if ($this->canResponseSatisfyRequest($request, $response)) {
				if (!isset($params['cache.hit'])) {
					$params['cache.hit'] = true;
				}
				$request->setResponse($response);
			}
		}
	}

	public function onRequestSent(Event $event)
	{
		$request = $event['request'];
		$response = $event['response'];
		if ($request->getParams()->get('cache.hit') === null && $this->canCache->canCacheRequest($request) && $this->canCache->canCacheResponse($response)) {
			$this->storage->cache($request, $response);
		}
		$this->addResponseHeaders($request, $response);
	}

	public function onRequestError(Event $event)
	{
		$request = $event['request'];
		if (!$this->canCache->canCacheRequest($request)) {
			return;
		}
		if ($response = $this->storage->fetch($request)) {
			$response->setHeader('Age', time() - strtotime($response->getLastModified() ?: $response->getDate() ?: 'now'));
			if ($this->canResponseSatisfyFailedRequest($request, $response)) {
				$request->getParams()->set('cache.hit', 'error');
				$this->addResponseHeaders($request, $response);
				$event['response'] = $response;
				$event->stopPropagation();
			}
		}
	}

	public function onRequestException(Event $event)
	{
		if (!$event['exception'] instanceof CurlException) {
			return;
		}
		$request = $event['request'];
		if (!$this->canCache->canCacheRequest($request)) {
			return;
		}
		if ($response = $this->storage->fetch($request)) {
			$response->setHeader('Age', time() - strtotime($response->getDate() ?: 'now'));
			if (!$this->canResponseSatisfyFailedRequest($request, $response)) {
				return;
			}
			$request->getParams()->set('cache.hit', 'error');
			$request->setResponse($response);
			$this->addResponseHeaders($request, $response);
			$event->stopPropagation();
		}
	}

	public function canResponseSatisfyRequest(RequestInterface $request, Response $response)
	{
		$responseAge = $response->calculateAge();
		$reqc = $request->getHeader('Cache-Control');
		$resc = $response->getHeader('Cache-Control');
		if ($reqc && $reqc->hasDirective('max-age') && $responseAge > $reqc->getDirective('max-age')) {
			return false;
		}
		if ($response->isFresh() === false) {
			$maxStale = $reqc ? $reqc->getDirective('max-stale') : null;
			if (null !== $maxStale) {
				if ($maxStale !== true && $response->getFreshness() < (-1 * $maxStale)) {
					return false;
				}
			} elseif ($resc && $resc->hasDirective('max-age') && $responseAge > $resc->getDirective('max-age')) {
				return false;
			}
		}
		if ($this->revalidation->shouldRevalidate($request, $response)) {
			try {
				return $this->revalidation->revalidate($request, $response);
			} catch (CurlException $e) {
				$request->getParams()->set('cache.hit', 'error');
				return $this->canResponseSatisfyFailedRequest($request, $response);
			}
		}
		return true;
	}

	public function canResponseSatisfyFailedRequest(RequestInterface $request, Response $response)
	{
		$reqc = $request->getHeader('Cache-Control');
		$resc = $response->getHeader('Cache-Control');
		$requestStaleIfError = $reqc ? $reqc->getDirective('stale-if-error') : null;
		$responseStaleIfError = $resc ? $resc->getDirective('stale-if-error') : null;
		if (!$requestStaleIfError && !$responseStaleIfError) {
			return false;
		}
		if (is_numeric($requestStaleIfError) && $response->getAge() - $response->getMaxAge() > $requestStaleIfError) {
			return false;
		}
		if (is_numeric($responseStaleIfError) && $response->getAge() - $response->getMaxAge() > $responseStaleIfError) {
			return false;
		}
		return true;
	}

	public function purge($url)
	{
		$url = $url instanceof RequestInterface ? $url->getUrl() : $url;
		$this->storage->purge($url);
	}

	protected function addResponseHeaders(RequestInterface $request, Response $response)
	{
		$params = $request->getParams();
		$response->setHeader('Via', sprintf('%s GuzzleCache/%s', $request->getProtocolVersion(), Version::VERSION));
		$lookup = ($params['cache.lookup'] === true ? 'HIT' : 'MISS') . ' from GuzzleCache';
		if ($header = $response->getHeader('X-Cache-Lookup')) {
			$values = $header->toArray();
			$values[] = $lookup;
			$response->setHeader('X-Cache-Lookup', array_unique($values));
		} else {
			$response->setHeader('X-Cache-Lookup', $lookup);
		}
		if ($params['cache.hit'] === true) {
			$xcache = 'HIT from GuzzleCache';
		} elseif ($params['cache.hit'] == 'error') {
			$xcache = 'HIT_ERROR from GuzzleCache';
		} else {
			$xcache = 'MISS from GuzzleCache';
		}
		if ($header = $response->getHeader('X-Cache')) {
			$values = $header->toArray();
			$values[] = $xcache;
			$response->setHeader('X-Cache', array_unique($values));
		} else {
			$response->setHeader('X-Cache', $xcache);
		}
		if ($response->isFresh() === false) {
			$response->addHeader('Warning', sprintf('110 GuzzleCache/%s "Response is stale"', Version::VERSION));
			if ($params['cache.hit'] === 'error') {
				$response->addHeader('Warning', sprintf('111 GuzzleCache/%s "Revalidation failed"', Version::VERSION));
			}
		}
	}
} 