<?php
require_once('init/init.php');

class CpDevToolsPage extends CPPage
{
    private $selfUri = 'dev_tools.php?z=x';
    
	function __construct()
	{
		parent::__construct();
        
		$this->AddBookmark('&nbsp;&nbsp;Dashboard', $this->selfUri.'&action=dash');
        
		$this->AddBookmark('&nbsp;&nbsp;Data Items', $this->selfUri.'&action=data_items');
		
		$this->AddBookmark('&nbsp;&nbsp;SCMS Rename', $this->selfUri.'&action=rename');
		$this->AddBookmark('&nbsp;&nbsp;Permissions', $this->selfUri.'&action=permissions');
		$this->AddBookmark('&nbsp;&nbsp;Profiler', $this->selfUri.'&action=profiler');
		$this->AddBookmark('&nbsp;&nbsp;Mail', $this->selfUri.'&action=mail');
        
		$this->SetPostAction('submit_debug', 'ForceDebugSubmit');
		
		$this->SetGetAction('dash', 'Dashboard');
		
		$this->SetGetAction('data_items', 'ListDataItems');
		$this->SetGetAction('add_di', 'AddDiForm');
		$this->SetGetPostAction('add_di', 'submit', 'AddDiSubmit');
		$this->SetGetAction('edit_di', 'EditDiForm');
		$this->SetGetPostAction('edit_di', 'submit', 'EditDiSubmit');
		$this->SetGetAction('delete_di', 'DeleteDi');
		$this->SetGetAction('recreate_thumbs', 'RecreateThumbsForm');
		$this->SetGetPostAction('recreate_thumbs', 'submit', 'RecreateThumbsSubmit');

		//renames
		$this->SetGetAction('rename', 'Rename');
		$this->SetGetPostAction('rename', 'submit', 'RenameSubmit');
		
		//profiler
		$this->SetGetAction('profiler', 'Profiler');
		$this->SetGetAction('profiler_clear', 'ProfilerReset');
		$this->SetGetPostAction('profiler', 'submit', 'ProfilerSubmitCondition');
		
		$this->SetGetAction('permissions', 'Permissions');
		
		//mail
		$this->SetGetAction('mail', 'MailForm');
		$this->SetGetPostAction('mail', 'submit', 'MailSubmit');

		if ($this->Auth->user['level'] != LISK_GROUP_DEVELOPERS) Navigation::Jump('developers_only.php');
		
		$this->App->Load('dev_tools', 'cms');
		
		$this->AuthorizePma();
	}
	
    private function AuthorizePma()
	{
	    GLOBAL $App;
	    
	    $liskSName = session_name();
        $liskSId = session_id();
        session_write_close();
        
        //pma auth session
        $pmaSName = 'SignonSession';
        session_name($pmaSName);
        session_start();
    	/* Store there credentials */
        $_SESSION['PMA_single_signon_user'] = $App->sqlUser;
        $_SESSION['PMA_single_signon_password'] = $App->sqlPassword;
        $_SESSION['PMA_single_signon_host'] = $App->sqlHost;
        //$pmaSId = session_id();
        session_write_close();
        //
        
        //restart lisk session
        session_name($liskSName);
        if (!empty($liskSId)) session_id($liskSId);
        session_start();
        
	}

	function Page()
	{
		Navigation::Jump($this->selfUri.'&action=dash');
	}
    
	function Dashboard()
	{
        GLOBAL $App,$Parser;
        $this->SetTitle('Dashboard');
		$this->currentBookmark = '&nbsp;&nbsp;Dashboard';
		
		$view = array(
		    'force_debug' => $this->RenderForceDebug(),
		    'date_time' => $this->RenderDateTime(),
		);
		
		$this->pageContent .= $Parser->MakeView($view, 'cms/dev_tools/dashboard', 'view');
	}
	
    private function RenderForceDebug()
	{
		GLOBAL $App, $Parser;

		$App->Load('list', 'type');
		$GLOBALS['LIST_FORCE_DEBUG'] = array(
			1	=> 'On',
			0	=> 'Off'
		);

		$fd = new T_List(array(
			'object'	=> 'def_force_debug',
		    'name' => 'fd',
		));

		$fd->value = (isset($_SESSION['force_debug']) && $_SESSION['force_debug'] == 1) ? 1 : 0;

		return $Parser->MakeView(array(
			'fd'	=> $fd->RenderFormView()
		), 'cms/dev_tools/dashboard', 'debug');
	}
	
    private function RenderDateTime()
    {
        GLOBAL $Db, $Parser, $App;
        
        $dbDateTime = $Db->Query('SELECT NOW() AS curDate');
        $dbDateTime = $dbDateTime[0]['curDate'];
        
        if (isset($_SERVER['REQUEST_TIME']))
        {
            $diff = $_SERVER['REQUEST_TIME'] - time();
            $diff = round($diff / 60 / 60, 1);
            if ($diff > 0) $diff = '+ '.$diff;
            $diff .= ' hour(s)';
        }
        else
        {
            $diff = 'unknown';
        }
        
        $view = array(
            'php_time_zone' => date_default_timezone_get(),
            'php_time' => date('Y-m-d h:i:s'),
            'mysql_time' => $dbDateTime,
            'http_diff' => $diff,
        );
        
        return $Parser->MakeView($view, 'cms/dev_tools/dashboard', 'date_time');
    }

	function ForceDebugSubmit()
	{
		$_SESSION['force_debug'] = (isset($_POST['fd']) && $_POST['fd'] == 1) ? 1 : 0;
		Navigation::Jump($this->selfUri.'&action=dash');
	}
	
	function ListDataItems()
	{
	    GLOBAL $Db, $Parser;
	    
	    $this->SetTitle('Data Items');
		$this->currentBookmark = '&nbsp;&nbsp;Data Items';
		
		$this->AddLink('Add DI',  $this->selfUri.'&action=add_di', 'img/ico/links/add.gif');
		
		//render dynamic data items
		$rows = $Db->Select(null, 'name', null, 'sys_di');
		if (Utils::IsArray($rows))
		{
    		foreach ($rows as $key=>$row)
    		{
    			$arr =  unserialize($row['data']);
    			$rows[$key]['code']	= print_r($arr, true);
    		    if ($this->DiHasImageFields($row['name']))
    			{
    			    $rows[$key]['name_recreate'] = $row['name'];
    			}
    		}
    		$this->pageContent .= $Parser->MakeList($rows, 'cms/dev_tools/data_items', 'db_items');
		}

		//render static data items]
		$rows = $this->GetStaticDataItems();
	    if (Utils::IsArray($rows))
		{
    		foreach ($rows as $key=>$row)
    		{
    			$rows[$key]['code']	= print_r($row['config'], true);
    			if ($this->DiHasImageFields($row['name']))
    			{
    			    $rows[$key]['name_recreate'] = $row['name'];
    			}
    		}
    		$this->pageContent .= $Parser->MakeList($rows, 'cms/dev_tools/data_items', 'code_items');
		}
		
	}
	
	private function GetStaticDataItems()
	{
	    $list = array();
	    $names = array();
	    
	    foreach ($GLOBALS as $name => $config)
	    {
	        if (substr($name, 0, 5) == 'DATA_')
	        {
	            $list[] = array(
	                'name' => substr($name, 5),
	                'config' => $config,
	            );
	            $names[] = $name;
	        }
	    }
	    
	    //do sort
	    if (Utils::IsArray($list))
	    {
	        array_multisort($names, SORT_ASC, $list);
	    }
	    
	    return $list;
	}
	
	private function DiHasImageFields($name)
	{
	    $exclude = array('dyn_cross_', 'dyn_tree_');
	    foreach ($exclude as $ex)
	    {
	        if (false !== strpos($name, $ex)) return false;
	    }
	    
	    $di = Data::Create($name);
	    foreach ($di->fields as $field)
	    {
	        if ($field instanceof T_Image) return true;
	    }
	    
	    return false;
	}
	
    private function DiGetImageFields($name)
	{
	    $fields = array();
	    
	    $di = Data::Create($name);
	    foreach ($di->fields as $field)
	    {
	        if ($field instanceof T_Image) $fields[] = $field->name;
	    }
	    
	    return $fields;
	}
	
	function AddDiForm()
	{
		GLOBAL $Parser;
		
		$this->SetTitle('Data Items');
		$this->currentBookmark = '&nbsp;&nbsp;Data Items';

		$this->pageContent .= $Parser->GetHtml('cms/dev_tools/data_items', 'form');
	}

	function AddDiSubmit()
	{
		GLOBAL $App;
        
		$arr = null;
		$diName = trim($_POST['name']);
		$arrStr = $_POST['conf_array'];
		@eval("\$arr = $arrStr;");
		
        if (Utils::IsArray($arr))
        {
		    $App->InstallDI($diName, $arr);
		    $this->SetNotification('DataItem installed.');
        }
        else
        {
            $this->SetError('DataItem not installed.<br />Please check the config!');
        }

		Navigation::Jump($this->selfUri.'&action=data_items');
	}

    function EditDiForm()
	{
		GLOBAL $Parser, $Db;
        
		$this->SetTitle('Data Items');
		$this->currentBookmark = '&nbsp;&nbsp;Data Items';
		
		$name = $_GET['name'];
		$arrStr = $Db->Get("name='$name'", 'data', 'sys_di');
		$arrStr = unserialize($arrStr);
		$arrStr = print_r($arrStr, true);

		$this->pageContent .= $Parser->MakeView(array(
			'code'	=> $this->GetArrayFromPrint($arrStr),
			'name'	=> $name
		), 'cms/dev_tools/data_items', 'form');
	}

	function EditDISubmit()
	{
		GLOBAL $App,$Db;
            
		$arr = null;
		$diName = trim($_GET['name']);
		$arrStr = $_POST['conf_array'];

		@eval("\$arr = $arrStr;");
        
		if (Utils::IsArray($arr))
		{
		    $Db->Update("name='{$diName}'", array(
				'data'	=> serialize($arr)
		    ), 'sys_di');
		    
		    $this->SetNotification('DataItem updated.');
		}
		else
		{
		   $this->SetError('DataItem not updated.<br />Please check the config!');
		}

		Navigation::Jump($this->selfUri.'&action=data_items');
	}
	
    function DeleteDI()
	{
		GLOBAL $App;

		$App->UninstallDI($_GET['name']);
		Navigation::Jump($this->selfUri.'&action=data_items');
	}
    
	function RecreateThumbsForm()
	{
	    GLOBAL $Parser, $Db, $App;
        
		$this->SetTitle('Data Items');
		$this->currentBookmark = '&nbsp;&nbsp;Data Items';
		
	    $name = $_GET['name'];
	    $fields = $this->DiGetImageFields($name);
	    if (!Utils::IsArray($fields))
	    {
            $this->SetError('DataItem ['.$name.'] has no image fields');
            Navigation::Jump($this->selfUri.'&action=data_items');
	    }
	    
	    $di = Data::Create($name);
	    
	    //define how many records with images we have
	    $cond = '';
	    foreach ($fields as $field)
	    {
	        $cond .= "`$field` != '',";
	    }
	    $cond = substr($cond, 0, -1);
        
	    $rows = $Db->Query('SELECT COUNT(*) AS count FROM '.$di->table.' WHERE '.$cond);
	    $count = isset($rows[0]) ? $rows[0]['count'] : 0;
	    
	    $this->pageContent .= $Parser->MakeView(array(
			'name'	=> $name,
			'records_with_images' => $count
		), 'cms/dev_tools/data_items', 'recreate_form');
	}
	
	function RecreateThumbsSubmit()
	{
	    GLOBAL $Db, $App;
	    
	    $name = $_GET['name'];
	    $fields = $this->DiGetImageFields($name);
	    if (!Utils::IsArray($fields))
	    {
            $this->SetError('DataItem ['.$name.'] has no image fields');
            Navigation::Jump($this->selfUri.'&action=data_items');
	    }
	    
	    $di = Data::Create($name);
	    $sqlCond = '';
	    $sqlFields = 'id,';
	    foreach ($fields as $field)
	    {
	        $sqlFields .= "`$field`,";
	        $sqlCond .= "`$field` != '',";
	    }
	    $sqlFields = substr($sqlFields, 0, -1);
	    $sqlCond = substr($sqlCond, 0, -1);
	    
	    $sql = 'SELECT '.$sqlFields.' FROM '.$di->table.' WHERE '.$sqlCond;
	    $res = $Db->MysqlQuery($sql);
	    
	    $c = 0;
	    while (false !== ($row = mysql_fetch_array($res, MYSQL_ASSOC)))
		{
			$row = Utils::StripSlashes($row);
			
			foreach ($fields as $field)
			{
			    $obj = $di->fields[$field];
			    $value = $row[$field];
			    $obj->RecreateUserThumbnails($value);
			}
			
			$c++;
		}
		
		mysql_free_result($res);
		
		$this->SetNotification('Thumbnails for '.$c.' record(s) were re-created!');
		
		Navigation::Jump($this->selfUri.'&action=recreate_thumbs&name='.$name);
	}
	
	private function GetArrayFromPrint($str)
	{
		if (strtolower(substr($str, 0, 5)) != 'array') return 'Invalid string!';

		$str = preg_replace('/\n\s+/', "\n", $str);
		$str = preg_replace('/\n\[/', "\n'", $str);
		$str = str_replace('] => ', "' => '", $str);
		$str = preg_replace("/\n('|\))/", "',\n\\1", $str);
		$str = str_replace("=> 'Array", "=> array", $str);
		$str = str_replace(")',", "),", $str);
		$str = str_replace("(',", "(", $str);
		
		$str = $str.";";

		return $str;
	}

	function Rename()
	{
		GLOBAL $Parser;
        $this->SetTitle('SCMS Rename');
		$this->pageContent .= $Parser->GetHtml('cms/dev_tools/rename', 'form');

		$this->currentBookmark = '&nbsp;&nbsp;SCMS Rename';
		$this->SetBack();
	}

	function RenameSubmit()
	{
		GLOBAL $Db;

		$what = isset($_POST['what']) ? $_POST['what'] : null;
		$to = isset($_POST['to']) ? $_POST['to'] : null;

		// don't allow empty values
		if (!strlen($what) || !strlen($to)) Navigation::JumpBack();

		switch ($_POST['where'])
		{
			case 1:
				$records = $Db->Select(null, null, 'id,content', 'sys_ss');
				if (Utils::IsArray($records))
				{
				    foreach ($records as $record)
				    {
    					$content = str_replace($what, $to, $record['content']);
    					$Db->Update("id={$record['id']}", array(
    						'content'	=> $content
    					),'sys_ss');
    				}
				}
				break;
				
			case 2:
				$records = $Db->Select(null, null, 'id,name,title,url,content', 'sys_ss');
				if (Utils::IsArray($records))
				{
					foreach ($records as $record)
					{
						$content = str_replace($what, $to, $record['content']);
						$name = str_replace($what, $to, $record['name']);
						$title = str_replace($what, $to, $record['title']);
						$url = str_replace($what, $to, $record['url']);
	
						$Db->Update("id={$record['id']}",array(
							'name'		=> $name,
							'title'		=> $title,
							'url'		=> $url,
							'content'	=> $content,
						),'sys_ss');
					}
				}
				break;
		}

		Navigation::JumpBack();
	}

	
	
	function Permissions()
	{
		GLOBAL $App, $Parser;

		$this->SetTitle('Files Permissions');
		$this->currentBookmark = '&nbsp;&nbsp;Permissions';
		
		$dirs = array(
			'init/installed/',
			$App->filePath,
			'tpl/modules/'
		);
		
		list($status, $list) = CMSDevTools::CheckPermissions($dirs);
		
		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$this->pageContent .= $Parser->MakeList($list, 'cms/dev_tools/permissions', 'list_prms');
	}

	public function Profiler()
	{
		GLOBAL $App,$Parser,$Db, $Paging;
		
		$this->SetBack();
		
		$this->SetTitle('Profiler');
		$this->currentBookmark = '&nbsp;&nbsp;Profiler';
		
		$App->Load('profiler','core');
		$Profiler = new LiskProfiler();
		
		$initContent = file_get_contents($App->sysRoot.'init/init.php');
		$initContent = str_replace(' ', '', $initContent);
		$initContent = str_replace('	', '', $initContent);
		
		if (!strpos($initContent,"define('LISK_PROFILER',true);"))
		{
			if (!strpos($initContent, "define('LISK_PROFILER',false);"))
			{
				$status = 'Unknown';
			}
			else
			{
				$status = 'OFF';
			}
		}
		else
		{
			$status = 'ON';
		}
		
		$this->customLine .= $Parser->MakeView(array(
			'min_time'	=> $Profiler->minTimeToLog,
			'min_sql'	=> $Profiler->minSqlTimeToLogQueries,
			'status'	=> $status
		), 'cms/dev_tools/profiler', 'info');
		
		$GLOBALS['DATA_TEMP_PROFILER_COND'] = array(
			'fields'	=> array(
				'apply_time'=> LiskType::TYPE_FLAG,
				'date_from'	=> LiskType::TYPE_DATETIME,
				'date_to'	=> LiskType::TYPE_DATETIME,
				'total'		=> LiskType::TYPE_INPUT,
				'render'	=> LiskType::TYPE_INPUT,
				'sql'		=> LiskType::TYPE_INPUT,
			)
		);
		$Cond = new Data('temp_profiler_cond');
		$Cond->value = @$_SESSION['sys_cp_profiler_condition'];
		
		$this->customLine .= $Parser->MakeForm($Cond,'cms/dev_tools/profiler','condition');
		
		$cond = '1=1';
		if (strlen($Cond->value['total'])) $cond .= " AND total_time>'{$Cond->value['total']}'";
		if (strlen($Cond->value['render'])) $cond .= " AND render_time>'{$Cond->value['render']}'";
		if (strlen($Cond->value['sql'])) $cond .= " AND sql_time>'{$Cond->value['sql']}'";
		if ($Cond->value['apply_time'] == 1) $cond .= " AND date>'{$Cond->value['date_from']}' AND date<'{$Cond->value['date_to']}'";
		
		
		$List = new CMSList('sys_profiler');
		$List->SetCond($cond);
		$List->Init();
		$List->buttonCheckbox = $List->buttonDeleteAll = $List->buttonEdit = false;
		
		$Paging->SwitchOn('cp');
		$this->pageContent .= $List->Render();
		
		$records = $Db->Get(null, 'COUNT(id)', $Profiler->table);
		$this->AddLink("Clear profiler data ($records records)",  $this->selfUri.'&action=profiler_clear');
		
	}
	
	public function ProfilerSubmitCondition()
	{
		GLOBAL $App;
		
		$App->Load('datetime', 'type');
		
		$_SESSION['sys_cp_profiler_condition'] = array(
			'total'			=> $_POST['total'],
			'render'		=> $_POST['render'],
			'sql'			=> $_POST['sql'],
			'apply_time'	=> @$_POST['apply_time_checked'],
			'date_from'		=> T_datetime::GetValueFromHash('date_from', $_POST),
			'date_to'		=> T_datetime::GetValueFromHash('date_to', $_POST),
		);

		Navigation::Jump(Navigation::Referer());
	}
	
	public function ProfilerReset()
	{
		GLOBAL $Db;
		
		$Db->Delete(null, 'sys_profiler');
		
		Navigation::Jump(Navigation::Referer());
	}
	
	function MailForm()
	{
	    GLOBAL $Parser;
	    
	    $this->SetBack();
	    
	    $this->SetTitle('Mail');
		$this->currentBookmark = '&nbsp;&nbsp;Mail';
		
	    $this->pageContent .= $Parser->GetHtml('cms/dev_tools/mail', 'view');
	}
	
	function MailSubmit()
	{
	    $email = $_POST['email'];
	    
	    $accepted = mail($email, 'Lisk test email', 'Lisk test email from '.$_SERVER['HTTP_HOST']);
	    
	    if ($accepted) $msg = 'Mail accepted for delivery to '.$email;
	    else $msg = 'Mail not accepted for delivery to '.$email;
	    
	    $this->SetError($msg);
	    Navigation::JumpBack($this->back);
	}
}

$Page = new CpDevToolsPage();
$Page->Render();
?>