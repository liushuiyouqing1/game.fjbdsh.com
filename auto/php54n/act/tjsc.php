<?php

            $sc=$db->getOne("select * from jz_sc where userid='".$connection->uid."' and planid='".$data2['planid']."' order by id desc");
            if($sc){
                return tip('你已经收藏过该方案',$connection);
            }
            $map[userid]=$connection->uid;
            $map[planid]=$data2['planid'];
            $db->insert('jz_sc',$map);
            tip('添加收藏成功',$connection);