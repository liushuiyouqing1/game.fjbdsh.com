<?php
namespace Aliyun\Common\Auth;

use Aliyun\Common\Communication\HttpRequest;

interface SignerInterface
{
	public function sign(HttpRequest $request, array $credentials);
} 