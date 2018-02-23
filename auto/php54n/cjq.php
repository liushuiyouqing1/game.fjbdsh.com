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
$cjqid = $argv[1];
$task = new Worker();
$url = '';
$task->count = 1;
$connection2 = array();
$task->onWorkerStart = function ($task) {
	ouput('开始运行程序');
	global $host;
	global $taskid;
	global $connection2;
	$connection2 = new AsyncTcpConnection('ws://' . $host);
	$connection2->onConnect = function ($connection2) {
		global $cjqid;
		ouput('链接到主服务器');
		ouput('发送身份信息到主服务器');
		$data['act'] = 'connect';
		$data['cjq'] = $cjqid;
		$connection2->send(json_encode($data));
	};
	$connection2->onMessage = function ($connection2, $data) {
		global $db;
		global $url;
		$data2 = json_decode($data, true);
		if ($data2['act'] == 'start') {
			$db = new Mysql($data2['host']['hostname'], $data2['host']['username'], $data2['host']['password'], $data2['host']['dbname']);
			ouput('开始运行');
			$time_interval = 5;
			Timer::add($time_interval, "cjq");
			$url = 'http://' . $data2['url'] . '/index.php/Portal/Date/kj/id/';
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
function cjq()
{
	global $db;
	global $cjqid;
	global $url;
	global $connection2;
	$cjq = $db->getOne("select * from jz_cjq where id=" . $cjqid . " order by id desc");
	$cjq['gz'] = htmlspecialchars_decode($cjq['gz']);
	$cjq['fg'] = htmlspecialchars_decode($cjq['fg']);
	$data['yxtime'] = date('Y-m-d H:i:s', time());
	$data['token'] = time() + 30;
	$db->update('jz_cjq', $data, "id=" . $id);
	$context = geturl($cjq['url']);
	$list = array();
	preg_match_all($cjq['gz'], $context, $list);
	$kjdata['qs'] = $list[$cjq['qs']]['0'];
	$list[$cjq['sj']]['0'] = str_replace("\r\n", "", $list[$cjq['sj']]['0']);
	$sj = explode($cjq['fg'], $list[$cjq['sj']]['0']);
	$kjdata['sj'] = implode(',', $sj);
	ouput("期数：" . $kjdata['qs'] . "开奖号码：" . $kjdata['sj']);
	$data2['typeid'] = $cjq['typeid'];
	$data2['num'] = $kjdata['qs'];
	$lsxx = $db->getOne("select * from jz_data where typeid='" . $data2['typeid'] . "' and num='" . $data2['num'] . "' order by id desc");
	if (!$lsxx && $data2['num']) {
		$data2['data'] = $kjdata['sj'];
		$data2[time] = time();
		$id = $db->insert('jz_data', $data2);
		$data = array();
		$data['act'] = 'kjtz';
		$connection2->send(json_encode($data));
		ouput(geturl($url . $id));
	} else {
		ouput('该数据已经存在');
	}
}

function ouput($str)
{
	$zmm = mb_convert_encoding($str, "GB2312", "UTF-8");
	echo $zmm . "\r\n";
}

function geturl($url)
{
	$cip = mt_rand(0, 254) . '.' . mt_rand(0, 254) . '.' . mt_rand(0, 254) . '.' . mt_rand(0, 254);
	$xip = mt_rand(0, 254) . '.' . mt_rand(0, 254) . '.' . mt_rand(0, 254) . '.' . mt_rand(0, 254);
	$header = array('CLIENT-IP:' . $cip, 'X-FORWARDED-FOR:' . $xip,);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_REFERER, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)");
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
} ?>