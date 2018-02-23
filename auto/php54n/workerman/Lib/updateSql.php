<?php
$mysql_conf = [
	'host'    => '127.0.0.1:3306', 
    'db'      => 'cn5HcBJ3k8', 
    'db_user' => 'cn5HcBJ3k8', 
    'db_pwd'  => 'cn5HcBJ3k8', 
];
$db = new PDO("mysql:host=" . $mysql_conf['host'] . ";dbname=" . $mysql_conf['db'], $mysql_conf['db_user'], $mysql_conf['db_pwd']);//创建一个pdo对象
$now = date('Y-m-d H:i:s');
$sql = "update jz_user set gailv = 0, is_grade = 0 where create_time < '{$now}';";
$result = $db->query($sql);
echo "执行完成";