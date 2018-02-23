<?php
    $qs=$data2['qs'];
    $money=$data2['money'];
    $data=$data2['data'];
    $type=$data2['type'];
    $weizhi=$data2['weizhi'];
    if($weizhi==''){
        return tip('请选择位置',$connection);
    }
    if(empty($data)){
        return tip('请选择内容',$connection);
    }
    if(empty($qs)){
        return tip('请选择期数',$connection);
    }
    $plan=$db->getOne("select * from jz_plan_user where uid='".$connection->uid."' and sort='".$type."'  and weizhi='".$weizhi."' and stages='".$qs."' order by id desc");
    $sort=$db->getOne("select * from jz_order_menu where id='".$type."' order by id desc");
    $location=$db->getOne("select * from jz_order_menu where id='".$sort['parentid']."' order by id desc");
    $typexx=$db->getOne("select * from jz_order_menu where id='".$location['parentid']."' order by id desc");

    if(!$plan){
        $plan=array();
        $plan['uid']=$connection->uid;
        $plan['sort']=$type;
        $plan['location']=$location['id'];
        $plan['type_id']=$location['parentid'];
        $plan['weizhi']=$weizhi;
        $plan['stages']=$qs;
        $plan['data_time']=time();
        $plan['id']=$db->insert('jz_plan_user',$plan);
    }

    eval('$num='.$typexx[appear].'_now($typexx[data],$plan[stages]);');
    $add=array();
    $add['planid']=$plan['id'];
    $add['data']=$data;
    $add['zt']=0;
    $add['num']=$num['num'];
    $add['now']=$num['now'];
    $add['sfuser']=$connection->uid;
    $add['money']=$money;
    $add['time']=time();

    $data=$db->getOne("select * from jz_plan_data where  planid=".$add['planid']." and num ='".$add['num']."' and time>'".strtotime('-12 hours')."'order by id desc");

    if($data){
        return tip('你已经发布本期计划了,请等待下一期',$connection);
    }
    $db->insert('jz_plan_data',$add);

    tip('发布成功',$connection);
    action('back','',$connection);

