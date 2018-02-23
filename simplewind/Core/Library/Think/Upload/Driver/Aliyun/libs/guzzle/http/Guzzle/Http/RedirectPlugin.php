<?php
namespace Guzzle\Http;

use Guzzle\Common\Event;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Url;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Exception\TooManyRedirectsException;
use Guzzle\Http\Exception\CouldNotRewindStreamException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RedirectPlugin implements EventSubscriberInterface
{
	const REDIRECT_COUNT = 'redirect.count';
	const MAX_REDIRECTS = 'redirect.max';
	const STRICT_REDIRECTS = 'redirect.strict';
	const PARENT_REQUEST = 'redirect.parent_request';
	const DISABLE = 'redirect.disable';
	protected $defaultMaxRedirects = 5;

	public static function getSubscribedEvents()
	{
		return array('request.sent' => array('onRequestSent', 100), 'request.clone' => 'cleanupRequest', 'request.before_send' => 'cleanupRequest');
	}

	public function cleanupRequest(Event $event)
	{
		$params = $event['request']->getParams();
		unset($params[self::REDIRECT_COUNT]);
		unset($params[self::PARENT_REQUEST]);
	}

	public function onRequestSent(Event $event)
	{
		$response = $event['response'];
		$request = $event['request'];
		if (!$response || $request->getParams()->get(self::DISABLE)) {
			return;
		}
		$original = $this->getOriginalRequest($request);
		if (!$response->isRedirect() || !$response->hasHeader('Location')) {
			if ($request !== $original) {
				$response->getParams()->set(self::REDIRECT_COUNT, $original->getParams()->get(self::REDIRECT_COUNT));
				$original->setResponse($response);
				$response->setEffectiveUrl($request->getUrl());
			}
			return;
		}
		$this->sendRedirectRequest($original, $request, $response);
	}

	protected function getOriginalRequest(RequestInterface $request)
	{
		$original = $request;
		while ($parent = $original->getParams()->get(self::PARENT_REQUEST)) {
			$original = $parent;
		}
		return $original;
	}

	protected function createRedirectRequest(RequestInterface $request, $statusCode, $location, RequestInterface $original)
	{
		$redirectRequest = null;
		$strict = $original->getParams()->get(self::STRICT_REDIRECTS);
		if ($request instanceof EntityEnclosingRequestInterface && !$strict && $statusCode <= 302) {
			$redirectRequest = RequestFactory::getInstance()->cloneRequestWithMethod($request, 'GET');
		} else {
			$redirectRequest = clone $request;
		}
		$redirectRequest->setIsRedirect(true);
		$redirectRequest->setResponseBody($request->getResponseBody());
		$location = Url::factory($location);
		if (!$location->isAbsolute()) {
			$originalUrl = $redirectRequest->getUrl(true);
			$originalUrl->getQuery()->clear();
			$location = $originalUrl->combine((string)$location);
		}
		$redirectRequest->setUrl($location);
		$redirectRequest->getEventDispatcher()->addListener('request.before_send', $func = function ($e) use (&$func, $request, $redirectRequest) {
			$redirectRequest->getEventDispatcher()->removeListener('request.before_send', $func);
			$e['request']->getParams()->set(RedirectPlugin::PARENT_REQUEST, $request);
		});
		if ($redirectRequest instanceof EntityEnclosingRequestInterface && $redirectRequest->getBody()) {
			$body = $redirectRequest->getBody();
			if ($body->ftell() && !$body->rewind()) {
				throw new CouldNotRewindStreamException('Unable to rewind the non-seekable entity body of the request after redirecting. cURL probably ' . 'sent part of body before the redirect occurred. Try adding acustom rewind function using on the ' . 'entity body of the request using setRewindFunction().');
			}
		}
		return $redirectRequest;
	}

	protected function prepareRedirection(RequestInterface $original, RequestInterface $request, Response $response)
	{
		$params = $original->getParams();
		$current = $params[self::REDIRECT_COUNT] + 1;
		$params[self::REDIRECT_COUNT] = $current;
		$max = isset($params[self::MAX_REDIRECTS]) ? $params[self::MAX_REDIRECTS] : $this->defaultMaxRedirects;
		if ($current > $max) {
			$this->throwTooManyRedirectsException($original, $max);
			return false;
		} else {
			return $this->createRedirectRequest($request, $response->getStatusCode(), trim($response->getLocation()), $original);
		}
	}

	protected function sendRedirectRequest(RequestInterface $original, RequestInterface $request, Response $response)
	{
		if ($redirectRequest = $this->prepareRedirection($original, $request, $response)) {
			try {
				$redirectRequest->send();
			} catch (BadResponseException $e) {
				$e->getResponse();
				if (!$e->getResponse()) {
					throw $e;
				}
			}
		}
	}

	protected function throwTooManyRedirectsException(RequestInterface $original, $max)
	{
		$original->getEventDispatcher()->addListener('request.complete', $func = function ($e) use (&$func, $original, $max) {
			$original->getEventDispatcher()->removeListener('request.complete', $func);
			$str = "{$max} redirects were issued for this request:\n" . $e['request']->getRawHeaders();
			throw new TooManyRedirectsException($str);
		});
	}
} 