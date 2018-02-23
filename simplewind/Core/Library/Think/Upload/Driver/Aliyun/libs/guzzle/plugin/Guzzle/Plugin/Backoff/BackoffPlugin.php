<?php
namespace Guzzle\Plugin\Backoff;

use Guzzle\Common\Event;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Curl\CurlMultiInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BackoffPlugin extends AbstractHasDispatcher implements EventSubscriberInterface
{
	const DELAY_PARAM = CurlMultiInterface::BLOCKING;
	const RETRY_PARAM = 'plugins.backoff.retry_count';
	const RETRY_EVENT = 'plugins.backoff.retry';
	protected $strategy;

	public function __construct(BackoffStrategyInterface $strategy = null)
	{
		$this->strategy = $strategy;
	}

	public static function getExponentialBackoff($maxRetries = 3, array $httpCodes = null, array $curlCodes = null)
	{
		return new self(new TruncatedBackoffStrategy($maxRetries, new HttpBackoffStrategy($httpCodes, new CurlBackoffStrategy($curlCodes, new ExponentialBackoffStrategy()))));
	}

	public static function getAllEvents()
	{
		return array(self::RETRY_EVENT);
	}

	public static function getSubscribedEvents()
	{
		return array('request.sent' => 'onRequestSent', 'request.exception' => 'onRequestSent', CurlMultiInterface::POLLING_REQUEST => 'onRequestPoll');
	}

	public function onRequestSent(Event $event)
	{
		$request = $event['request'];
		$response = $event['response'];
		$exception = $event['exception'];
		$params = $request->getParams();
		$retries = (int)$params->get(self::RETRY_PARAM);
		$delay = $this->strategy->getBackoffPeriod($retries, $request, $response, $exception);
		if ($delay !== false) {
			$params->set(self::RETRY_PARAM, ++$retries)->set(self::DELAY_PARAM, microtime(true) + $delay);
			$request->setState(RequestInterface::STATE_TRANSFER);
			$this->dispatch(self::RETRY_EVENT, array('request' => $request, 'response' => $response, 'handle' => $exception ? $exception->getCurlHandle() : null, 'retries' => $retries, 'delay' => $delay));
		}
	}

	public function onRequestPoll(Event $event)
	{
		$request = $event['request'];
		$delay = $request->getParams()->get(self::DELAY_PARAM);
		if (null !== $delay && microtime(true) >= $delay) {
			$request->getParams()->remove(self::DELAY_PARAM);
			if ($request instanceof EntityEnclosingRequestInterface && $request->getBody()) {
				$request->getBody()->seek(0);
			}
			$multi = $event['curl_multi'];
			$multi->remove($request);
			$multi->add($request);
		}
	}
} 