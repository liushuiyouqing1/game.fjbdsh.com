<?php
namespace Guzzle\Plugin\Oauth;

use Guzzle\Common\Event;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OauthPlugin implements EventSubscriberInterface
{
	protected $config;

	public function __construct($config)
	{
		$this->config = Collection::fromConfig($config, array('version' => '1.0', 'consumer_key' => 'anonymous', 'consumer_secret' => 'anonymous', 'signature_method' => 'HMAC-SHA1', 'signature_callback' => function ($stringToSign, $key) {
			return hash_hmac('sha1', $stringToSign, $key, true);
		}), array('signature_method', 'signature_callback', 'version', 'consumer_key', 'consumer_secret'));
	}

	public static function getSubscribedEvents()
	{
		return array('request.before_send' => array('onRequestBeforeSend', -1000));
	}

	public function onRequestBeforeSend(Event $event)
	{
		$timestamp = $this->getTimestamp($event);
		$request = $event['request'];
		$nonce = $this->generateNonce($request);
		$authorizationParams = array('oauth_callback' => $this->config['callback'], 'oauth_consumer_key' => $this->config['consumer_key'], 'oauth_nonce' => $nonce, 'oauth_signature' => $this->getSignature($request, $timestamp, $nonce), 'oauth_signature_method' => $this->config['signature_method'], 'oauth_timestamp' => $timestamp, 'oauth_token' => $this->config['token'], 'oauth_verifier' => $this->config['verifier'], 'oauth_version' => $this->config['version'],);
		$request->setHeader('Authorization', $this->buildAuthorizationHeader($authorizationParams));
		return $authorizationParams;
	}

	private function buildAuthorizationHeader($authorizationParams)
	{
		$authorizationString = 'OAuth ';
		foreach ($authorizationParams as $key => $val) {
			if ($val) {
				$authorizationString .= $key . '="' . urlencode($val) . '", ';
			}
		}
		return substr($authorizationString, 0, -2);
	}

	public function getSignature(RequestInterface $request, $timestamp, $nonce)
	{
		$string = $this->getStringToSign($request, $timestamp, $nonce);
		$key = urlencode($this->config['consumer_secret']) . '&' . urlencode($this->config['token_secret']);
		return base64_encode(call_user_func($this->config['signature_callback'], $string, $key));
	}

	public function getStringToSign(RequestInterface $request, $timestamp, $nonce)
	{
		$params = $this->getParamsToSign($request, $timestamp, $nonce);
		$params = $this->prepareParameters($params);
		$parameterString = new QueryString($params);
		$url = Url::factory($request->getUrl())->setQuery('')->setFragment(null);
		return strtoupper($request->getMethod()) . '&' . rawurlencode($url) . '&' . rawurlencode((string)$parameterString);
	}

	public function getParamsToSign(RequestInterface $request, $timestamp, $nonce)
	{
		$params = new Collection(array('oauth_consumer_key' => $this->config['consumer_key'], 'oauth_nonce' => $nonce, 'oauth_signature_method' => $this->config['signature_method'], 'oauth_timestamp' => $timestamp, 'oauth_token' => $this->config['token'], 'oauth_version' => $this->config['version']));
		if (array_key_exists('callback', $this->config) == true) {
			$params['oauth_callback'] = $this->config['callback'];
		}
		if (array_key_exists('verifier', $this->config) == true) {
			$params['oauth_verifier'] = $this->config['verifier'];
		}
		$params->merge($request->getQuery());
		if ($this->shouldPostFieldsBeSigned($request)) {
			$params->merge($request->getPostFields());
		}
		$params = $params->toArray();
		ksort($params);
		return $params;
	}

	public function shouldPostFieldsBeSigned($request)
	{
		if (!$this->config->get('disable_post_params') && $request instanceof EntityEnclosingRequestInterface && false !== strpos($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded')) {
			return true;
		}
		return false;
	}

	public function generateNonce(RequestInterface $request)
	{
		return sha1(uniqid('', true) . $request->getUrl());
	}

	public function getTimestamp(Event $event)
	{
		return $event['timestamp'] ?: time();
	}

	protected function prepareParameters($data)
	{
		ksort($data);
		foreach ($data as $key => &$value) {
			switch (gettype($value)) {
				case 'NULL':
					unset($data[$key]);
					break;
				case 'array':
					$data[$key] = self::prepareParameters($value);
					break;
				case 'boolean':
					$data[$key] = $value ? 'true' : 'false';
					break;
			}
		}
		return $data;
	}
} 