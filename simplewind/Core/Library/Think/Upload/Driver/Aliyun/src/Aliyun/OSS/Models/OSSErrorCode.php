<?php
namespace Aliyun\OSS\Models;
class OSSErrorCode
{
	const ACCESS_DENIED = 'AccessDenied';
	const BUCKES_ALREADY_EXISTS = 'BucketAlreadyExists';
	const BUCKETS_NOT_EMPTY = 'BucketNotEmpty';
	const FILE_GROUP_TOO_LARGE = 'FileGroupTooLarge';
	const FILE_PART_STALE = 'FilePartStale';
	const INVALID_ARGUMENT = 'InvalidArgument';
	const INVALID_ACCESS_KEY_ID = 'InvalidAccessKeyId';
	const INVALID_BUCKET_NAME = 'InvalidBucketName';
	const INVALID_OBJECT_NAME = 'InvalidObjectName';
	const INVALID_PART = 'InvalidPart';
	const INVALID_PART_ORDER = 'InvalidPartOrder';
	const INTERNAL_ERROR = 'InternalError';
	const MISSING_CONTENT_LENGTH = 'MissingContentLength';
	const NO_SUCH_BUCKET = 'NoSuchBucket';
	const NO_SUCH_KEY = 'NoSuchKey';
	const NOT_IMPLEMENTED = 'NotImplemented';
	const PRECONDITION_FAILED = 'PreconditionFailed';
	const REQUEST_TIME_TOO_SKEWED = 'RequestTimeTooSkewed';
	const REQUEST_TIMEOUT = 'RequestTimeout';
	const SIGNATURE_DOES_NOT_MATCH = 'SignatureDoesNotMatch';
	const TOO_MANY_BUCKETS = 'TooManyBuckets';
}