<?php
namespace Guzzle\Http\QueryAggregator;

use Guzzle\Http\QueryString;

interface QueryAggregatorInterface
{
	public function aggregate($key, $value, QueryString $query);
} 