<?php
use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Connection\AsyncTcpConnection;

require_once __DIR__ . '/workerman/Autoloader.php';
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('PRC');
include 'mysql.class.php';
$host = '127.0.0.1:8001';
$taskid = $argv[1];
$dk = $argv[2];
$db = array();
echo $dk;
$server = array();
ouput("读取配置");
$typelist = array();
$yslist = array();
$worker = new Worker('websocket://0.0.0.0:' . $dk);
$worker->uidConnections = array();
function sfzj()
{
	global $typelist;
	global $db;
	global $worker;
	global $yslist;
	$type = $db->getAll("select * from jz_order_menu where parentid=0 order by id desc");
	$bonussql = $db->getOne("select * from jz_options where option_name='bonus'");
	$bonus = json_decode($bonussql['option_value'], true);
	if ($bonus['sfts']) {
		$where = " option_name='bonus' ";
		$bonus['sfts'] = 0;
		$map = array();
		$map['option_value'] = json_encode($bonus, JSON_UNESCAPED_UNICODE);
		$db->update("jz_options", $map, $where);
		foreach ($worker->connections as $connection) {
			$send['msg'] = $bonus['tsxx'];
			$send['act'] = 'zxtz';
			$connection->send(json_encode($send));
		}
	}
	if ($type) {
		foreach ($type as $key => $value) {
			$data = $db->getOne("select * from jz_data where typeid=" . $value[id] . " order by id desc");
			if ($typelist[$value[id]] != $yslist[$value[id]]['num']) {
				$typelist[$value[id]] = $yslist[$value[id]]['num'];
				ouput($type[title] . $data['num'] . '期开奖并发送通知');
				if ($worker->connections) {
					foreach ($worker->connections as $connection) {
						$send['msg'] = $value[id];
						$send['act'] = 'kjtz';
						$connection->send(json_encode($send));
					}
				}
			}
			$yslist[$value[id]] = $data;
		}
	}
}

$worker->onWorkerStart = function ($worker) {
	ouput('程序开始运行');
	global $host;
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
		global $taskid;
		global $server;
		$data2 = json_decode($data, true);
		if ($data2['act'] == 'start') {
			$db = new Mysql($data2['host']['hostname'], $data2['host']['username'], $data2['host']['password'], $data2['host']['dbname']);
			$server = $db->getOne("select * from jz_server where id='" . $taskid . "'");
			start();
		}
		$server = $db->getOne("select * from jz_server where id='" . $taskid . "'");
	};
	$connection2->onClose = function ($connection2) {
		ouput('到主服务器的链接关闭');
	};
	$connection2->onError = function ($connection2, $code, $msg) {
		ouput('到主服务器的链接错误' . $msg);
	};
	$connection2->connect();
};
$worker->onClose = function ($connection) {
	global $db;
	global $title;
	if (isset($connection->uid)) {
		$bonussql = $db->getOne("select * from jz_options where option_name='bonus'");
		$bonus = json_decode($bonussql['option_value'], true);
		$map2 = array();
		$map2[zt] = 0;
		$where = ' uid=' . $connection->uid;
		$db->update("jz_online", $map2, $where);
		$online = $db->getAll("select *  from jz_online where zt='1'  group by uid order by id desc");
		$zronline = $db->getAll("select *  from jz_online where   time<'" . strtotime(date('Y-m-d 00:00:00', time())) . "' and time>'" . strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))) . "' group by uid order by id desc");
		$jronline = $db->getAll("select *  from jz_online where  time>'" . strtotime(date('Y-m-d 00:00:00', time())) . "' group by uid order by id desc ");
		$bonus['zxonline'] = count($online) + 0;
		$bonus['zronline'] = count($zronline) + 0;
		$bonus['jronline'] = count($jronline) + 0;
		$bonus['gxtime'] = date('Y-m-d H:i:s', time());
		$where = " option_name='bonus' ";
		$map = array();
		$map['option_value'] = json_encode($bonus, JSON_UNESCAPED_UNICODE);
		$db->update("jz_options", $map, $where);
	}
	$server = $db->getOne("select * from jz_server where title='" . $title . "'");
	$map = array();
	$map['zt'] = 1;
	$map['num'] = $server['num'] - 1;
	$db->update('jz_server', $map, "id=" . $server['id']);
};
$worker->onConnect = function ($connection) {
	global $db;
	global $title;
	ouput("新的链接ip为 " . $connection->getRemoteIp());
	$server = $db->getOne("select * from jz_server where title='" . $title . "'");
	$map['zt'] = 1;
	$map['num'] = $server['num'] + 1;
	$db->update('jz_server', $map, "id=" . $server['id']);
};
$worker->onMessage = function ($connection, $data) {
	global $db;
	global $bonus;
	global $extract;
	print_r($data);
	$data2 = json_decode($data, true);
	reqact($data2, $connection);
	try {
		global $db;
		global $url;
		global $worker;
		global $title;
		$data2 = json_decode($data, true);
		$fyzym = array('index', 'register', 'dologin', 'doreg', 'recharge', 'docharge', 'kefu', 'H5BB71183');
		$bonussql = $db->getOne("select * from jz_options where option_name='bonus'");
		$bonus = json_decode($bonussql['option_value'], true);
		if (!in_array($data2['act'], $fyzym)) {
			if ($data2['salt'] != md5(md5($data2['userid'] . 'zmm') . 'zmm')) {
				loginout('信息验证有误,请重新登陆');
			} else {
				$user = $db->getOne("select * from jz_user where id='" . $data2['userid'] . "' order by id desc limit 1");
				if (time() > strtotime($user['due_time'])) {
					loginout('您的时间已到期,请联系客服充值');
				}
				if ($user[status] == '1') {
					loginout('您已经被封号');
				}
				if ($user[status] == '2') {
					loginout('您已经被限制登陆');
				}
				$token = $db->getOne("select * from jz_online where uid='" . $data2['userid'] . "' and ms='" . $data2['ms'] . "' order by id desc limit 1");
				if (!isset($connection->uid)) {
					$map2 = array();
					$map2[zt] = 0;
					$where = ' uid=' . $data2['userid'];
					$db->update("jz_online", $map2, $where);
					$connection->uid = $data2['userid'];
					$connection->token = md5(time());
					$map = array();
					$map[uid] = $data2['userid'];
					$map[ms] = $data2['ms'];
					$map[token] = $connection->token;
					$map[time] = time();
					$map[zt] = 1;
					$map[xl] = $title;
					$db->insert('jz_online', $map);
					$online = $db->getAll("select *  from jz_online where zt='1'  group by uid order by id desc");
					$zronline = $db->getAll("select *  from jz_online where   time<'" . strtotime(date('Y-m-d 00:00:00', time())) . "' and time>'" . strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))) . "' group by uid order by id desc");
					$jronline = $db->getAll("select *  from jz_online where  time>'" . strtotime(date('Y-m-d 00:00:00', time())) . "' group by uid order by id desc ");
					$bonus['zxonline'] = count($online) + 0;
					$bonus['zronline'] = count($zronline) + 0;
					$bonus['jronline'] = count($jronline) + 0;
					$bonus['gxtime'] = date('Y-m-d H:i:s', time());
					$where = " option_name='bonus' ";
					$map = array();
					$map['option_value'] = json_encode($bonus, JSON_UNESCAPED_UNICODE);
					$db->update("jz_options", $map, $where);
				} elseif ($token['token'] != $connection->token) {
					unset($connection->uid);
					loginout('您已经在其他地方登陆了');
				} elseif ($token[time] < strtotime(date('Y-m-d 00:00:00', time()))) {
					$map2 = array();
					$map2[zt] = 0;
					$where = ' uid=' . $data2['userid'];
					$db->update("jz_online", $map2, $where);
					$connection->token = md5(time());
					$map = array();
					$map[uid] = $data2['userid'];
					$map[ms] = $data2['ms'];
					$map[token] = $connection->token;
					$map[time] = time();
					$map[zt] = 1;
					$map[xl] = $title;
					$db->insert('jz_online', $map);
					$online = $db->getAll("select *  from jz_online where zt='1'  group by uid order by id desc");
					$zronline = $db->getAll("select *  from jz_online where   time<'" . strtotime(date('Y-m-d 00:00:00', time())) . "' and time>'" . strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))) . "' group by uid order by id desc");
					$jronline = $db->getAll("select *  from jz_online where  time>'" . strtotime(date('Y-m-d 00:00:00', time())) . "' group by uid order by id desc ");
					$bonus['zxonline'] = count($online) + 0;
					$bonus['zronline'] = count($zronline) + 0;
					$bonus['jronline'] = count($jronline) + 0;
					$bonus['gxtime'] = date('Y-m-d H:i:s', time());
					$where = " option_name='bonus' ";
					$map = array();
					$map['option_value'] = json_encode($bonus, JSON_UNESCAPED_UNICODE);
					$db->update("jz_options", $map, $where);
				}
			}
		}
		if ($data2['act'] == 'ylfx') {
			$list = $db->getAll("select * from jz_data where typeid='" . $data2['typeid'] . "' order by id desc limit " . $data2['qs']);
			$zshow = array();
			$zylshow = array();
			$zylshow2 = array();
			$zljshow = array();
			$zljshow2 = array();
			$sfshow = array();
			$datalist = explode(',', $data2['sz']);
			$html = '';
			foreach ($datalist as $key => $value) {
				$html = $html . '<span class="fl ng-binding">' . $value . '</span>';
			}
			$act = 'html';
			$msg[id] = 'tjsz';
			$msg[html] = $html;
			action($act, $msg, $connection);
			$html = '';
			foreach ($list as $key => $value) {
				$datasj = explode(',', $value['data']);
				$html = $html . '<li class="">
            <div class="fl ng-binding">' . $value[num] . '</div>
            <div class="fl ng-binding">' . $datasj[$data2['ws']] . '</div>
            <div class="fl">';
				foreach ($datalist as $key2 => $value2) {
					if ($value2 == $datasj[$data2['ws']]) {
						$zshow[$value2] = $zshow[$value2] + 1;
						if ($sfshow[$value2] == 1) {
							$zljshow2[$value2] = $zljshow2[$value2] + 1;
						} else {
							$zljshow2[$value2] = 1;
						}
						if ($zljshow[$value2] < $zljshow2[$value2]) {
							$zljshow[$value2] = $zljshow2[$value2];
						}
						$sfshow[$value2] = 1;
						$html = $html . '<span class="fl"><span class="ng-binding span-active">' . $value2 . '</span></span>';
					} else {
						if ($sfshow[$value2] == 0) {
							$zylshow2[$value2] = $zylshow2[$value2] + 1;
						} else {
							$zylshow2[$value2] = 1;
						}
						if ($zylshow[$value2] < $zylshow2[$value2]) {
							$zylshow[$value2] = $zylshow2[$value2];
						}
						$sfshow[$value2] = 0;
						$html = $html . '<span class="fl"><span class="ng-binding">' . $value2 . '</span></span>';
					}
				}
				$html = $html . '</div></li>';
			}
			$msg[id] = 'ul';
			$msg[html] = $html;
			action($act, $msg, $connection);
			$html = '';
			foreach ($datalist as $key => $value) {
				$html = $html . '<span class="fl ng-binding">' . ($zshow[$value] + 0) . '</span>';
			}
			$msg[id] = 'zxs';
			$msg[html] = $html;
			action($act, $msg, $connection);
			$html = '';
			foreach ($datalist as $key => $value) {
				$html = $html . '<span class="fl ng-binding">' . ($zylshow[$value] + 0) . '</span>';
			}
			$msg[id] = 'zyl';
			$msg[html] = $html;
			action($act, $msg, $connection);
			$html = '';
			foreach ($datalist as $key => $value) {
				$html = $html . '<span class="fl ng-binding">' . ($zljshow[$value] + 0) . '</span>';
			}
			$msg[id] = 'zlj';
			$msg[html] = $html;
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'kefu') {
			$act = 'html';
			$msg[id] = 'area';
			$msg[html] = '<div id="ewm">
            <a href="http://' . $bonus['url'] . '/data/upload/' . $bonus['img'] . '" download="">
                <img width="100" height="100" class="wx" src="http://' . $bonus['url'] . '/data/upload/' . $bonus['img'] . '"></a>
            <p>微信请点击上方二维码保存</p>
        </div>
        <div>
            <img src="img/qq.jpg">
            <p class="ng-binding">QQ联系方式：' . $bonus['qq'] . '<span id="qqxx"></span></p>
        </div>';
			act($act, $msg);
		}
		if ($data2['act'] == 'location') {
			if (!$data2['typeid']) {
				$data2['typeid'] = '15';
			}
			$list = $db->getAll("select * from jz_order_menu where parentid='" . $data2['typeid'] . "' order by listorder asc");
			$type = $db->getOne("select * from jz_order_menu where id='" . $data2['typeid'] . "' order by listorder asc");
			$act = 'html';
			$msg[id] = 'location';
			$msg[html] = '';
			if ($data2['typeid'] != '15' && $data2['typeid'] != '16') {
				foreach ($list as $key => $value) {
					$msg['html'] = $msg['html'] . '<div class="col col-20" onclick="sort(' . $value['id'] . ')"><button>' . $value['name'] . '</button></div>';
				}
				$msg['html'] = $msg['html'] . '<div class="col col-20" onclick="order_location(' . $type['parentid'] . ')"><button style="background: #e09422;">返回</button></div>';
			} else {
				foreach ($list as $key => $value) {
					$msg['html'] = $msg['html'] . '<div class="col col-20" onclick="order_location(' . $value['id'] . ')"><button>' . $value['name'] . '</button></div>';
				}
			}
			action($act, $msg, $connection);
			$act = 'tzwz';
			$msg = '';
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'sort') {
			$list = $db->getAll("select * from jz_plan where sort='" . $data2['typeid'] . "' order by id desc");
			$act = 'html';
			$msg[id] = 'sort';
			$msg[html] = '';
			foreach ($list as $key => $value) {
				$msg['html'] = $msg['html'] . '<div class="program1 ng-binding" onclick="jhlb(' . $value['id'] . ')">' . $value['plan_name'] . '</div>';
			}
			action($act, $msg, $connection);
			$act = 'tzwz2';
			$msg = count($list);
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'mysc') {
			$sclist = $db->getAll("select * from jz_sc where userid='" . $data2['userid'] . "' order by id desc");
			$list = array();
			$plan = array();
			foreach ($sclist as $key => $value) {
				$plan[] = $value[planid];
			}
			if ($sclist) {
				$list = $db->getAll("select * from jz_plan where id in (" . implode(',', $plan) . ") order by id desc");
				$act = 'html';
				$msg[id] = 'sort';
				$msg[html] = '';
				foreach ($list as $key => $value) {
					$msg['html'] = $msg['html'] . '<div class="program1 ng-binding" onclick="jhlb2(' . $value['id'] . ')">' . $value['plan_name'] . '</div>';
				}
				action($act, $msg, $connection);
				$act = 'tzwz2';
				$msg = count($list);
				action($act, $msg, $connection);
			} else {
				error('您还没有收藏方案');
			}
		}
		if ($data2['act'] == 'tjsc') {
			$sc = $db->getOne("select * from jz_sc where userid='" . $data2['userid'] . "' and planid='" . $data2['planid'] . "' order by id desc");
			if ($sc) {
				error('你已经收藏过该方案');
			}
			$map[userid] = $data2['userid'];
			$map[planid] = $data2['planid'];
			$db->insert('jz_sc', $map);
			success('添加收藏成功');
		}
		if ($data2['act'] == 'qxsc') {
			$sc = $db->getOne("select * from jz_sc where userid='" . $data2['userid'] . "' and planid='" . $data2['planid'] . "' order by id desc");
			if (!$sc) {
				error('你已经删除过该方案');
			}
			$db->del('jz_sc', 'id=' . $sc[id]);
			success('删除收藏成功');
		}
		if ($data2['act'] == 'jhlb') {
			$plan = $db->getOne("select * from jz_plan where id='" . $data2['planid'] . "' order by id desc");
			$plan_data = $db->getAll("select * from jz_plan_data where planid='" . $data2['planid'] . "' order by id desc limit " . $bonus[xzhs]);
			$type = $db->getOne("select * from jz_order_menu where id='" . $plan['type_id'] . "' order by id desc");
			$location = $db->getOne("select * from jz_order_menu where id='" . $plan['location'] . "' order by id desc");
			$sort = $db->getOne("select * from jz_order_menu where id='" . $plan['sort'] . "' order by id desc");
			$act = 'html';
			$msg[id] = 'title';
			$msg[html] = $plan[plan_name] . '(' . $type['name'] . $location['name'] . ')<span  class="zmmrenew" style="" onclick="tjsc(' . $plan[id] . ')">收藏</span>';
			action($act, $msg, $connection);
			$act = 'html';
			$msg[id] = 'detail2';
			$msg[html] = '<ul class="" style="">';
			$zt[0] = '错';
			$zt[1] = '中';
			$zt['-1'] = '等待开奖';
			$cw = 0;
			foreach ($plan_data as $key => $value) {
				$dmtext = '';
				if ($sort[appear] == 'dm') {
					$dm = explode('-', $value['data']);
					$value['data'] = $dm[1];
					$dmtext = "(" . $dm[0] . ")";
				}
				if ($key != 0 && $plan_data[$key][zt] == 0) {
					$cw = $cw + 1;
				}
				if ($key == 0) {
					$msg[html] = $msg[html] . '<li class="" onclick="copy(' . $value['id'] . ')">
                        <div>
                        <span class="fl ng-binding">' . $value['num'] . '期' . $dmtext . '</span>
                        <span class="fr ng-binding" style="width:65px;">等待开奖..</span>
                        <span class="fl2 ng-binding" >' . substr($value['now'], -3) . '期</span>
                        </div><div class="cl sjvalue' . $value['id'] . '" style="color:red">' . $value['data'] . '</div></li>';
				} elseif ($cw < 2) {
					if (!$value['zjdata']) {
						$value['zjdata'] = '等待开奖';
						$value['zt'] = '-1';
					} elseif ($plan['type_id'] == '16') {
						$datalist = explode(',', $value['zjdata']);
						$value['zjdata'] = '开' . $datalist[$location['method'] - 1];
					}
					$msg[html] = $msg[html] . '<li class="" onclick="copy2(' . $value['id'] . ')">
                    <div>
                    <span class="fl ng-binding">' . $value['num'] . '期' . $dmtext . '&nbsp&nbsp&nbsp&nbsp[<font class="fjvalue sjvalue' . $value['id'] . '">' . $value['data'] . '</font>]</span>
                    <span class="fl ng-binding" style="min-width:40px;    margin-left: 15px;    margin-right: 5px;">' . substr($value['zjnum'], -3) . '期</span>
                    <span class="fl ng-binding" style="min-width:60px;    margin-right: 5px;">' . $value['zjdata'] . '</span>
                    <span class="fr ng-binding" style="margin-right:10px;    color: red;">' . $zt[$value['zt']] . '</span>
                    <div class="clear"></div>
                    </div></li>';
				}
			}
			$msg[html] = $msg[html] . '</ul>';
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'jhlb2') {
			$plan = $db->getOne("select * from jz_plan where id='" . $data2['planid'] . "' order by id desc");
			$plan_data = $db->getAll("select * from jz_plan_data where planid='" . $data2['planid'] . "' order by id desc limit " . $bonus[xzhs]);
			$location = $db->getOne("select * from jz_order_menu where id='" . $plan['location'] . "' order by id desc");
			$sort = $db->getOne("select * from jz_order_menu where id='" . $plan['sort'] . "' order by id desc");
			$act = 'html';
			$msg[id] = 'title';
			$msg[html] = $plan[plan_name] . '(' . $location['name'] . ')<span  class="zmmrenew" style="" onclick="qxsc(' . $plan[id] . ')">删除</span>';
			action($act, $msg, $connection);
			$act = 'html';
			$msg[id] = 'detail2';
			$msg[html] = '<ul class="" style="">';
			$zt[0] = '错';
			$zt[1] = '中';
			$zt['-1'] = '等待开奖';
			$cw = 0;
			foreach ($plan_data as $key => $value) {
				$dmtext = '';
				if ($sort[appear] == 'dm') {
					$dm = explode('-', $value['data']);
					$value['data'] = $dm[1];
					$dmtext = "(" . $dm[0] . ")";
				}
				if ($key != 0 && $plan_data[$key][zt] == 0) {
					$cw = $cw + 1;
				}
				if ($key == 0) {
					$msg[html] = $msg[html] . '<li class="" onclick="copy(' . $value['id'] . ')">
                        <div>
                        <span class="fl ng-binding">' . $value['num'] . '期' . $dmtext . '</span>
                        <span class="fr ng-binding" style="width:65px;">等待开奖..</span>
                        <span class="fl2 ng-binding" >' . substr($value['now'], -3) . '期</span>
                        </div><div class="cl sjvalue' . $value['id'] . '" style="color:red">' . $value['data'] . '</div></li>';
				} elseif ($cw < 2) {
					if (!$value['zjdata']) {
						$value['zjdata'] = '等待开奖';
						$value['zt'] = '-1';
					} elseif ($plan['type_id'] == '16') {
						$datalist = explode(',', $value['zjdata']);
						$value['zjdata'] = '开' . $datalist[$location['method'] - 1];
					}
					$msg[html] = $msg[html] . '<li class="" onclick="copy2(' . $value['id'] . ')">
                    <div>
                    <span class="fl ng-binding">' . $value['num'] . '期' . $dmtext . '&nbsp&nbsp&nbsp&nbsp[<font class="fjvalue sjvalue' . $value['id'] . '">' . $value['data'] . '</font>]</span>
                    <span class="fl ng-binding" style="min-width:40px;    margin-left: 15px;    margin-right: 5px;">' . substr($value['zjnum'], -3) . '期</span>
                    <span class="fl ng-binding" style="min-width:60px;    margin-right: 5px;">' . $value['zjdata'] . '</span>
                    <span class="fr ng-binding" style="margin-right:10px;    color: red;">' . $zt[$value['zt']] . '</span>
                    <div class="clear"></div>
                    </div></li>';
				}
			}
			$msg[html] = $msg[html] . '</ul>';
			action($act, $msg, $connection);
			$act = 'xzlb';
			$msg = $plan['type_id'];
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'modification') {
			if (!$data2['old']) {
				error('原始密码不能为空');
			}
			if (!$data2['new']) {
				error('新密码不能为空');
			}
			if (!$data2['new2']) {
				error('请确认你的新密码');
			}
			if ($data2['new'] != $data2['new2']) {
				error('两次输入的密码不一样');
			}
			if (count(str_split($data2['new'])) < 6 || count(str_split($data2['new'])) > 12) {
				error('请确认6-12位密码');
			}
			if (md5($data2['old']) != $user['password']) {
				error('原始密码错误');
			}
			$map[password] = md5($data2['new']);
			$db->update('jz_user', $map, 'id=' . $user[id]);
			success('修改密码成功', 'tab-account');
		}
		if ($data2['act'] == 'user') {
			$act = 'html';
			$msg[id] = 'user_login';
			$msg[html] = $user['user_login'];
			action($act, $msg, $connection);
			$act = 'datadjs';
			$msg = strtotime($user['due_time']) - time();
			action($act, $msg, $connection);
			if ($user['is_grade']) {
				$act = 'showzhz';
				$msg = '';
				action($act, $msg, $connection);
				$act = 'html';
				$msg[id] = 'zhnum';
				$msg[html] = '子账户管理(允许数量：' . $user['child_num'] . ')';
				action($act, $msg, $connection);
			}
		}
		if ($data2['act'] == 'lottery') {
			$list = $db->getAll("select * from jz_data where typeid='" . $data2['typeid'] . "' order by id desc limit " . (($data2['page'] - 1) * 50) . ",50 ");
			if ($data2['page'] == '1') {
				$act = 'html';
			} else {
				$act = 'append';
			}
			$msg[html] = '';
			foreach ($list as $key2 => $value2) {
				$datalist = explode(',', $value2['data']);
				$msg['html'] = $msg['html'] . '<li  class="">
        <div>
          <span class="fl ng-binding">第' . $value2[num] . '期</span>
          <span class="fr ng-binding">' . date('Y.m.d H:i', $value2[time]) . '</span>
        </div>
        <div class="cl">';
				foreach ($datalist as $key => $value) {
					$msg['html'] = $msg['html'] . '<span class="ng-binding">' . $value . '</span>';
				}
				$msg['html'] = $msg['html'] . '</div></li>';
			}
			$msg['id'] = 'list';
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'scuser') {
			$son = $db->getOne("select * from jz_user where id='" . $data2['id'] . "' order by id desc ");
			if ($son['parent'] != $user['id']) {
				error('信息有误');
			}
			$db->del('jz_user', 'id=' . $data2['id']);
			$act = 'init';
			$msg = 'account';
			action($act, $msg, $connection);
			success('删除成功');
		}
		if ($data2['act'] == 'account') {
			$list = $db->getAll("select * from jz_user where parent='" . $user['id'] . "' order by id desc ");
			$act = 'html';
			$msg[html] = '';
			foreach ($list as $key2 => $value2) {
				$datalist = explode(',', $value2['data']);
				$msg['html'] = $msg['html'] . '<li  class="">
        <div>
          <span class="fl ng-binding">' . $value2['user_login'] . '</span>
          <span class="fr ng-binding"><a onclick="scuser(' . $value2['id'] . ');">删除</a></span>
        </div>
        <div class="cl">上次登录时间:' . $value2['last_time'] . '</div></li>';
			}
			$msg['id'] = 'user_account';
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'hometype') {
			$typexx = $db->getOne("select * from jz_order_menu where id='" . $data2['typeid'] . "' order by id desc");
			$kjdata = $db->getOne("select * from jz_data where typeid='" . $data2['typeid'] . "' order by id desc");
			$act = 'tpyexx';
			$msg = array();
			$msg['typeid'] = $data2['typeid'];
			$msg['num'] = $kjdata['num'];
			$datalist = explode(',', $kjdata['data']);
			$msg['html'] = '';
			foreach ($datalist as $key => $value) {
				$msg['html'] = $msg['html'] . '<span class="ng-binding">' . $value . '</span>';
			}
			$msg['num'] = $kjdata['num'];
			action($act, $msg, $connection);
			eval('$num=' . $typexx[appear] . '($typexx[data]);');
			$act = 'djs';
			$msg = array();
			$msg['time'] = $num[time] - time();
			$msg['id'] = 'djs' . $data2['typeid'];
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'programtype') {
			$typexx = $db->getOne("select * from jz_order_menu where id='" . $data2['typeid'] . "' order by id desc");
			$kjdata = $db->getOne("select * from jz_data where typeid='" . $data2['typeid'] . "' order by id desc");
			$act = 'html';
			$msg = array();
			$msg['id'] = 'lastkjxx';
			$msg['html'] = '上期开奖  ' . $kjdata['num'] . '期  ' . $kjdata['data'];
			action($act, $msg, $connection);
			eval('$num=' . $typexx[appear] . '($typexx[data]);');
			$act = 'prodjs';
			$msg = $num[time] - time();
			action($act, $msg, $connection);
			$act = 'html';
			$msg = array();
			$msg['id'] = 'gxsj';
			$msg['html'] = '更新时间&nbsp;&nbsp;' . date("Y年m月d日 H:i:s", time());
			action($act, $msg, $connection);
			$msg = array();
			$msg['id'] = 'jhxx';
			$msg['html'] = '欢迎使用红星计划&nbsp;&nbsp;内容仅供参考&nbsp;&nbsp;QQ客服&nbsp;&nbsp' . $bonus[qq];
			action($act, $msg, $connection);
		}
		if ($data2['act'] == 'index') {
			$user = $db->getOne("select * from jz_user where id='" . $data2['userid'] . "' order by id desc limit 1");
			if (!$user) {
				act('openurl', 'login');
			} else {
				act('openurl', 'tab-home');
			}
		}
		if ($data2['act'] == 'dologin') {
			if (!$data2['user_login']) {
				error('用户不能为空');
			}
			if (!$data2['password']) {
				error('密码不能为空');
			}
			$user = $db->getOne("select * from jz_user where user_login='" . $data2['user_login'] . "' order by id desc limit 1");
			if (!$user) {
				error('用户不存在');
			}
			if ($user['password'] != md5($data2['password'])) {
				error('密码错误');
			}
			if (time() > strtotime($user['due_time'])) {
				error('您的时间已到期,请联系客服充值');
			}
			if ($user[status] == '1') {
				error('您已经被封号');
			}
			if ($user[status] == '2') {
				error('您已经被限制登陆');
			}
			if ($bonus['sfgg']) {
				$act = 'dcgg';
				if ($user['is_grade'] == 0) {
					$msg = "<h1>" . $bonus['ggtitle'] . "</h1><p>" . $bonus['ggbody'] . "</p>";
				} else {
					$msg = "<h1>" . $bonus['gj_ggtitle'] . "</h1><p>" . $bonus['gj_ggbody'] . "</p>";
				}
				action($act, $msg, $connection);
			}
			$msg = array();
			$act = "dologin";
			$msg['msg'] = '登录成功';
			$msg['userid'] = $user['id'];
			$msg['salt'] = md5(md5($user['id'] . 'zmm') . 'zmm');
			act($act, $msg);
		}
		if ($data2['act'] == 'doreg') {
			if (!$data2['user_login']) {
				error('用户不能为空');
			}
			if (!$data2['password']) {
				error('密码不能为空');
			}
			if (!$data2['password2']) {
				error('请确认你的密码');
			}
			if ($data2['password'] != $data2['password2']) {
				error('两次输入的密码不一样');
			}
			if (count(str_split($data2['password'])) < 6 || count(str_split($data2['password'])) > 12) {
				error('请确认6-12位密码');
			}
			$reg[user_login] = $data2['user_login'];
			$xtuser = $db->getOne("select * from jz_user where user_login='" . $data2['user_login'] . "' order by id desc limit 1");
			if ($xtuser) {
				error('用户已经存在');
			}
			$reg[mobile] = 0;
			$reg[password] = md5($data2['password']);
			$reg[create_time] = date('Y-m-d H:i:s', time());
			$reg[due_time] = date('Y-m-d H:i:s', strtotime('+' . $bonus['sj'] . ' hours'));
			$reg[last_time] = date('Y-m-d H:i:s', time());
			$reg[reg_ip] = $connection->getRemoteIp();
			$id = $db->insert('jz_user', $reg);
			$act = "doreg";
			$msg['msg'] = '注册成功,请登录';
			$msg['id'] = $id;
			$msg['url'] = 'login';
			act($act, $msg);
		}
		if ($data2['act'] == 'doreg2') {
			if (!$data2['user_login']) {
				error('用户不能为空');
			}
			if (!$data2['password']) {
				error('密码不能为空');
			}
			if (!$data2['password2']) {
				error('请确认你的密码');
			}
			if ($data2['password'] != $data2['password2']) {
				error('两次输入的密码不一样');
			}
			if (count(str_split($data2['password'])) < 6 || count(str_split($data2['password'])) > 12) {
				error('请确认6-12位密码');
			}
			$reg[user_login] = $data2['user_login'];
			$xtuser = $db->getOne("select * from jz_user where user_login='" . $data2['user_login'] . "' order by id desc limit 1");
			if ($xtuser) {
				error('用户已经存在');
			}
			$sonlist = $db->getAll("select * from jz_user where parent='" . $user['id'] . "' order by id desc");
			if (count($sonlist) >= $user['child_num']) {
				error('你的子账户已经到上限了');
			}
			$reg['mobile'] = 0;
			$reg['password'] = md5($data2['password']);
			$reg['create_time'] = date('Y-m-d H:i:s', time());
			$reg['due_time'] = $user['due_time'];
			$reg['parent'] = $user['id'];
			$reg['last_time'] = date('Y-m-d H:i:s', time());
			$reg['reg_ip'] = $connection->getRemoteIp();
			$id = $db->insert('jz_user', $reg);
			$act = 'back';
			$msg = '';
			action($act, $msg, $connection);
			success('子账户开通成功');
		}
		if ($data2['act'] == 'docharge') {
			if (!$data2['user_login']) {
				error('用户不能为空');
			}
			if (!$data2['cardid']) {
				error('卡密不能为空');
			}
			$user = $db->getOne("select * from jz_user where user_login='" . $data2['user_login'] . "' order by id desc limit 1");
			if (!$user) {
				error('用户不存在');
			}
			if ($user['parent']) {
				error('子账户不能充值,请联系可客服');
			}
			if ($user['is_grade'] == 1) {
				error('高级用户不能充值,请联系可客服');
			}
			$card = $db->getOne("select * from jz_card where card='" . $data2['cardid'] . "' order by id desc limit 1");
			if (!$card) {
				error('卡密错误');
			}
			if ($card['status'] == 1) {
				error('该卡密已经使用');
			}
			$map2[user_login] = $data2['user_login'];
			$map2[status] = 1;
			$map2[usce_time] = date('Y-m-d H:i:s', time());
			$db->update('jz_card', $map2, 'id=' . $card[id]);
			$due_time = strtotime($user[due_time]);
			if ($due_time < time()) {
				$due_time = time();
			}
			$map[due_time] = date('Y-m-d H:i:s', $due_time + $card[num] * 3600);
			$db->update('jz_user', $map, 'id=' . $user[id]);
			success('充值成功,加时' . $card[num] . '小时', '');
		}
	} catch (Exception $e) {
		$result = json_decode($e->getMessage(), true);
		$connection->send(json_encode($result));
	}
};
Worker::runAll();
function start()
{
	global $db;
	global $title;
	$map2 = array();
	$map2[zt] = 0;
	$where = ' xl="' . $title . '"';
	$db->update("jz_online", $map2, $where);
	$bonussql = $db->getOne("select * from jz_options where option_name='bonus'");
	$bonus = json_decode($bonussql['option_value'], true);
	$online = $db->getAll("select *  from jz_online where zt='1'  group by uid order by id desc");
	$zronline = $db->getAll("select *  from jz_online where   time<'" . strtotime(date('Y-m-d 00:00:00', time())) . "' and time>'" . strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))) . "' group by uid order by id desc");
	$jronline = $db->getAll("select *  from jz_online where  time>'" . strtotime(date('Y-m-d 00:00:00', time())) . "' group by uid order by id desc ");
	$bonus['zxonline'] = count($online) + 0;
	$bonus['zronline'] = count($zronline) + 0;
	$bonus['jronline'] = count($jronline) + 0;
	$bonus['gxtime'] = date('Y-m-d H:i:s', time());
	$where = " option_name='bonus' ";
	$map = array();
	$map['option_value'] = json_encode($bonus, JSON_UNESCAPED_UNICODE);
	$db->update("jz_options", $map, $where);
	echo date("Y-m-d H:i:s", time());
	$time_interval = 5;
	Timer::add($time_interval, 'sfzj');
}

function ouput($str)
{
	$zmm = mb_convert_encoding($str, "GB2312", "UTF-8");
	echo $zmm . "\r\n";
}

function loginout($msg)
{
	$data['msg'] = $msg;
	$data['act'] = 'loginout';
	throw new Exception(json_encode($data));
}

function action($act, $msg, $connection)
{
	$data['msg'] = $msg;
	$data['act'] = $act;
	$connection->send(json_encode($data));
}

function addhtml($html, $connection)
{
	$data['act'] = 'addhtml';
	$msg['html'] = $html;
	$msg['id'] = 'content';
	$data['msg'] = $msg;
	$connection->send(json_encode($data));
}

function act($act, $msg)
{
	$data['msg'] = $msg;
	$data['act'] = $act;
	throw new Exception(json_encode($data));
}

function error($msg)
{
	$data['msg'] = $msg;
	$data['act'] = 'error';
	throw new Exception(json_encode($data));
}

function success($msg, $url = '')
{
	$zzxx[msg] = $msg;
	$zzxx[url] = $url;
	$data['msg'] = $zzxx;
	$data['act'] = 'success';
	throw new Exception(json_encode($data));
}

function ssc($data)
{
	$timelist = explode(',', $data);
	$qs = 1;
	$end = '';
	foreach ($timelist as $key => $value) {
		if (time() > strtotime(date("Y-m-d", time()) . " " . $value)) {
			$qs = $key + 2;
		}
	}
	$time = strtotime(date("Y-m-d", time()) . " " . $timelist[$qs - 1]);
	$now = date('Ymd', time()) . zhws($qs, 3);
	$res['now'] = $now;
	$res['num'] = $now . $end;
	$res['time'] = $time;
	return $res;
}

function jsk3($data)
{
	$timelist = explode(',', $data);
	$qs = 1;
	$end = '';
	foreach ($timelist as $key => $value) {
		if (time() > strtotime(date("Y-m-d", time()) . " " . $value)) {
			$qs = $key + 2;
		}
	}
	$time = strtotime(date("Y-m-d", time()) . " " . $timelist[$qs - 1]);
	$now = date('Ymd', time()) . zhws($qs, 2);
	$res['now'] = $now;
	$res['num'] = $now . $end;
	$res['time'] = $time;
	return $res;
}

function fc3d($data)
{
	$timelist = explode(',', $data);
	$qs = 1;
	$end = '';
	foreach ($timelist as $key => $value) {
		if (time() > strtotime(date("Y-m-d", time()) . " " . $value)) {
			$qs = $key + 2;
		}
	}
	$bz = (strtotime(date("Y-m-d 00:00:00", time())) - strtotime(date("Y-1-1 00:00:00", time()))) / (24 * 3600);
	$time = strtotime(date("Y-m-d", time()) . " " . $timelist[$qs - 1]);
	$now = date('Y', strtotime('+' . ($qs - 1) . ' days')) . zhws($qs + $bz, 3);
	$res['now'] = $now;
	$res['num'] = $now . $end;
	$res['time'] = $time;
	return $res;
}

function pk10($data)
{
	$day = (strtotime(date("Y-m-d 00:00:00", time())) - strtotime('2017-04-01 00:00:00')) / (24 * 3600);
	$timelist = explode(',', $data);
	$qs = 1;
	$end = '';
	foreach ($timelist as $key => $value) {
		if (time() > strtotime(date("Y-m-d", time()) . " " . $value)) {
			$qs = $key + 2;
		}
	}
	$time = strtotime(date("Y-m-d", time()) . " " . $timelist[$qs - 1]);
	$now = $qs + 609998 + $day * 179;
	$res['num'] = $now . $end;
	$res['time'] = $time;
	return $res;
}

function zhws($num, $ws)
{
	$sz = $num;
	$j = 1;
	for ($i = 1; $i < $ws; $i++) {
		$j = $j * 10;
		if ($num < $j) {
			$sz = '0' . $sz;
		}
	}
	return $sz;
} ?>

