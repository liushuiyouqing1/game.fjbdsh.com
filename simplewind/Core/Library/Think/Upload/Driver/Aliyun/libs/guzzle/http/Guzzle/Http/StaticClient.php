<?php
namespace Guzzle\Http;

use Guzzle\Http\Client;
use Guzzle\Http\ClientInterface;
use Guzzle\Stream\StreamRequestFactoryInterface;
use Guzzle\Stream\PhpStreamRequestFactory;

final class StaticClient
{
	private static $client;

	public static function mount($className = 'Guzzle', ClientInterface $client = null)
	{
		class_alias(__CLASS__, $className);
		if ($client) {
			self::$client = $client;
		}
	}

	public static function request($method, $url, $options = array())
	{
		if (!self::$client) {
			self::$client = new Client();
		}
		$request = self::$client->createRequest($method, $url, null, null, $options);
		if (isset($options['stream'])) {
			if ($options['stream'] instanceof StreamRequestFactoryInterface) {
				return $options['stream']->fromRequest($request);
			} elseif ($options['stream'] == true) {
				$streamFactory = new PhpStreamRequestFactory();
				return $streamFactory->fromRequest($request);
			}
		}
		return $request->send();
	}

	public static function get($url, $options = array())
	{
		return self::request('GET', $url, $options);
	}

	public static function head($url, $options = array())
	{
		return self::request('HEAD', $url, $options);
	}

	public static function delete($url, $options = array())
	{
		return self::request('DELETE', $url, $options);
	}

	public static function post($url, $options = array())
	{
		return self::request('POST', $url, $options);
	}

	public static function put($url, $options = array())
	{
		return self::request('PUT', $url, $options);
	}

	public static function patch($url, $options = array())
	{
		return self::request('PATCH', $url, $options);
	}

	public static function options($url, $options = array())
	{
		return self::request('OPTIONS', $url, $options);
	}
} 