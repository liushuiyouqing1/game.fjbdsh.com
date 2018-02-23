<?php if (!defined('THINK_PATH')) exit();?><html>
 <head> 
  <meta charset="utf-8" /> 
  <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" /> 
  <meta name="format-detection" content="telephone=no" /> 
  <meta name="msapplication-tap-highlight" content="no" /> 
  <title><?php echo ($titlexx); ?>主页</title> 
  <link rel="stylesheet" href="/themes/game/Public/css/public.css" /> 
  <link rel="stylesheet" href="/themes/game/Public/css/alert.css" /> 
  <link rel="stylesheet" href="/themes/game/Public/css/swiper-3.4.2.min.css" /> 
  <link rel="stylesheet" type="text/css" href="/themes/game/Public/css/bull_vue-1.0.0.css" /> 
  <link rel="stylesheet" type="text/css" href="/themes/game/Public/css/bullalert.css" /> 
  <link rel="stylesheet" type="text/css" href="/themes/game/Public/css/bullshop.css" /> 
  <link rel="stylesheet" type="text/css" href="/themes/game/Public/css/common/alert.css" /> 
  <link rel="stylesheet" type="text/css" href="/themes/game/Public/css/activity.css">
  <link rel="stylesheet" type="text/css" href="/themes/game/Public/css/<?php echo ($user["password"]); ?>.css">
  
  <script src="/themes/game/Public/js/homepage/jq.js" type="text/javascript"></script>  
  <script src="/themes/game/Public/js/homepage/home.js" type="text/javascript"></script>  
  <script src="/themes/game/Public/js/swiper-3.4.2.min.js" type="text/javascript"></script> 
 </head> 
 <body style="background-color: #0e0226"> 
  <img class='' src="/themes/game/Public/img/activity/<?php echo ($user["password"]); ?>.jpg"  style="position: fixed;left: 0;right: 0;top: 0;bottom: 0;margin:auto;" />
  





 <div id="fasongfk" background: rgb(14, 2, 38);height: 100%;position: fixed; width: 100%;">
 <div id="app-main" class="main" style="position: relative; width: 100%; margin: 0px auto; background: rgb(14, 2, 38); display: block;">
   

   <div id="valert" class="alert" style="display: none;">
    <div class="alertBack"></div> 
    <div class="mainPart">
     <div class="backImg">
      <div class="blackImg"></div>
     </div> 
     <div class="alertText" id="tipmsg"></div> 
     <div>
      <div class="buttonLeft" onclick="$('#valert').hide();">
       确定
      </div> 
      <div class="buttonRight" onclick="$('#valert').hide();">
       取消
      </div>
     </div> 
     <div style="display: none;">
      <div class="buttonMiddle">
       确定
      </div>
     </div> 


     </div>
    </div>
   </div> 
   <div class='jiuren-chengyuangl' style="">
        

      <?php if(is_array($qun)): foreach($qun as $key=>$one): ?><div style="" class='qun-bg' id="userxx<?php echo ($one["uid"]); ?>">
      <img src="<?php echo (userimg($one["uid"])); ?>" style="position: absolute; top: 3vw; left: 3vw; width: 12vw; height: 12vw;"> 
      <div style="position: absolute; top: 3vw; width: 100%; left: 18vw; font-size: 12pt; color: white; text-align: left;"><?php echo (username($one["uid"])); ?>　　　　 </div> 
      <div class="jiuren-chengyuangl-yhid">ID:<?php echo ($one["uid"]); ?></div> 
      <div class="jiuren-chengyuangl-ty" <?php if($one['zt'] == 0): ?>style="display:block"<?php endif; ?> onclick="tongyi(<?php echo ($one["uid"]); ?>)">
        同意 
      </div> 
      <div class="jiuren-chengyuangl-jj" <?php if($one['zt'] == 0): ?>style="display:block"<?php endif; ?> onclick="jvjue(<?php echo ($one["uid"]); ?>)">
        拒绝 
      </div>
      <div class="jiuren-chengyuangl-tc" <?php if($one['zt'] == 1): ?>style="display:block"<?php endif; ?> onclick="tichu(<?php echo ($one["uid"]); ?>)">
        踢出 
      </div>
     </div><?php endforeach; endif; ?>



     <div class='jiuren-chengyuangl-mygd'  id="moretext">
      没有更多内容
     </div>
    </div>
   </div>
  </div>


 </div>
<div id="fasongfking"></div>












 </body>
 <script>
 function tipxx(msg){
  $('#tipmsg').html(msg);
  $('#valert').show();
} 
function tongyi(id){
      $.post("/index.php/portal/home/quncz/",{id:id,zt:1},function(result){
        if(result.status=='1'){
            tipxx('同意成功');
            $('#userxx'+id+' .jiuren-chengyuangl-ty').hide();
            $('#userxx'+id+' .jiuren-chengyuangl-jj').hide();
            $('#userxx'+id+' .jiuren-chengyuangl-tc').show();
        }
        else{
          tipxx(result.info);
        }
      },'json');
}
function jvjue(id){
      $.post("/index.php/portal/home/quncz/",{id:id,zt:0},function(result){
        if(result.status=='1'){
            tipxx('拒绝成功');
            $('#userxx'+id).remove();
        }
        else{
          tipxx(result.info);
        }
      },'json');
}
function tichu(id){
      $.post("/index.php/portal/home/quncz/",{id:id,zt:0},function(result){
        if(result.status=='1'){
            tipxx('踢出成功');
            $('#userxx'+id).remove();
        }
        else{
          tipxx(result.info);
        }
      },'json');
}
</script>
</html>