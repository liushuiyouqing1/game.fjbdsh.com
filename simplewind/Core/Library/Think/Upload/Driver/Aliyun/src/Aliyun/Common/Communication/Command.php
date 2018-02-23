<?php
namespace Aliyun\Common\Communication;

use Aliyun\Common\Exceptions\ClientException;
use Aliyun\Common\Exceptions\ServiceException;

abstract class Command
{
	protected $service;
	protected $name;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getService()
	{
		return $this->service;
	}

	public function setService($service)
	{
		$this->service = $service;
	}

	protected function leaveRequestOpen($options)
	{
		return false;
	}

	protected function leaveResponseOpen($options)
	{
		return false;
	}

	protected function commandOptions()
	{
		return array();
	}

	protected function afterResult($result, $options)
	{
		return $result;
	}

	protected function checkOptions($options)
	{
		return $options;
	}

	abstract protected function getRequest($options);

	abstract protected function getContext($options);

	abstract protected function parseResponse(HttpResponse $response, $options);

	public function execute($clientOptions, $userOptions)
	{
		$request = null;
		$response = null;
		$result = null;
		$options = $this->checkOptions(array_merge($clientOptions, $this->commandOptions(), $userOptions));
		try {
			$context = $this->getContext($options);
			$request = $this->getRequest($options);
			$response = $this->service->sendRequest($request, $context);
			$result = $this->afterResult($this->parseResponse($response, $options), $options);
			$this->handleStream($request, $response, $options);
		} catch (\Exception $ex) {
			$this->handleStream($request, $response, $options);
			if ($ex instanceof ServiceException || $ex instanceof ClientException) {
				throw $ex;
			}
			throw new ClientException($ex->getMessage(), $ex);
		}
		return $result;
	}

	private function handleStream($request, $response, $options)
	{
		if (!$this->leaveResponseOpen($options) && $response instanceof HttpResponse) {
			$response->close();
		}
		if (!$this->leaveRequestOpen($options) && $request instanceof HttpRequest) {
			$request->close();
		}
	}
} 