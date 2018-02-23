<?php
namespace Guzzle\Plugin\Backoff;
abstract class AbstractErrorCodeBackoffStrategy extends AbstractBackoffStrategy
{
	protected static $defaultErrorCodes = array();
	protected $errorCodes;

	public function __construct(array $codes = null, BackoffStrategyInterface $next = null)
	{
		$this->errorCodes = array_fill_keys($codes ?: static::$defaultErrorCodes, 1);
		$this->next = $next;
	}

	public static function getDefaultFailureCodes()
	{
		return static::$defaultErrorCodes;
	}

	public function makesDecision()
	{
		return true;
	}
} 