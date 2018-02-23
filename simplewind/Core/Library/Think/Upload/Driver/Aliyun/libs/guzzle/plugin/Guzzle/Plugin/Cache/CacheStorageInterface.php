<?php
namespace Guzzle\Plugin\Cache;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

interface CacheStorageInterface
{
	public function fetch(RequestInterface $request);

	public function cache(RequestInterface $request, Response $response);

	public function delete(RequestInterface $request);

	public function purge($url);
} 