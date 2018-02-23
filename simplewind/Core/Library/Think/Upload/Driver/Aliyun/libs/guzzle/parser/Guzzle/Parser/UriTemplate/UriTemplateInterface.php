<?php
namespace Guzzle\Parser\UriTemplate;
interface UriTemplateInterface
{
	public function expand($template, array $variables);
} 