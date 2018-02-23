<?php
			global $bonus;
			$typexx=$db->getOne("select * from jz_order_menu where id='".$data2['type']."' order by id desc");
			$kjdata=$db->getOne("select * from jz_data where typeid='".$data2['type']."' order by id desc");
			$act='html';
			$msg=array();
			$msg['id']='lastkjxx';
			$msg['html']='上期开奖  '.$kjdata['num'].'期  '.$kjdata['data'];
			action($act,$msg,$connection);
			eval('$num='.$typexx[appear].'($typexx[data]);');//计算当前期数
			$act='prodjs';
			$msg=$num[time]-time();
			action($act,$msg,$connection);
			$act='html';
			$msg=array();
			$msg['id']='gxsj';
			$msg['html']='更新时间&nbsp;&nbsp;'.date("Y年m月d日 H:i:s",time());
			action($act,$msg,$connection);
			$msg=array();
			$msg['id']='jhxx';
			$msg['html']='欢迎使用众神人工计划&nbsp;&nbsp;内容仅供参考&nbsp;&nbsp;QQ客服&nbsp;&nbsp'.$bonus[qq];
			action($act,$msg,$connection);