<?php
            if(strtotime($connection->user['due_time'])-time()<0){
                act('back','',$connection);
                return tip('非vip会员不能浏览官方计划,请充值',$connection);
            }
            
			$type=$db->getOne("select * from jz_order_menu where id='".$data2['type']."' order by listorder asc");
 			title($type['name'].'-收藏官方计划',$connection);
            
 			$msg=array();
			$msg[id]='typetitle';
			$msg[html]='收藏官方计划('.$type['name'].')';
 			act('html',$msg,$connection);


			$data=array();
 			$data['act']='programtype';
 			$data['type']=$data2['type'];
 			reqact($data,$connection);


 			$msg=array();
            $msg[id]='type'.$data2['type'];
            $msg[html]='location-action';
            act('active',$msg,$connection);

            $sc=$db->getAll("select * from jz_sc where userid='".$connection->uid."' order by id desc");
            $xzlist[]=0;
            foreach ($sc as $key => $value) {
                $xzlist[]=$value['planid'];
            }
            $list=$db->getAll("select *,min(id) as id from jz_plan where type_id='".$data2['type']."' and id in(".implode(',', $xzlist).") group by plan_name order by id desc");

            $msg[id]='sort';
    		$msg[html]='';
            foreach ($list as $key => $value) {
             	$msg['html']=$msg['html'].'<div class="program1 ng-binding" id="program1-action'.$value['id'].'" onclick="jhlb('.$value['id'].')">'.$value['plan_name'].'</div>';
    		}
    		act('html',$msg,$connection);


    		$data=array();
		    $data['act']='jhlb';
		    $data['planid']=$list[0]['id'];
		    reqact($data,$connection);
			// $data=array();
 		// 	$data['act']='order_location';
 		// 	$data['location']=$list[0]['id'];
 		// 	reqact($data,$connection);
