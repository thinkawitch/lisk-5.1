<?php
/**
 * CLASS Debug
 * @package lisk
 *
 */
class Debug
{

	public $structure;

	private	$prevDebug;
	private $config;
	private $renders;

	function __construct()
	{
		GLOBAL $App;

		$this->config = array( /* group => color */
			'GENERAL'	=> '#8495BB',
			'GET'		=> '#FFCC66',
			'POST'		=> '#00FF66',
			'FILES'		=> '#CC00CC',
			'SESSION'	=> '#9999FF',
			'COOKIE'	=> '#CC99FF',
			'SQL'		=> '#FF9999',
			'DATA'		=> '#FCFC00',
			'FILESYS'	=> '#FCFC00',
			'AUTH'		=> '#CCCCCC',
			'EMAIL'		=> '#CCCCCC',
			'NAVIGATION'=> '#6DD4FF',
			'DEBUG'		=> '#FFFF66',
			'SCMS'		=> '#FCFC00'
		);

		$this->structure = array();

		//general
		$this->AddDebug('GENERAL', 'script name', $_SERVER['PHP_SELF'], null);
		$this->AddDebug('GENERAL', 'SERVER HTTP_REFERER', @$_SERVER['HTTP_REFERER'], null);
		$this->AddDebug('GENERAL', 'SERVER REQUEST URI', $_SERVER['REQUEST_URI'], null);


		//previous page debugger
		if ($App->debug)
		{
			$this->prevDebug = isset($_SESSION['debug_old']) ? $_SESSION['debug_old'] : null;
			$_SESSION['debug_old'] = '';
		}

		//init standart data / get post cookie
		$arr = array(
			'GET'     => $_GET,
			'POST'    => $_POST,
			'COOKIE'  => $_COOKIE,
			'FILES'	  => $_FILES,
		);

		foreach ($arr as $groupKey=>$groupValue)
		{
			if (Utils::IsArray($groupValue))
			{
				foreach($groupValue as $key=>$value) $this->AddDebug($groupKey, $key, $value, null);
			}
			else
			{
				$this->AddDebug($groupKey, $groupKey, 'empty', null);
			}
		}

		if (isset($_SESSION))
		{
		foreach($_SESSION as $key=>$value)
		{
			if (substr($key, 0, 8) != 'SYS_NAV_') $this->AddDebug('SESSION', $key, $value, null);
			else $this->AddDebug('NAVIGATION', $key, $value, null);
		}
		}

		$this->RemoveDebug('SESSION', 'debug_old');
	}

	public function AddDebug($who, $name, $value, $error)
	{
		$this->structure[$who][] = array(
			'name' => $name,
			'value' => $value,
			'error' => $error,
		);
	}

	public function RemoveDebug($who, $name)
	{
	    if (!isset($this->structure[$who]) || !Utils::IsArray($this->structure[$who])) return;
	    
		foreach ($this->structure[$who] as $k=>$v)
		{
			if ($v['name']==$name)
			{
				unset($this->structure[$who][$k]);
				//remove first encounter
				return;
			}
		}
	}

	private function Init()
	{
		GLOBAL $App;
		$GLOBALS['execTime'] =  sprintf('%0.5f', Utils::GetMicroTime() - $App->startTime);

		$this->AddDebug('GENERAL', 'script execution time', $GLOBALS['execTime'], null);
	}

	private function SaveOldDebug()
	{
		$_SESSION['debug_old'] = $this->structure;
	}

	public function Render()
	{
		GLOBAL $App;
		if ($App->debug === false) return false;

		$this->Init();

		$link = $this->GetPatternLink();
		$htmlLinks = '';
		$div = $this->GetPatternDiv();
		$htmlDivs = '';

		//add sql totals
		if (isset($this->structure['SQL']) && Utils::IsArray($this->structure['SQL']))
		{
			$timeTotal = 0;
			foreach ($this->structure['SQL'] as $node)
			{
				$timeTotal += $node['value'];
			}
			$this->structure['SQL'][] = array(
				'name' 	=> 'Queries Total:',
				'value' => '<b>'.count($this->structure['SQL']).'</b>',
				'error' => '',
			);
			$this->structure['SQL'][] = array(
				'name' 	=> 'SQL Time Total:',
				'value' => '<b>'.$timeTotal,'</b>',
				'error' => '',
			);
		}

		$this->SaveOldDebug();

		//generate _renders
		foreach($this->structure as $who=>$nodes)
		{
			if(Utils::IsArray($nodes) || (isset($this->prevDebug[$who]) && Utils::IsArray($this->prevDebug[$who])))
			{
				$this->renders[] = array(
					'method' => 'RenderVariables',
					'name'   => $who,
					'id'     => uniqid('d', false),
					'data'   => $nodes,
					'prevData' => isset($this->prevDebug[$who]) ? $this->prevDebug[$who] : '',
				);
			}
		}

		//add prev nodes if now there no ones
        if (Utils::IsArray($this->prevDebug))
        {
            foreach($this->prevDebug as $who=>$nodes)
            {
                if (!array_key_exists($who, $this->structure))
                {
                    $this->renders[] = array(
                        'method' => 'RenderVariables',
                        'name'   => $who,
                        'id'     => uniqid('d', false),
                        'data'   => array(),
                        'prevData' => $nodes,
                    );
                }
            }
        }

		//links first
		foreach($this->renders as $render)
		{
			$newLink = $link;
			$newLink = str_replace('{ID}', $render['id'], $newLink);
			$newLink = str_replace('{NAME}', $render['name'], $newLink);
			$htmlLinks .= $newLink;


			$newDiv = $div;
			$newDiv = str_replace('{DIV_TABLE}',
				$this->$render['method']($render['data'], $render['name'])
				.$this->$render['method']($render['prevData'], 'PREVIOUS '.$render['name'])
				,$newDiv
			);
			$newDiv = str_replace('{DIV_ID}', $render['id'], $newDiv);
			$htmlDivs .= $newDiv;
		}

		//
		$base = $this->GetPatternGlobal();
		$base = str_replace('{DEBUG_LINKS}', $htmlLinks, $base);
		$base = str_replace('{DEBUG_DIVS}', $htmlDivs, $base);

		return $base;
	}

	private function RenderVariables($hash, $who)
	{
		$htmlVariables = '';
		
		$newVariables = $this->GetPatternVariables();
		$node['value'] = $this->RenderHash($hash);
		if (strlen($node['value']))
		{
			$node['name'] = $who;
			$node['bgcolor'] = @$this->config[$who];
			$htmlVariables .= $this->StrReplace($newVariables, $node);
		}
		
		return $htmlVariables;
	}

	private function StrReplace($str, $hash)
	{
		if (Utils::IsArray($hash))
		{
			foreach($hash as $k=>$v)
			{
				$str = str_replace('{'.strtoupper($k).'}', $v, $str);
			}
		}
		return $str;
	}

	private function RenderHash($hash)
	{
		$html = '';

		if (Utils::IsArray($hash))
		{
			$html .= '<table border="0" cellspacing="1" cellpadding="2" bgcolor="#000000" width="100%">';
			foreach($hash as $v)
			{
				$htmlError = '';
				if ($v['error']) $htmlError = '<br><span style="color:red">'.$v['error'].'</span>';
				
				if (is_bool($v['value'])) $v['value']=($v['value'])?'true':'false';
				$html .='<tr><td bgcolor="#ffffff">'.$v['name'].$htmlError.'</td><td bgcolor="#ffffff">'.nl2br(print_r($v['value'],true)).'</td></tr>';
			}
			$html .= '</table>';
		}
		return $html;
	}

	private function GetPatternVariables()
	{
		return <<<EOD
<table cellpadding="4" cellspacing="0" class="pattern-vars">
<tr><td bgcolor="{BGCOLOR}"><strong>{NAME}</strong></td></tr>
<tr><td style="padding:0px;">{VALUE}</td></tr>
</table><br>
EOD;
	}

	private function GetPatternDiv()
	{
		return <<<EOD
<div id="div_{DIV_ID}" class="pattern-div">
{DIV_TABLE}
</div>
EOD;
	}

	private function GetPatternLink()
	{
		return <<<EOD
		<strong><a href="#toggle" onclick="$('#div_{ID}').toggle(); return false;">{NAME}</a></strong>
EOD;
	}

	private function GetPatternGlobal()
	{
	    GLOBAL $App;
	    $httpRoot = $App->httpRoot;
		return <<<EOD
<!-- begin_lisk_debug -->
<link href="{$httpRoot}css/lisk/debug.css" rel="stylesheet" type="text/css" />
<div id="debug">
    <div id="debugMenu">
        <table>
        <tr>
        	<td>{DEBUG_LINKS} <a href="#close-all" onclick="$('#debugTabs').hide();$('#debug').hide(); return false;"><strong>x</strong></a></td>
        </tr>
    	</table>
	</div>
	<div id="debugTabs">{DEBUG_DIVS}</div>
</div>
<!-- end_lisk_debug -->
EOD;

	}
}

$GLOBALS['Debug'] = new Debug();

?>