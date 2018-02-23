<?php
namespace Guzzle\Plugin\CurlAuth;

use Guzzle\Common\Event;
use Guzzle\Common\Version;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CurlAuthPlugin implements EventSubscriberInterface
{
	private $username;
	private $password;
	private $scheme;

	public function __construct($username, $password, $scheme = CURLAUTH_BASIC)
	{
		Version::warn(__CLASS__ . " is deprecated. Use \$client->getConfig()->setPath('request.options/auth', array('user', 'pass', 'Basic|Digest');");
		$this->username = $username;
		$this->password = $password;
		$this->scheme = $scheme;
	}

	public static function getSubscribedEvents()
	{
		return array('client.create_request' => array('onRequestCreate', 255));
	}

	public function onRequestCreate(Event $event)
	{
		$event['request']->setAuth($this->username, $this->password, $this->scheme);
	}
} 