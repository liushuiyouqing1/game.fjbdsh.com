<?php
namespace Guzzle\Common\Exception;
class ExceptionCollection extends \Exception implements GuzzleException, \IteratorAggregate, \Countable
{
	protected $exceptions = array();

	public function setExceptions(array $exceptions)
	{
		$this->exceptions = array();
		foreach ($exceptions as $exception) {
			$this->add($exception);
		}
		return $this;
	}

	public function add($e)
	{
		if ($this->message) {
			$this->message .= "\n";
		}
		if ($e instanceof self) {
			$this->message .= '(' . get_class($e) . ")";
			foreach (explode("\n", $e->getMessage()) as $message) {
				$this->message .= "\n    {$message}";
			}
		} elseif ($e instanceof \Exception) {
			$this->exceptions[] = $e;
			$this->message .= '(' . get_class($e) . ') ' . $e->getMessage();
		}
		return $this;
	}

	public function count()
	{
		return count($this->exceptions);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->exceptions);
	}

	public function getFirst()
	{
		return $this->exceptions ? $this->exceptions[0] : null;
	}
} 