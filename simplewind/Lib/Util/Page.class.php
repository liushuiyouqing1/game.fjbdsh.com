<?php

class Page
{
	private $Page_size;
	private $Total_Size;
	private $Current_page;
	private $List_Page;
	private $Total_Pages = 20;
	private $Page_tpl = array();
	private $PageParam;
	private $PageLink;
	private $Static;
	private $pList;
	private $pListEnd;
	private $pListStart;
	private $pFirst;
	private $pPrev;
	private $pLast;
	private $pNext;
	public $firstRow;
	public $listRows;
	private $linkwraper = "";
	private $linkwraper_pre = "";
	private $linkwraper_after = "";
	private $searching = false;

	function __construct($Total_Size = 1, $Page_Size = 20, $Current_Page = 1, $List_Page = 6, $PageParam = 'p', $PageLink = '', $Static = FALSE)
	{
		$this->Page_size = intval($Page_Size);
		$this->Total_Size = intval($Total_Size);
		if (!$Current_Page) {
			$this->Current_page = 1;
		} else {
			$this->Current_page = (int)$Current_Page < 1 ? 1 : (int)$Current_Page;
		}
		$this->Total_Pages = ceil($Total_Size / $Page_Size);
		$this->List_Page = (int)$List_Page;
		$this->PageParam = $PageParam;
		$this->PageLink = (empty($PageLink) ? $_SERVER ["PHP_SELF"] : $PageLink);
		$this->Static = $Static;
		$this->Page_tpl ['default'] = array('Tpl' => '<div class="pager">{first}{prev}{liststart}{list}{listend}{next}{last} 跳转到{jump}页</div>', 'Config' => array());
		$this->GetCurrentPage();
		$this->listRows = $Page_Size;
		$this->firstRow = ($this->Current_page - 1) * $this->listRows;
		if ($this->firstRow < 0) {
			$this->firstRow = 0;
		}
	}

	public function __set($Param, $value)
	{
		$this->$Param = $value;
	}

	public function __get($Param)
	{
		return $this->$Param;
	}

	public function getTotalPages()
	{
		return $this->Total_Pages;
	}

	public function setLinkWraper($wraper)
	{
		if (empty($wraper)) {
		} else {
			$this->linkwraper = $wraper;
			$this->linkwraper_after = "</$wraper>";
			$this->linkwraper_pre = "<$wraper>";
		}
	}

	private function UrlParameters($url = array())
	{
		unset($url[C('VAR_MODULE')]);
		unset($url[C('VAR_CONTROLLER')]);
		unset($url[C('VAR_ACTION')]);
		foreach ($url as $key => $val) {
			if ($key != $this->PageParam && $key != "_URL_") $arg [$key] = $val;
		}
		$arg[$this->PageParam] = '*';
		if ($this->Static) {
			if (is_array($this->PageLink)) {
				return str_replace('{page}', '*', $this->PageLink['list']);
			} else {
				return str_replace('{page}', '*', $this->PageLink);
			}
		} else {
			if ($this->searching) {
				$url = leuu(MODULE_NAME . "/" . CONTROLLER_NAME . "/" . ACTION_NAME) . "?" . http_build_query($arg);
			} else {
				$url = leuu(MODULE_NAME . "/" . CONTROLLER_NAME . "/" . ACTION_NAME, $arg);
			}
			return str_replace("%2A", "*", $url);
		}
	}

	public function SetPager($Tpl_Name = 'default', $Tpl = '', $Config = array())
	{
		if (empty($Tpl)) $Tpl = $this->Page_tpl ['default'] ['Tpl'];
		if (empty($Config)) $Config = $this->Page_tpl ['default'] ['Config'];
		$this->Page_tpl [$Tpl_Name] = array('Tpl' => $Tpl, 'Config' => $Config);
	}

	public function show($Tpl_Name = 'default')
	{
		if ($this->Total_Pages <= 1) {
			return;
		}
		return $this->Pager($this->Page_tpl [$Tpl_Name]);
	}

	public function GetCurrentPage()
	{
		$p = isset($_GET [$this->PageParam]) ? intval($_GET [$this->PageParam]) : 1;
		$p = $p < 1 ? 1 : $p;
		$total_pages = intval($this->Total_Pages);
		$this->Current_page = ($p <= $total_pages ? $p : $total_pages);
	}

	public function Pager($Page_tpl = '')
	{
		if (empty($Page_tpl)) $Page_tpl = $this->Page_tpl ['default'];
		$_GET = array_merge($_GET, $_POST);
		$cfg = array('recordcount' => intval($this->Total_Size), 'pageindex' => intval($this->Current_page), 'pagecount' => intval($this->Total_Pages), 'pagesize' => intval($this->Page_size), 'listlong' => intval($this->List_Page), 'listsidelong' => 2, 'list' => '*', 'currentclass' => 'current', 'link' => $this->UrlParameters($_GET), 'first' => '&laquo;', 'prev' => '&#8249;', 'next' => '&#8250;', 'last' => '&raquo;', 'more' => $this->linkwraper_pre . '<span>...</span>' . $this->linkwraper_after, 'disabledclass' => 'disabled', 'jump' => 'input', 'jumpplus' => '', 'jumpaction' => '', 'jumplong' => 50);
		if (!empty($Page_tpl ['Config'])) {
			foreach ($Page_tpl ['Config'] as $key => $val) {
				if (array_key_exists($key, $cfg)) $cfg [$key] = $val;
			}
		}
		if ((int)$cfg ['listlong'] % 2 != 0) {
			$cfg ['listlong'] = $cfg ['listlong'] + 1;
		}
		$tmpStr = $Page_tpl ['Tpl'];
		$pStart = $cfg ['pageindex'] - (($cfg ['listlong'] / 2) + ($cfg ['listlong'] % 2)) + 1;
		$pEnd = $cfg ['pageindex'] + $cfg ['listlong'] / 2;
		if ($pStart < 1) {
			$pStart = 1;
			$pEnd = $cfg ['listlong'];
		}
		if ($pEnd > $cfg ['pagecount']) {
			$pStart = $cfg ['pagecount'] - $cfg ['listlong'] + 1;
			$pEnd = $cfg ['pagecount'];
		}
		if ($pStart < 1) $pStart = 1;
		for ($i = $pStart; $i <= $pEnd; $i++) {
			if ($i == $cfg ['pageindex']) {
				$wraper = empty($this->linkwraper) ? '' : '<' . $this->linkwraper . ' class="active ' . $cfg ['currentclass'] . '">';
				$this->pList .= $wraper . '<span class="' . $cfg ['currentclass'] . '" >' . str_replace('*', $i, $cfg ['list']) . '</span> ' . $this->linkwraper_after;
			} else {
				if ($this->Static && $i == 1) {
					$this->pList .= $this->linkwraper_pre . '<a href="' . $this->PageLink['index'] . '"> ' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
				} else {
					$this->pList .= $this->linkwraper_pre . '<a href="' . str_replace('*', $i, $cfg ['link']) . '"> ' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
				}
			}
		}
		if ($cfg ['listsidelong'] > 0) {
			if ($cfg ['listsidelong'] < $pStart) {
				for ($i = 1; $i <= $cfg ['listsidelong']; $i++) {
					if ($this->Static && $i == 1) {
						$this->pListStart .= $this->linkwraper_pre . '<a href="' . $this->PageLink['index'] . '">' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
					} else {
						$this->pListStart .= $this->linkwraper_pre . '<a href="' . str_replace('*', $i, $cfg ['link']) . '">' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
					}
				}
				$this->pListStart .= ($cfg ['listsidelong'] + 1) == $pStart ? '' : $cfg ['more'] . ' ';
			} else {
				if ($cfg ['listsidelong'] >= $pStart && $pStart > 1) {
					for ($i = 1; $i <= ($pStart - 1); $i++) {
						if ($this->Static && $i == 1) {
							$this->pListStart .= $this->linkwraper_pre . '<a href="' . $this->PageLink['index'] . '"> ' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
						} else {
							$this->pListStart .= $this->linkwraper_pre . '<a href="' . str_replace('*', $i, $cfg ['link']) . '"> ' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
						}
					}
				}
			}
			if (($cfg ['pagecount'] - $cfg ['listsidelong']) > $pEnd) {
				$this->pListEnd = ' ' . $cfg ['more'] . $this->pListEnd;
				for ($i = (($cfg ['pagecount'] - $cfg ['listsidelong']) + 1); $i <= $cfg ['pagecount']; $i++) {
					if ($this->Static && $i == 1) {
						$this->pListEnd .= $this->linkwraper_pre . '<a href="' . $this->PageLink['index'] . '">' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
					} else {
						$this->pListEnd .= $this->linkwraper_pre . '<a href="' . str_replace('*', $i, $cfg ['link']) . '"> ' . str_replace('*', $i, $cfg ['list']) . ' </a> ' . $this->linkwraper_after;
					}
				}
			} else {
				if (($cfg ['pagecount'] - $cfg ['listsidelong']) <= $pEnd && $pEnd < $cfg ['pagecount']) {
					for ($i = ($pEnd + 1); $i <= $cfg ['pagecount']; $i++) {
						if ($this->Static && $i == 1) {
							$this->pListEnd .= '<a href="' . $this->PageLink['index'] . '">' . str_replace('*', $i, $cfg ['list']) . '</a> ' . $this->linkwraper_after;
						} else {
							$this->pListEnd .= $this->linkwraper_pre . '<a href="' . str_replace('*', $i, $cfg ['link']) . '"> ' . str_replace('*', $i, $cfg ['list']) . ' </a> ' . $this->linkwraper_after;
						}
					}
				}
			}
		}
		if ($cfg ['pageindex'] > 1) {
			if ($this->Static) {
				$this->pFirst = $this->linkwraper_pre . '<a href="' . $this->PageLink['index'] . '">' . $cfg ['first'] . '</a> ' . $this->linkwraper_after;
			} else {
				$this->pFirst = $this->linkwraper_pre . '<a href="' . str_replace('*', 1, $cfg ['link']) . '">' . $cfg ['first'] . '</a> ' . $this->linkwraper_after;
			}
			if ($this->Static && ($cfg ['pageindex'] - 1) == 1) {
				$this->pPrev = $this->linkwraper_pre . '<a href="' . $this->PageLink['index'] . '">' . $cfg ['prev'] . '</a> ' . $this->linkwraper_after;
			} else {
				$this->pPrev = $this->linkwraper_pre . '<a href="' . str_replace('*', $cfg ['pageindex'] - 1, $cfg ['link']) . '">' . $cfg ['prev'] . '</a> ' . $this->linkwraper_after;
			}
		}
		if ($cfg ['pageindex'] < $cfg ['pagecount']) {
			$this->pLast = $this->linkwraper_pre . '<a href="' . str_replace('*', $cfg ['pagecount'], $cfg ['link']) . '">' . $cfg ['last'] . '</a> ' . $this->linkwraper_after;
			$this->pNext = $this->linkwraper_pre . '<a href="' . str_replace('*', $cfg ['pageindex'] + 1, $cfg ['link']) . '">' . $cfg ['next'] . '</a> ' . $this->linkwraper_after;
		}
		switch (strtolower($cfg ['jump'])) {
			case 'input' :
				$pJumpValue = 'this.value';
				$pJump = '<input type="text" size="3" title="请输入要跳转到的页数并回车"' . (($cfg ['jumpplus'] == '') ? '' : ' ' . $cfg ['jumpplus']);
				$pJump .= ' onkeydown="javascript:if(event.charCode==13||event.keyCode==13){if(!isNaN(' . $pJumpValue . ')){';
				$pJump .= ($cfg ['jumpaction'] == '' ? ((strtolower(substr($cfg ['link'], 0, 11)) == 'javascript:') ? str_replace('*', $pJumpValue, substr($cfg ['link'], 12)) : " document.location.href='" . str_replace('*', '\'+' . $pJumpValue . '+\'', $cfg ['link']) . '\';') : str_replace("*", $pJumpValue, $cfg ['jumpaction']));
				$pJump .= '}return false;}" />';
				break;
			case 'select' :
				$pJumpValue = "this.options[this.selectedIndex].value";
				$pJump = '<select ' . ($cfg ['jumpplus'] == '' ? ' ' . $cfg ['jumpplus'] . ' onchange="javascript:' : $cfg ['jumpplus']);
				$pJump .= ($cfg ['jumpaction'] == '' ? ((strtolower(substr($cfg ['link'], 0, 11)) == 'javascript:') ? str_replace('*', $pJumpValue, substr($cfg ['link'], 12)) : " document.location.href='" . str_replace('*', '\'+' . $pJumpValue . '+\'', $cfg ['link']) . '\';') : str_replace("*", $pJumpValue, $cfg ['jumpaction']));
				$pJump .= '" title="请选择要跳转到的页数"> ';
				if ($cfg ['jumplong'] == 0) {
					for ($i = 0; $i <= $cfg ['pagecount']; $i++) {
						$pJump .= '<option value="' . $i . '"' . (($i == $cfg ['pageindex']) ? ' selected="selected"' : '') . ' >' . $i . '</option> ';
					}
				} else {
					$pJumpLong = intval($cfg ['jumplong'] / 2);
					$pJumpStart = ((($cfg ['pageindex'] - $pJumpLong) < 1) ? 1 : ($cfg ['pageindex'] - $pJumpLong));
					$pJumpStart = ((($cfg ['pagecount'] - $cfg ['pageindex']) < $pJumpLong) ? ($pJumpStart - ($pJumpLong - ($cfg ['pagecount'] - $cfg ['pageindex'])) + 1) : $pJumpStart);
					$pJumpStart = (($pJumpStart < 1) ? 1 : $pJumpStart);
					$j = 1;
					for ($i = $pJumpStart; $i <= $cfg ['pageindex']; $i++, $j++) {
						$pJump .= '<option value="' . $i . '"' . (($i == $cfg ['pageindex']) ? ' selected="selected"' : '') . '>' . $i . '</option> ';
					}
					$pJumpLong = $cfg ['pagecount'] - $cfg ['pageindex'] < $pJumpLong ? $pJumpLong : $pJumpLong + ($pJumpLong - $j) + 1;
					$pJumpEnd = $cfg ['pageindex'] + $pJumpLong > $cfg ['pagecount'] ? $cfg ['pagecount'] : $cfg ['pageindex'] + $pJumpLong;
					for ($i = $cfg ['pageindex'] + 1; $i <= $pJumpEnd; $i++) {
						$pJump .= '<option value="' . $i . '">' . $i . '</option> ';
					}
				}
				$pJump .= '</select>';
				break;
		}
		$patterns = array('/{recordcount}/', '/{pagecount}/', '/{pageindex}/', '/{pagesize}/', '/{list}/', '/{liststart}/', '/{listend}/', '/{first}/', '/{prev}/', '/{next}/', '/{last}/', '/{jump}/');
		$replace = array($cfg ['recordcount'], $cfg ['pagecount'], $cfg ['pageindex'], $cfg ['pagesize'], $this->pList, $this->pListStart, $this->pListEnd, $this->pFirst, $this->pPrev, $this->pNext, $this->pLast, $pJump);
		$tmpStr = chr(13) . chr(10) . preg_replace($patterns, $replace, $tmpStr) . chr(13) . chr(10);
		unset($cfg);
		return $tmpStr;
	}
} 