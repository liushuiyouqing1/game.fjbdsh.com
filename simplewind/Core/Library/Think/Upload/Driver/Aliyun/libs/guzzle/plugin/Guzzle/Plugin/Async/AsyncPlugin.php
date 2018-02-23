<?php
namespace Guzzle\Plugin\Async;

use Guzzle\Common\Event;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\CurlException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AsyncPlugin implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return array('request.before_send' => 'onBeforeSend', 'request.exception' => 'onRequestTimeout', 'request.sent' => 'onRequestSent', 'curl.callback.progress' => 'onCurlProgress');
	}

	public function onBeforeSend(Event $event)
	{
		$event['request']->getCurlOptions()->set('progress', true);
	}

	public function onCurlProgress(Event $event)
	{
		if ($event['handle'] && ($event['downloaded'] || ($event['uploaded'] && $event['upload_size'] === $event['uploaded']))) {
			curl_setopt($event['handle'], CURLOPT_TIMEOUT_MS, 1);
			curl_setopt($event['handle'], CURLOPT_NOBODY, true);
		}
	}

	public function onRequestTimeout(Event $event)
	{
		if ($event['exception'] instanceof CurlException) {
			$event['request']->setResponse(new Response(200, array('X-Guzzle-Async' => 'Did not wait for the response')));
		}
	}

	public function onRequestSent(Event $event)
	{
		$event['request']->getResponse()->setHeader('X-Guzzle-Async', 'Did not wait for the response');
	}
} 