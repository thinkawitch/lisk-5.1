<?php
/**
 * CLASS Paging
 * @package lisk
 *
 */
class Paging
{
    const TYPE_EXTENDED = 1;
    const TYPE_STANDART = 2;
    
    const STATUS_OFF = 0;
    const STATUS_ON = 1;
    private $status;
     
	public $itemsTotal;    // total number of items by current select
	public $pagesTotal;    // total number of pages

	public $offset;			// SQL "LIMIT" from value

	private $itemsPerPage;	// items per page
	public $pagesPerPage;	// pages per page (f.e. if we have 45 pages)

	public $pcp;			// Paging Current Page value
	public $pi;             // page items count, user selected value

	private $type;
	
	public $showFrom;		// show start from
	public $showTo;			// show end to

	/**
	 * @var Template
	 */
	private $tpl;

	function __construct()
	{
        $this->status = self::STATUS_OFF;
		$this->pcp = isset($_GET['pcp']) ? intval($_GET['pcp']) : 0;
		$this->pi = isset($_GET['pi']) ? $_GET['pi'] : null;
	}

	public function SwitchOn($name)
	{
		$settings = $this->GetSettings($name);
		
		$this->itemsPerPage = $settings['items_per_page'];
		$this->pagesPerPage = $settings['pages_per_page'];
		$this->type = $settings['paging_type'];
		
		$this->ApplyUserItemsPerPage();
		
		$this->status = self::STATUS_ON;
	}
	
    public function SwitchOff()
	{
		$this->status = self::STATUS_OFF;
		$this->itemsTotal = null;
		$this->tpl = null;
	}
    
	public function IsOn()
	{
	    return $this->status == self::STATUS_ON;
	}
    
	private function ApplyUserItemsPerPage()
	{
	    if ($this->type == self::TYPE_EXTENDED && isset($this->pi))
		{
		    //apply user selected items limit
		    if ($this->pi == 'all') $this->itemsPerPage = 10000;
		    else $this->itemsPerPage = intval($this->pi);
		}
	}
	
	public function SetItemsPerPage($count)
	{
	    $this->itemsPerPage = $count;
	    $this->ApplyUserItemsPerPage();
	}
	
	public function GetItemsPerPage()
	{
	    return $this->itemsPerPage;
	}
	
	public function Calculate()
	{
		$this->offset = 0;

		if ($this->itemsPerPage == 0) $this->itemsPerPage = 10000;

		// get number of pages
		$this->pagesTotal = round($this->itemsTotal / $this->itemsPerPage);
		if ($this->pagesTotal * $this->itemsPerPage < $this->itemsTotal)
		{
			$this->pagesTotal++;
		}

		if ($this->pcp >= $this->pagesTotal) $this->pcp = $this->pagesTotal - 1;
		if ($this->pcp < 0) $this->pcp = 0;

		if ($this->pagesTotal > 1)
		{
			$this->offset = $this->pcp * $this->itemsPerPage;
		}

		//Init show From and show To
		$this->showFrom = $this->offset + 1;
		$this->showTo   = $this->showFrom - 1 + $this->itemsPerPage;

		if ($this->showTo > $this->itemsTotal)
		{
			$this->showTo = $this->itemsTotal;
		}
	}

	

	public function Render($staticUrl=null, $template=null)
	{
		if ($this->status != self::STATUS_ON)  return '';

		if ($staticUrl === null) $staticUrl = defined('STATIC_URLS') && STATIC_URLS == true;

		// arrange tpl
		$this->tpl = new Template();
		
		if ($template == null) $template = 'paging'.$this->type;
		
		$tplName = $this->tpl->GetSystemTemplateFile($template);
		$this->tpl->LoadTemplatefile($tplName);

		switch ($this->type)
		{
			case self::TYPE_EXTENDED:
				$rez = $this->RenderExtended($staticUrl);
				break;
				
			case self::TYPE_STANDART:
				$rez = $this->RenderStandart($staticUrl);
				break;
				
			default:
			    $rez = '';
		}

		$this->SwitchOff();
		
		return $rez;
	}

	private function PrepareRange()
	{
	    $current = $this->pcp + 1;
		$startAdd = 0;
		$shift = ceil($this->pagesPerPage / 2);
		$limitLeft = $current - $shift;
		
		if ($limitLeft < 0)
		{
			$limitLeft = $current;

			if ($this->pagesPerPage >= $shift - $limitLeft)
			{
				$startAdd = $shift - $limitLeft;
			}
			else
			{
				$startAdd = 0;
			}

			$start = 1;
		}
		else
		{
			$limitLeft = $shift;
			$start = $current - $shift + 1;
		}

		$limitRight = $this->pagesTotal - $current;

		if ($limitRight < ($this->pagesPerPage/2))
		{
			$limitRight = $this->pagesTotal - $current;
			$end = $this->pagesTotal;
			$start -= $shift - ($this->pagesTotal - $current) - ($this->pagesPerPage%2);
		}
		else
		{
			$limitRight = $shift;
			$end = $current + $shift - ($this->pagesPerPage%2) + $startAdd;
		}

		if ($start < 1) $start = 1;
		if ($end > $this->pagesTotal) $end = $this->pagesTotal;
		if ($end < 1) $end = 1;
		
		return array($start, $end);
	}

	private function RenderStandart($static)
	{
		$tpl = $this->tpl;

		$staticUri = isset($GLOBALS['url']) ? $GLOBALS['url'] : '';

		if (substr($staticUri, -1, 1) != '/') $staticUri .= '/';

		// parse first and prev links/buttons
		if ($this->pcp > 0)
		{
			$tpl->ParseVariable(array(
				'href'	=> ($static) ? $staticUri.'page_1/' : Navigation::AddGetVariable(array('pcp' => 0))
			), 'first');

			$tpl->ParseVariable(array(
				'href'	=> ($static) ? $staticUri.'page_'.($this->pcp).'/' : Navigation::AddGetVariable(array('pcp'=>($this->pcp - 1)))
			), 'prev');
		}
		else
		{
			$tpl->TouchBlock('first_empty');
			$tpl->TouchBlock('prev_empty');
		}

		list($start, $end) = $this->PrepareRange();
		
        // parse inner links
		for ($i=$start; $i<=$end; $i++)
		{
		    if ($static) $href = $staticUri.'page_'.$i.'/';
		    else $href = Navigation::AddGetVariable(array('pcp'=> $i-1));

		    $block = ($i != ($this->pcp + 1)) ? 'page' : 'cur_page';
		    
		    $tpl->ParseVariable(array(
				'number' => $i,
				'href' => $href
			), $block);
		    
			
			if ($i != $end)
			{
				$tpl->TouchBlock('page_separator');
			}
			
			$tpl->SetCurrentBlock('pages');
			$tpl->ParseCurrentBlock();
		}

		// make next, last buttons
		if ($this->pcp < ($this->pagesTotal-1))
		{
			$tpl->ParseVariable(array(
				'href'	=> ($static) ? $staticUri.'page_'.($this->pcp + 2).'/' : Navigation::AddGetVariable(array('pcp'=>($this->pcp+1)))
			), 'next');

			$tpl->ParseVariable(array(
				'href'	=> ($static) ? $staticUri.'page_'.($this->pagesTotal).'/' : Navigation::AddGetVariable(array('pcp'=>($this->pagesTotal-1)))
			), 'last');
		}
		else
		{
			$tpl->TouchBlock('next_empty');
			$tpl->TouchBlock('last_empty');
		}

		$tpl->SetCurrentBlock('paging');
		$tpl->ParseCurrentBlock();

		return $tpl->Get();
	}
	
    private function RenderExtended($static)
	{
		$tpl = $this->tpl;

		$staticUri = isset($GLOBALS['url']) ? $GLOBALS['url'] : '';

		if (substr($staticUri, -1, 1) != '/') $staticUri .= '/';
		
		$pageItems = $this->itemsPerPage;

		// parse first and prev links/buttons
		if ($this->pcp > 0)
		{
		    if ($static) $href = $staticUri.'page_1/pi_'.$pageItems.'/';
		    else $href = Navigation::AddGetVariable(array('pcp'=>0, 'pi' => $pageItems));
			$tpl->ParseVariable(array('href' => $href), 'first');
            
			if ($static) $href = $staticUri.'page_'.($this->pcp).'/pi_'.$pageItems.'/';
		    else $href = Navigation::AddGetVariable(array('pcp'=> $this->pcp - 1, 'pi' => $pageItems));
		    $tpl->ParseVariable(array('href' => $href), 'prev');
		}
		else
		{
			$tpl->TouchBlock('first_empty');
			$tpl->TouchBlock('prev_empty');
		}

		list($start, $end) = $this->PrepareRange();
		
		// parse inner links
		for ($i=$start; $i<=$end; $i++)
		{
		    if ($static) $href = $staticUri.'page_'.$i.'/pi_'.$pageItems.'/';
		    else $href = Navigation::AddGetVariable(array('pcp'=> $i-1, 'pi' => $pageItems));

		    $block = ($i != ($this->pcp + 1)) ? 'page' : 'cur_page';
		    
		    $tpl->ParseVariable(array(
				'number' => $i,
				'href' => $href
			), $block);
		    
			
			if ($i != $end)
			{
				$tpl->TouchBlock('page_separator');
			}
			
			$tpl->SetCurrentBlock('pages');
			$tpl->ParseCurrentBlock();
		}


		// parse next, last buttons
		if ($this->pcp < ($this->pagesTotal-1))
		{
		    if ($static) $href = $staticUri.'page_'.($this->pcp+2).'/pi_'.$pageItems.'/';
		    else $href = Navigation::AddGetVariable(array('pcp'=> $this->pcp + 1, 'pi' => $pageItems));
		    $tpl->ParseVariable(array('href' => $href), 'next');
            
		    if ($static) $href = $staticUri.'page_'.($this->pagesTotal).'/pi_'.$pageItems.'/';
		    else $href = Navigation::AddGetVariable(array('pcp'=> $this->pagesTotal - 1, 'pi' => $pageItems));
		    $tpl->ParseVariable(array('href' => $href), 'last');
		}
		else
		{
			$tpl->TouchBlock('next_empty');
			$tpl->TouchBlock('last_empty');
		}
		
		// parse user selection
		if ($static) $baseUrl = $staticUri.'page_1/';
		else
		{
		    unset($_GET['pi']);
		    $baseUrl = Navigation::AddGetVariable(array('pcp'=> 0));
		    if (isset($this->pi)) $_GET['pi'] = $this->pi;
		}
		$options = '';
		$configOpts = array(
		    '10' => '10',
		    '20' => '20',
		    '50' => '50',
		    '100' => '100',
		    'all' => 'all',
		);
		foreach ($configOpts as $k=>$v)
		{
		    $selected = $k == $this->pi ? 'selected="selected"' : '';
		    $options .= '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
		}
		$tpl->ParseVariable(array('options' => $options, 'base_url' => $baseUrl ), 'user_selection');

		$tpl->SetCurrentBlock('paging');
		$tpl->ParseCurrentBlock();

		return $tpl->Get();
	}
	
    private function GetSettings($name)
	{
		GLOBAL $App, $Db;
		if (isset($GLOBALS['DEFAULT_PAGING'][$name]))
		{
		    return $GLOBALS['DEFAULT_PAGING'][$name];
		}
		else
		{
            $settings = $Db->Get('name='.Database::Escape($name), null, 'sys_paging');
		    if (!Utils::IsArray($settings)) $App->RaiseError("Paging $name is undefined.");
		    return $settings;
		}
	}

}

$GLOBALS['Paging'] = new Paging();

?>