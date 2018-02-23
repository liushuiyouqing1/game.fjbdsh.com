<?php
namespace Think;
class Db
{
	static private $instance = array();
	static private $_instance = null;

	static public function getInstance($config = array())
	{
		$md5 = md5(serialize($config));
		if (!isset(self::$instance[$md5])) {
			$options = self::parseConfig($config);
			if ('mysqli' == $options['type']) $options['type'] = 'mysql';
			$class = !empty($options['lite']) ? 'Think\Db\Lite' : 'Think\\Db\\Driver\\' . ucwords(strtolower($options['type']));
			if (class_exists($class)) {
				self::$instance[$md5] = new $class($options);
			} else {
				E(L('_NO_DB_DRIVER_') . ': ' . $class);
			}
		}
		self::$_instance = self::$instance[$md5];
		return self::$_instance;
	}

	static private function parseConfig($config)
	{
		if (!empty($config)) {
			if (is_string($config)) {
				return self::parseDsn($config);
			}
			$config = array_change_key_case($config);
			$config = array('type' => $config['db_type'], 'username' => $config['db_user'], 'password' => $config['db_pwd'], 'hostname' => $config['db_host'], 'hostport' => $config['db_port'], 'database' => $config['db_name'], 'dsn' => isset($config['db_dsn']) ? $config['db_dsn'] : null, 'params' => isset($config['db_params']) ? $config['db_params'] : null, 'charset' => isset($config['db_charset']) ? $config['db_charset'] : 'utf8', 'deploy' => isset($config['db_deploy_type']) ? $config['db_deploy_type'] : 0, 'rw_separate' => isset($config['db_rw_separate']) ? $config['db_rw_separate'] : false, 'master_num' => isset($config['db_master_num']) ? $config['db_master_num'] : 1, 'slave_no' => isset($config['db_slave_no']) ? $config['db_slave_no'] : '', 'debug' => isset($config['db_debug']) ? $config['db_debug'] : APP_DEBUG, 'lite' => isset($config['db_lite']) ? $config['db_lite'] : false,);
		} else {
			$config = array('type' => C('DB_TYPE'), 'username' => C('DB_USER'), 'password' => C('DB_PWD'), 'hostname' => C('DB_HOST'), 'hostport' => C('DB_PORT'), 'database' => C('DB_NAME'), 'dsn' => C('DB_DSN'), 'params' => C('DB_PARAMS'), 'charset' => C('DB_CHARSET'), 'deploy' => C('DB_DEPLOY_TYPE'), 'rw_separate' => C('DB_RW_SEPARATE'), 'master_num' => C('DB_MASTER_NUM'), 'slave_no' => C('DB_SLAVE_NO'), 'debug' => C('DB_DEBUG', null, APP_DEBUG), 'lite' => C('DB_LITE'),);
		}
		return $config;
	}

	static private function parseDsn($dsnStr)
	{
		if (empty($dsnStr)) {
			return false;
		}
		$info = parse_url($dsnStr);
		if (!$info) {
			return false;
		}
		$dsn = array('type' => $info['scheme'], 'username' => isset($info['user']) ? $info['user'] : '', 'password' => isset($info['pass']) ? $info['pass'] : '', 'hostname' => isset($info['host']) ? $info['host'] : '', 'hostport' => isset($info['port']) ? $info['port'] : '', 'database' => isset($info['path']) ? substr($info['path'], 1) : '', 'charset' => isset($info['fragment']) ? $info['fragment'] : 'utf8',);
		if (isset($info['query'])) {
			parse_str($info['query'], $dsn['params']);
		} else {
			$dsn['params'] = array();
		}
		return $dsn;
	}

	static public function __callStatic($method, $params)
	{
		return call_user_func_array(array(self::$_instance, $method), $params);
	}
} 