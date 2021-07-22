<?php

$id = isset($_GET['id']) ? $_GET['id'] : null;

switch ($id)
{
	case 'top':
		require_once('init/init.php');

		class CpTopFramePage extends CPPage
		{
			function Page()
			{
				GLOBAL $Tpl,$Auth,$App;
				$this->SetGlobalTemplate('0');
				$this->LoadTemplate('cms/top');

				$App->debug = false;  // javascript date not working with debug

				$time = time();

				//make time in GMT value
				$diffToGmt = date('O');
				$diffSign = substr($diffToGmt, 0, 1);
				$diffHours  = substr($diffToGmt, 1, 2);
				$diffMinutes = substr($diffToGmt, 3, 2);

				$diffOffset = $diffHours*3600 + $diffMinutes*60;
				if ($diffSign=='+') $time -= $diffOffset;
				else $time += $diffOffset;

				$Tpl->SetVariable(array(
					'user_name'	=> $Auth->user['login'],
					'year'		=> date('Y', $time),
					'month'		=> date('F', $time),
					'day'		=> date('d', $time),
					'hours'		=> date('H', $time),
					'minutes'	=> date('i', $time),
					'seconds'	=> date('s', $time),
					'weekday'	=> date('l', $time),
					'weekday_num' => date('w', $time),
				));
				$this->pageContent = $Tpl->Get();
			}
		}
		
		$Page = new CpTopFramePage();
		$Page->Render();
		
		break;

	case 'menu':
		require_once('init/init.php');
		$App->Load('group', 'cms');
		
		class CpMenuFramePage extends CPPage
		{
			private $CPGroups;

			function __construct()
			{
				parent::__construct();
				$this->App->debug = false;
			}

			function Page()
			{
				GLOBAL $Parser;
				
				$this->CPGroups = new CPGroup();
				$this->CPGroups->GetRights($this->Auth->user['level']);

				$CPMenu = Data::Create('sys_cp_menu');
				$CPMenu->Select("id!=1");
				$values = $CPMenu->values;
                
				$cpMenuCfg = array();
				$this->BuildCPMenu(1, $values, $cpMenuCfg);
				$html = $this->MakeDivs($cpMenuCfg);

				$this->SetGlobalTemplate('0');
				$this->LoadTemplate('cms/menu');
				$Parser->ParseView(array(
					'menu' => $html,
					),
					'view'
				);

				$this->pageContent .= $this->Tpl->Get();
				
			}
			
			function MakeDivs($menu)
			{
				GLOBAL $Parser, $Auth;
				static $c = 0;
				static $level=1;
				
				$rights = $this->CPGroups->values;

				$separator = $Parser->GetHtml('cms/menu', 'separator');
				
				$colors = array(
					'1' => '#D3D3D9',
					'2' => '#F5F5FB',
					'3' => '#FFFFFF',
					'4' => '',
					'5' => '',
					'6' => '',
				);
				
				$padd = 12*$level;
				
				$c++;
				
				if ($c==1) $divId = 'id="menutable"';
				else $divId = '';
				
				$html = '<table border="0" width="100%" cellspacing="0" cellpadding="0" '.$divId.'>';
				foreach ($menu as $name=>$item)
				{
					
					if ($item['link'] == 'node_tree.php?type=sys_cp_menu' && $Auth->user['level'] != LISK_GROUP_DEVELOPERS) continue;
					
					$rKey = str_replace(array(' ','\'','"'),array('_','',''),$name);
					
					if ( (!Utils::IsArray($rights) || !array_key_exists($rKey,$rights) || @$rights[$rKey] == 0)
						 && $Auth->user['level'] != LISK_GROUP_ADMINISTRATORS
						 && $Auth->user['level'] != LISK_GROUP_DEVELOPERS )
					{
						continue;
					}
					
					$html .= $separator;
					$item['id'] = $c;
					$item['name'] = $name;
					$item['bgcolor'] = $colors[$level];
					$item['padd'] = $padd;
					if (Utils::IsArray($item['submenu']))
					{
						$level++;
						$item['sub'] = $this->MakeDivs($item['submenu']);
						$level--;
						$html .= $Parser->MakeView($item, 'cms/menu', 'container');
						
						$html .= "\r\n";
					}
					else
					{
						$html .= $Parser->MakeView($item, 'cms/menu', 'line');
						
						$html .= "\r\n";
					}
					
					
				}
				if ($level==1)
				{
					$html .= $separator;
				}
				$html .= '</table>';
				return $html;
			}

			function BuildCPMenu($id, $values, &$arrayToInsert)
			{
				foreach ($values as $row)
				{
					$name = Format::Label($row['name']);
					if ($row['parent_id']==$id)
					{
						$arrayToInsert[$row['name']] = array(
							'link'	=> $row['url'],
							'hint'	=> (!strlen($row['hint'])) ? $name : $row['hint'],
						);
						
						$this->BuildCPMenu($row['id'], $values, $arrayToInsert[$row['name']]['submenu']);
					}
				}
			}
		}
        
		$PageMenu = new CpMenuFramePage();
		$PageMenu->Render();
		break;
}

?>