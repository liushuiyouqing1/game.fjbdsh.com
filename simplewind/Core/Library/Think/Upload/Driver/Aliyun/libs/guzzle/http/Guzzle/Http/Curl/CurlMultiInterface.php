<?php
namespace Guzzle\Http\Curl;

use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Common\Exception\ExceptionCollection;
use Guzzle\Http\Message\RequestInterface;

interface CurlMultiInterface extends \Countable, HasDispatcherInterface
{
	const POLLING_REQUEST = 'curl_multi.polling_request';
	const ADD_REQUEST = 'curl_multi.add_request';
	const REMOVE_REQUEST = 'curl_multi.remove_request';
	const MULTI_EXCEPTION = 'curl_multi.exception';
	const BLOCKING = 'curl_multi.blocking';

	public function add(RequestInterface $request);

	public function all();

	public function remove(RequestInterface $request);

	public function reset($hard = false);

	public function send();
} 