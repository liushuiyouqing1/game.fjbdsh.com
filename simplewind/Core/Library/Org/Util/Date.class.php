<?php
namespace Org\Util;
class Date
{
	protected $date;
	protected $timezone;
	protected $year;
	protected $month;
	protected $day;
	protected $hour;
	protected $minute;
	protected $second;
	protected $weekday;
	protected $cWeekday;
	protected $yDay;
	protected $cMonth;
	protected $CDATE;
	protected $YMD;
	protected $CTIME;
	protected $Week = array("日", "一", "二", "三", "四", "五", "六");

	public function __construct($date = '')
	{
		$this->date = $this->parse($date);
		$this->setDate($this->date);
	}

	public function parse($date)
	{
		if (is_string($date)) {
			if (($date == "") || strtotime($date) == -1) {
				$tmpdate = time();
			} else {
				$tmpdate = strtotime($date);
			}
		} elseif (is_null($date)) {
			$tmpdate = time();
		} elseif (is_numeric($date)) {
			$tmpdate = $date;
		} else {
			if (get_class($date) == "Date") {
				$tmpdate = $date->date;
			} else {
				$tmpdate = time();
			}
		}
		return $tmpdate;
	}

	public function valid($date)
	{
	}

	public function setDate($date)
	{
		$dateArray = getdate($date);
		$this->date = $dateArray[0];
		$this->second = $dateArray["seconds"];
		$this->minute = $dateArray["minutes"];
		$this->hour = $dateArray["hours"];
		$this->day = $dateArray["mday"];
		$this->month = $dateArray["mon"];
		$this->year = $dateArray["year"];
		$this->weekday = $dateArray["wday"];
		$this->cWeekday = '星期' . $this->Week[$this->weekday];
		$this->yDay = $dateArray["yday"];
		$this->cMonth = $dateArray["month"];
		$this->CDATE = $this->format("%Y-%m-%d");
		$this->YMD = $this->format("%Y%m%d");
		$this->CTIME = $this->format("%H:%M:%S");
		return;
	}

	public function format($format = "%Y-%m-%d %H:%M:%S")
	{
		return strftime($format, $this->date);
	}

	public function isLeapYear($year = '')
	{
		if (empty($year)) {
			$year = $this->year;
		}
		return ((($year % 4) == 0) && (($year % 100) != 0) || (($year % 400) == 0));
	}

	public function dateDiff($date, $elaps = "d")
	{
		$__DAYS_PER_WEEK__ = (7);
		$__DAYS_PER_MONTH__ = (30);
		$__DAYS_PER_YEAR__ = (365);
		$__HOURS_IN_A_DAY__ = (24);
		$__MINUTES_IN_A_DAY__ = (1440);
		$__SECONDS_IN_A_DAY__ = (86400);
		$__DAYSELAPS = ($this->parse($date) - $this->date) / $__SECONDS_IN_A_DAY__;
		switch ($elaps) {
			case "y":
				$__DAYSELAPS = $__DAYSELAPS / $__DAYS_PER_YEAR__;
				break;
			case "M":
				$__DAYSELAPS = $__DAYSELAPS / $__DAYS_PER_MONTH__;
				break;
			case "w":
				$__DAYSELAPS = $__DAYSELAPS / $__DAYS_PER_WEEK__;
				break;
			case "h":
				$__DAYSELAPS = $__DAYSELAPS * $__HOURS_IN_A_DAY__;
				break;
			case "m":
				$__DAYSELAPS = $__DAYSELAPS * $__MINUTES_IN_A_DAY__;
				break;
			case "s":
				$__DAYSELAPS = $__DAYSELAPS * $__SECONDS_IN_A_DAY__;
				break;
		}
		return $__DAYSELAPS;
	}

	public function timeDiff($time, $precision = false)
	{
		if (!is_numeric($precision) && !is_bool($precision)) {
			static $_diff = array('y' => '年', 'M' => '个月', 'd' => '天', 'w' => '周', 's' => '秒', 'h' => '小时', 'm' => '分钟');
			return ceil($this->dateDiff($time, $precision)) . $_diff[$precision] . '前';
		}
		$diff = abs($this->parse($time) - $this->date);
		static $chunks = array(array(31536000, '年'), array(2592000, '个月'), array(604800, '周'), array(86400, '天'), array(3600, '小时'), array(60, '分钟'), array(1, '秒'));
		$count = 0;
		$since = '';
		for ($i = 0; $i < count($chunks); $i++) {
			if ($diff >= $chunks[$i][0]) {
				$num = floor($diff / $chunks[$i][0]);
				$since .= sprintf('%d' . $chunks[$i][1], $num);
				$diff = (int)($diff - $chunks[$i][0] * $num);
				$count++;
				if (!$precision || $count >= $precision) {
					break;
				}
			}
		}
		return $since . '前';
	}

	public function getDayOfWeek($n)
	{
		$week = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday');
		return (new Date($week[$n]));
	}

	public function firstDayOfWeek()
	{
		return $this->getDayOfWeek(1);
	}

	public function firstDayOfMonth()
	{
		return (new Date(mktime(0, 0, 0, $this->month, 1, $this->year)));
	}

	public function firstDayOfYear()
	{
		return (new Date(mktime(0, 0, 0, 1, 1, $this->year)));
	}

	public function lastDayOfWeek()
	{
		return $this->getDayOfWeek(0);
	}

	public function lastDayOfMonth()
	{
		return (new Date(mktime(0, 0, 0, $this->month + 1, 0, $this->year)));
	}

	public function lastDayOfYear()
	{
		return (new Date(mktime(0, 0, 0, 1, 0, $this->year + 1)));
	}

	public function maxDayOfMonth()
	{
		$result = $this->dateDiff(strtotime($this->dateAdd(1, 'm')), 'd');
		return $result;
	}

	public function dateAdd($number = 0, $interval = "d")
	{
		$hours = $this->hour;
		$minutes = $this->minute;
		$seconds = $this->second;
		$month = $this->month;
		$day = $this->day;
		$year = $this->year;
		switch ($interval) {
			case "yyyy":
				$year += $number;
				break;
			case "q":
				$month += ($number * 3);
				break;
			case "m":
				$month += $number;
				break;
			case "y":
			case "d":
			case "w":
				$day += $number;
				break;
			case "ww":
				$day += ($number * 7);
				break;
			case "h":
				$hours += $number;
				break;
			case "n":
				$minutes += $number;
				break;
			case "s":
				$seconds += $number;
				break;
		}
		return (new Date(mktime($hours, $minutes, $seconds, $month, $day, $year)));
	}

	public function numberToCh($number)
	{
		$number = intval($number);
		$array = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
		$str = '';
		if ($number == 0) {
			$str .= "十";
		}
		if ($number < 10) {
			$str .= $array[$number - 1];
		} elseif ($number < 20) {
			$str .= "十" . $array[$number - 11];
		} elseif ($number < 30) {
			$str .= "二十" . $array[$number - 21];
		} else {
			$str .= "三十" . $array[$number - 31];
		}
		return $str;
	}

	public function yearToCh($yearStr, $flag = false)
	{
		$array = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
		$str = $flag ? '公元' : '';
		for ($i = 0; $i < 4; $i++) {
			$str .= $array[substr($yearStr, $i, 1)];
		}
		return $str;
	}

	public function magicInfo($type)
	{
		$result = '';
		$m = $this->month;
		$y = $this->year;
		$d = $this->day;
		switch ($type) {
			case 'XZ':
				$XZDict = array('摩羯', '宝瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
				$Zone = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
				if ((100 * $m + $d) >= $Zone[0] || (100 * $m + $d) < $Zone[1]) $i = 0; else for ($i = 1; $i < 12; $i++) {
					if ((100 * $m + $d) >= $Zone[$i] && (100 * $m + $d) < $Zone[$i + 1]) break;
				}
				$result = $XZDict[$i] . '座';
				break;
			case 'GZ':
				$GZDict = array(array('甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'), array('子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'));
				$i = $y - 1900 + 36;
				$result = $GZDict[0][$i % 10] . $GZDict[1][$i % 12];
				break;
			case 'SX':
				$SXDict = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
				$result = $SXDict[($y - 4) % 12];
				break;
		}
		return $result;
	}

	public function __toString()
	{
		return $this->format();
	}
}