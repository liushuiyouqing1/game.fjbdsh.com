<?php
namespace Think;
class Page
{
	public $firstRow;
	public $listRows;
	public $parameter;
	public $totalRows;
	public $totalPages;
	public $rollPage = 11;
	public $lastSuffix = true;
	private $p = 'p';
	private $url = '';
	private $nowPage = 1;
	private $config = array('header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>', 'prev' => '<<', 'next' => '>>', 'first' => '1...', 'last' => '...%TOTAL_PAGE%', 'theme' => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',);

	public function __construct($totalRows, $listRows = 20, $parameter = array())
	{
		C('VAR_PAGE') && $this->p = C('VAR_PAGE');
		$this->totalRows = $totalRows;
		$this->listRows = $listRows;
		$this->parameter = empty($parameter) ? $_GET : $parameter;
		$this->nowPage = empty($_GET[$this->p]) ? 1 : intval($_GET[$this->p]);
		$this->nowPage = $this->nowPage > 0 ? $this->nowPage : 1;
		$this->firstRow = $this->listRows * ($this->nowPage - 1);
	}

	public function setConfig($name, $value)
	{
		if (isset($this->config[$name])) {
			$this->config[$name] = $value;
		}
	}

	private function url($page)
	{
		return str_replace(urlencode('[PAGE]'), $page, $this->url);
	}

	public function show()
	{
		if (0 == $this->totalRows) return '';
		$this->parameter[$this->p] = '[PAGE]';
		$this->url = U(ACTION_NAME, $this->parameter);
		$this->totalPages = ceil($this->totalRows / $this->listRows);
		if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
			$this->nowPage = $this->totalPages;
		}
		$now_cool_page = $this->rollPage / 2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->lastSuffix && $this->config['last'] = $this->totalPages;
		$up_row = $this->nowPage - 1;
		$up_page = $up_row > 0 ? '<a class="prev" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '';
		$down_row = $this->nowPage + 1;
		$down_page = ($down_row <= $this->totalPages) ? '<a class="next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '';
		$the_first = '';
		if ($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1) {
			$the_first = '<a class="first" href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
		}
		$the_end = '';
		if ($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages) {
			$the_end = '<a class="end" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
		}
		$link_page = "";
		for ($i = 1; $i <= $this->rollPage; $i++) {
			if (($this->nowPage - $now_cool_page) <= 0) {
				$page = $i;
			} elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
				$page = $this->totalPages - $this->rollPage + $i;
			} else {
				$page = $this->nowPage - $now_cool_page_ceil + $i;
			}
			if ($page > 0 && $page != $this->nowPage) {
				if ($page <= $this->totalPages) {
					$link_page .= '<a class="num" href="' . $this->url($page) . '">' . $page . '</a>';
				} else {
					break;
				}
			} else {
				if ($page > 0 && $this->totalPages != 1) {
					$link_page .= '<span class="current">' . $page . '</span>';
				}
			}
		}
		$page_str = str_replace(array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%'), array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages), $this->config['theme']);
		return "<div>{$page_str}</div>";
	}
} 