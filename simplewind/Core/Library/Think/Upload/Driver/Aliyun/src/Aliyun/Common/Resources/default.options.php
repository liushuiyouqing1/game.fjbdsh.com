<?php
use \Aliyun\Common\Models\ServiceOptions;
use \Aliyun\Common\Utilities\ServiceConstants;

return array(ServiceOptions::MAX_ERROR_RETRY => 3, ServiceOptions::USER_AGENT => 'aliyun-sdk-php' . '/' . ServiceConstants::SDK_VERSION, ServiceOptions::CURL_OPTIONS => array(),); 