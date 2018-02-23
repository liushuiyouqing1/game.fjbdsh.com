<?php

class PHPExcel_CalcEngine_Logger
{
	private $_writeDebugLog = FALSE;
	private $_echoDebugLog = FALSE;
	private $_debugLog = array();
	private $_cellStack;

	public function __construct(PHPExcel_CalcEngine_CyclicReferenceStack $stack)
	{
		$this->_cellStack = $stack;
	}

	public function setWriteDebugLog($pValue = FALSE)
	{
		$this->_writeDebugLog = $pValue;
	}

	public function getWriteDebugLog()
	{
		return $this->_writeDebugLog;
	}

	public function setEchoDebugLog($pValue = FALSE)
	{
		$this->_echoDebugLog = $pValue;
	}

	public function getEchoDebugLog()
	{
		return $this->_echoDebugLog;
	}

	public function writeDebugLog()
	{
		if ($this->_writeDebugLog) {
			$message = implode(func_get_args());
			$cellReference = implode(' -> ', $this->_cellStack->showStack());
			if ($this->_echoDebugLog) {
				echo $cellReference, ($this->_cellStack->count() > 0 ? ' => ' : ''), $message, PHP_EOL;
			}
			$this->_debugLog[] = $cellReference . ($this->_cellStack->count() > 0 ? ' => ' : '') . $message;
		}
	}

	public function clearLog()
	{
		$this->_debugLog = array();
	}

	public function getLog()
	{
		return $this->_debugLog;
	}
} 