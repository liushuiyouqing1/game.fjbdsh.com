<?php
namespace Behavior;
class CronRunBehavior
{
	public function run(&$params)
	{
		$lockfile = RUNTIME_PATH . 'cron.lock';
		if (is_writable($lockfile) && filemtime($lockfile) > $_SERVER['REQUEST_TIME'] - C('CRON_MAX_TIME', null, 60)) {
			return;
		} else {
			touch($lockfile);
		}
		set_time_limit(1000);
		ignore_user_abort(true);
		if (is_file(RUNTIME_PATH . '~crons.php')) {
			$crons = include RUNTIME_PATH . '~crons.php';
		} elseif (is_file(COMMON_PATH . 'Conf/crons.php')) {
			$crons = include COMMON_PATH . 'Conf/crons.php';
		}
		if (isset($crons) && is_array($crons)) {
			$update = false;
			$log = array();
			foreach ($crons as $key => $cron) {
				if (empty($cron[2]) || $_SERVER['REQUEST_TIME'] >= $cron[2]) {
					G('cronStart');
					include COMMON_PATH . 'Cron/' . $cron[0] . '.php';
					G('cronEnd');
					$_useTime = G('cronStart', 'cronEnd', 6);
					$cron[2] = $_SERVER['REQUEST_TIME'] + $cron[1];
					$crons[$key] = $cron;
					$log[] = "Cron:$key Runat " . date('Y-m-d H:i:s') . " Use $_useTime s\n";
					$update = true;
				}
			}
			if ($update) {
				\Think\Log::write(implode('', $log));
				$content = "<?php\nreturn " . var_export($crons, true) . ";\n?>";
				file_put_contents(RUNTIME_PATH . '~crons.php', $content);
			}
		}
		unlink($lockfile);
		return;
	}
}