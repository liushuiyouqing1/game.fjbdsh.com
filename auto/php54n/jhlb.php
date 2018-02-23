<?php
$msg = array();
$msg[id] = 'program1-action' . $data2['planid'];
$msg[html] = 'program1-action';
act('active', $msg, $connection);
global $bonus;
if (!$bonus['xzhs']) {
	$bonus['xzhs'] = '10';
}
$plan = $db->getOne("select * from jz_plan where id='" . $data2['planid'] . "' order by id desc");
$type = $db->getOne("select * from jz_order_menu where id='" . $plan['type_id'] . "' order by id desc");
$act = 'html';
$msg[id] = 'title';
$msg[html] = $plan[plan_name] . '(' . $type['name'] . ')<span  class="zmmrenew" style="" onclick="tjsc(' . $plan[id] . ')">收藏</span>';
action($act, $msg, $connection);
$act = 'html';
$msg[id] = 'detail2';
$msg[html] = '<ul class="" style="">';
$zt[0] = '错';
$zt[1] = '中';
$zt['-1'] = '等待开奖';
$planlist = $db->getAll("select * from jz_plan where plan_name='" . $plan['plan_name'] . "' order by id desc");
foreach ($planlist as $key2 => $value2) {
	$plan_data = $db->getAll("select * from jz_plan_data where planid='" . $value2['id'] . "' order by id desc limit " . $bonus[xzhs]);
	$location = $db->getOne("select * from jz_order_menu where id='" . $value2['location'] . "' order by id desc");
	$sort = $db->getOne("select * from jz_order_menu where id='" . $value2['sort'] . "' order by id desc");
	if ($location['select'] == '6666-任选') {
		$loxx = explode('#', $value2['weizhi']);
		$warray = array('个', '十', '百', '千', '万');
		$weizhi = '';
		foreach ($loxx as $key3 => $value3) {
			$weizhi = $weizhi . $warray[$value3];
		}
	} else {
		$loxx = explode('#', $location['select']);
		foreach ($loxx as $key3 => $value3) {
			$sj = explode('-', $value3);
			if ($sj['0'] == $value2['weizhi']) {
				$weizhi = $sj['1'];
			}
		}
	}
	if ($weizhi == '全部') {
		$weizhi = $location['name'];
	}
	$lb = '【' . $plan['plan_name'] . '】' . $weizhi . '-' . $sort['name'] . '-' . $value2['stages'] . '期';
	if ($key2 != 0) {
		$msg[html] = $msg[html] . '<hr style=" border-style: dashed;">';
	}
	$msg[html] = $msg[html] . '<li style="text-align: center;
    font-size: .35rem;">' . $lb . '<li>';
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
			$msg[html] = $msg[html] . '<li class="" onclick="send(\'planxx\',{id:' . $value['id'] . '})">
                        <div>
                        <span class="fl ng-binding">' . $value['num'] . '期' . $dmtext . '</span>
                        <span class="fr ng-binding" style="width:1.6rem">等待开奖..</span>
                        <span class="fl2 ng-binding" >' . substr($value['now'], -3) . '期</span>
                        </div><div class="cl sjvalue' . $value['id'] . '" style="color:red">' . $value['data'] . '</div></li>';
		} elseif ($cw < 2) {
			if (!$value['zjdata']) {
				$value['zjdata'] = '等待开奖';
				$value['zt'] = '-1';
			} else {
				$datalist = explode(',', $value['zjdata']);
				$loxx = explode('#', $location['select']);
				$value['zjdata'] = '';
				foreach ($loxx as $key3 => $value3) {
					$value['zjdata'] = $datalist[$value3] . ' ' . $value['zjdata'];
				}
				$value['zjdata'] = '开' . $value['zjdata'];
			}
			$msg[html] = $msg[html] . '<li class="" onclick="send(\'planxx\',{id:' . $value['id'] . '})">
                    <div>
                    <span class="fl ng-binding">' . $value['num'] . '期' . $dmtext . '&nbsp[<font class="fjvalue sjvalue' . $value['id'] . '">' . $value['data'] . '</font>]</span>
                    <span class="fl ng-binding" >' . substr($value['zjnum'], -3) . '期</span>
                    <span class="fl ng-binding" >' . $value['zjdata'] . '</span>
                    <span class="fr ng-binding" style="margin-right:10px;    color: red;">' . $zt[$value['zt']] . '</span>
                    <div class="clear"></div>
                    </div></li>';
		}
	}
}
$msg[html] = $msg[html] . '</ul>';
action($act, $msg, $connection);