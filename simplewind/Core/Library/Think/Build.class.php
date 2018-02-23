<?php
namespace Think;
class Build
{
	static protected $controller = '<?php
namespace [MODULE]\Controller;
use Think\Controller;
class [CONTROLLER]Controller extends Controller {
    public function index(){
        $this->show(\'<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>\',\'utf-8\');
    }
}';
	static protected $model = '<?php
namespace [MODULE]\Model;
use Think\Model;
class [MODEL]Model extends Model {

}';

	static public function checkDir($module)
	{
		if (!is_dir(APP_PATH . $module)) {
			self::buildAppDir($module);
		} elseif (!is_dir(LOG_PATH)) {
			self::buildRuntime();
		}
	}

	static public function buildAppDir($module)
	{
		if (!is_dir(APP_PATH)) mkdir(APP_PATH, 0755, true);
		if (is_writeable(APP_PATH)) {
			$dirs = array(COMMON_PATH, COMMON_PATH . 'Common/', CONF_PATH, APP_PATH . $module . '/', APP_PATH . $module . '/Common/', APP_PATH . $module . '/Controller/', APP_PATH . $module . '/Model/', APP_PATH . $module . '/Conf/', APP_PATH . $module . '/View/', RUNTIME_PATH, CACHE_PATH, CACHE_PATH . $module . '/', LOG_PATH, LOG_PATH . $module . '/', TEMP_PATH, DATA_PATH,);
			foreach ($dirs as $dir) {
				if (!is_dir($dir)) mkdir($dir, 0755, true);
			}
			self::buildDirSecure($dirs);
			if (!is_file(CONF_PATH . 'config' . CONF_EXT)) file_put_contents(CONF_PATH . 'config' . CONF_EXT, '.php' == CONF_EXT ? "<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);" : '');
			if (!is_file(APP_PATH . $module . '/Conf/config' . CONF_EXT)) file_put_contents(APP_PATH . $module . '/Conf/config' . CONF_EXT, '.php' == CONF_EXT ? "<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);" : '');
			if (defined('BUILD_CONTROLLER_LIST')) {
				$list = explode(',', BUILD_CONTROLLER_LIST);
				foreach ($list as $controller) {
					self::buildController($module, $controller);
				}
			} else {
				self::buildController($module);
			}
			if (defined('BUILD_MODEL_LIST')) {
				$list = explode(',', BUILD_MODEL_LIST);
				foreach ($list as $model) {
					self::buildModel($module, $model);
				}
			}
		} else {
			header('Content-Type:text/html; charset=utf-8');
			exit('应用目录[' . APP_PATH . ']不可写，目录无法自动生成！<BR>请手动生成项目目录~');
		}
	}

	static public function buildRuntime()
	{
		if (!is_dir(RUNTIME_PATH)) {
			mkdir(RUNTIME_PATH);
		} elseif (!is_writeable(RUNTIME_PATH)) {
			header('Content-Type:text/html; charset=utf-8');
			exit('目录 [ ' . RUNTIME_PATH . ' ] 不可写！');
		}
		mkdir(CACHE_PATH);
		if (!is_dir(LOG_PATH)) mkdir(LOG_PATH);
		if (!is_dir(TEMP_PATH)) mkdir(TEMP_PATH);
		if (!is_dir(DATA_PATH)) mkdir(DATA_PATH);
		return true;
	}

	static public function buildController($module, $controller = 'Index')
	{
		$file = APP_PATH . $module . '/Controller/' . $controller . 'Controller' . EXT;
		if (!is_file($file)) {
			$content = str_replace(array('[MODULE]', '[CONTROLLER]'), array($module, $controller), self::$controller);
			if (!C('APP_USE_NAMESPACE')) {
				$content = preg_replace('/namespace\s(.*?);/', '', $content, 1);
			}
			$dir = dirname($file);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			file_put_contents($file, $content);
		}
	}

	static public function buildModel($module, $model)
	{
		$file = APP_PATH . $module . '/Model/' . $model . 'Model' . EXT;
		if (!is_file($file)) {
			$content = str_replace(array('[MODULE]', '[MODEL]'), array($module, $model), self::$model);
			if (!C('APP_USE_NAMESPACE')) {
				$content = preg_replace('/namespace\s(.*?);/', '', $content, 1);
			}
			$dir = dirname($file);
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			file_put_contents($file, $content);
		}
	}

	static public function buildDirSecure($dirs = array())
	{
		defined('BUILD_DIR_SECURE') or define('BUILD_DIR_SECURE', true);
		if (BUILD_DIR_SECURE) {
			defined('DIR_SECURE_FILENAME') or define('DIR_SECURE_FILENAME', 'index.html');
			defined('DIR_SECURE_CONTENT') or define('DIR_SECURE_CONTENT', ' ');
			$content = DIR_SECURE_CONTENT;
			$files = explode(',', DIR_SECURE_FILENAME);
			foreach ($files as $filename) {
				foreach ($dirs as $dir) file_put_contents($dir . $filename, $content);
			}
		}
	}
} 