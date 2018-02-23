<?php
namespace Guzzle\Plugin\Cookie;

use Guzzle\Common\Event;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\CookieJar\CookieJarInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CookiePlugin implements EventSubscriberInterface
{
	protected $cookieJar;

	public function __construct(CookieJarInterface $cookieJar = null)
	{
		$this->cookieJar = $cookieJar ?: new ArrayCookieJar();
	}

	public static function getSubscribedEvents()
	{
		return array('request.before_send' => array('onRequestBeforeSend', 125), 'request.sent' => array('onRequestSent', 125));
	}

	public function getCookieJar()
	{
		return $this->cookieJar;
	}

	public function onRequestBeforeSend(Event $event)
	{
		$request = $event['request'];
		if (!$request->getParams()->get('cookies.disable')) {
			$request->removeHeader('Cookie');
			foreach ($this->cookieJar->getMatchingCookies($request) as $cookie) {
				$request->addCookie($cookie->getName(), $cookie->getValue());
			}
		}
	}

	public function onRequestSent(Event $event)
	{
		$this->cookieJar->addCookiesFromResponse($event['response'], $event['request']);
	}
} 