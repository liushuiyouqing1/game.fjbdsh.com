<?php
namespace Guzzle\Http\QueryAggregator;

use Guzzle\Http\QueryString;

class DuplicateAggregator implements QueryAggregatorInterface
{
	public function aggregate($key, $value, QueryString $query)
	{
		if ($query->isUrlEncoding()) {
			return array($query->encodeValue($key) => array_map(array($query, 'encodeValue'), $value));
		} else {
			return array($key => $value);
		}
	}
} 