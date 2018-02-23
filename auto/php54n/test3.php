<?php
use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;

require_once __DIR__ . '/workerman/Autoloader.php';
error_reporting(E_ALL & ~E_NOTICE);
ini_set('date.timezone', 'Asia/Shanghai');
include 'mysql.class.php';
$db = array();
$host = '127.0.0.1:8001';
$taskid = $argv[1];
$task = new Worker();
$task->count = 1;
$task->onWorkerStart = function ($task) {
	ouput('开始运行程序');
	global $host;
	global $taskid;
	$connection2 = new AsyncTcpConnection('ws://' . $host);
	$connection2->onConnect = function ($connection2) {
		global $taskid;
		ouput('链接到主服务器');
		ouput('发送身份信息到主服务器');
		$data['act'] = 'connect';
		$data['task'] = $taskid;
		$connection2->send(json_encode($data));
	};
	$connection2->onMessage = function ($connection2, $data) {
		global $db;
		$data2 = json_decode($data, true);
		if ($data2['act'] == 'start') {
			$db = new Mysql($data2['host']['hostname'], $data2['host']['username'], $data2['host']['password'], $data2['host']['dbname']);
			ouput('开始运行');
			$time_interval = 30;
			Timer::add($time_interval, "opencjq");
		}
	};
	$connection2->onClose = function ($connection2) {
		ouput('到主服务器的链接关闭');
	};
	$connection2->onError = function ($connection2, $code, $msg) {
		ouput('到主服务器的链接错误' . $msg);
	};
	$connection2->connect();
};
Worker::runAll();
function opencjq()
{
	global $db;
	ouput('读取采集器');
	$cjqlist = $db->getAll("select * from jz_cjq where zt=1 and token=0 order by id desc");
	foreach ($cjqlist as $key => $value) {
		ouput('开启采集器' . $value['name']);
		$title = mb_convert_encoding($value[name] . '-' . $value[id], "GB2312", "UTF-8");
		$zmm = popen('opencjq.bat ' . $value['id'] . ' ' . $title, "r");
		$bonussql = $db->getOne("select * from jz_options where option_name='bonus'");
		$bonus = json_decode($bonussql['option_value'], true);
		ouput('清理采集数据');
		$db->del('jz_data', "time<" . (time() - 3600 * 24 * $bonus[blts]));
	}
}

function ouput($str)
{
	$zmm = mb_convert_encoding($str, "GB2312", "UTF-8");
	echo $zmm . "\r\n";
} ?>