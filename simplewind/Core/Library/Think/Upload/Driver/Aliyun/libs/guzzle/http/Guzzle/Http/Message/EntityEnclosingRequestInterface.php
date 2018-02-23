<?php
namespace Guzzle\Http\Message;

use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\QueryString;

interface EntityEnclosingRequestInterface extends RequestInterface
{
	const URL_ENCODED = 'application/x-www-form-urlencoded; charset=utf-8';
	const MULTIPART = 'multipart/form-data';

	public function setBody($body, $contentType = null);

	public function getBody();

	public function getPostField($field);

	public function getPostFields();

	public function setPostField($key, $value);

	public function addPostFields($fields);

	public function removePostField($field);

	public function getPostFiles();

	public function getPostFile($fieldName);

	public function removePostFile($fieldName);

	public function addPostFile($field, $filename = null, $contentType = null);

	public function addPostFiles(array $files);

	public function configureRedirects($strict = false, $maxRedirects = 5);
} 