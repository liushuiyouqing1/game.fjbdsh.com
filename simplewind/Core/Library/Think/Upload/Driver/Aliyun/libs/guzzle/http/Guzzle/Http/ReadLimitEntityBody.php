<?php
namespace Guzzle\Http;
class ReadLimitEntityBody extends AbstractEntityBodyDecorator
{
	protected $limit;
	protected $offset;

	public function __construct(EntityBodyInterface $body, $limit, $offset = 0)
	{
		parent::__construct($body);
		$this->setLimit($limit)->setOffset($offset);
		$this->body->seek($offset);
	}

	public function __toString()
	{
		return substr((string)$this->body, $this->offset, $this->limit) ?: '';
	}

	public function isConsumed()
	{
		return (($this->offset + $this->limit) - $this->body->ftell()) <= 0;
	}

	public function getContentLength()
	{
		$length = $this->body->getContentLength();
		return $length === false ? $this->limit : min($this->limit, min($length, $this->offset + $this->limit) - $this->offset);
	}

	public function seek($offset, $whence = SEEK_SET)
	{
		return $whence === SEEK_SET ? $this->body->seek(max($this->offset, min($this->offset + $this->limit, $offset))) : false;
	}

	public function setOffset($offset)
	{
		$this->body->seek($offset);
		$this->offset = $offset;
		return $this;
	}

	public function setLimit($limit)
	{
		$this->limit = $limit;
		return $this;
	}

	public function read($length)
	{
		$remaining = ($this->offset + $this->limit) - $this->body->ftell();
		if ($remaining > 0) {
			return $this->body->read(min($remaining, $length));
		} else {
			return false;
		}
	}
} 