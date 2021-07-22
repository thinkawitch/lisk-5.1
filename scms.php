<?php
require_once('init/init.php');

class ScmsHandlerPage extends Page
{
	public $curPage;
	public $cl;

	function __construct()
	{
		parent::__construct();
		
		$this->SetParameters();
		
		$this->App->Load('scms', 'class');
	}


	private function RenderContent($customContent=null)
	{
		GLOBAL $Parser;

		$sectionTpl = $this->curPage['section_tpl'];
		$subsectionTpl = $this->curPage['subsection_tpl'];
		$pageTpl = $this->curPage['page_tpl'];

		if (!strlen($sectionTpl)) $sectionTpl = 'empty';
		if (!strlen($subsectionTpl)) $subsectionTpl = 'empty';
		if (!strlen($pageTpl)) $pageTpl = 'empty';

		if ($sectionTpl != 'empty' && $subsectionTpl != 'empty' && $pageTpl != 'empty')
		{
			$inner = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'    => $customContent,
						), $pageTpl, 'scms');

			$inner = $Parser->MakeView(array('content' => $inner), $subsectionTpl, 'scms');
			$content = $Parser->MakeView(array('content' => $inner), $sectionTpl, 'scms');
		}
		elseif ($sectionTpl != 'empty' && $subsectionTpl != 'empty')
		{
			$inner = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'    => $customContent,
						), $subsectionTpl, 'scms');

			$content = $Parser->MakeView(array('content' => $inner), $sectionTpl, 'scms');
		}
		elseif ($sectionTpl != 'empty' && $pageTpl != 'empty')
		{
			$inner = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'    => $customContent,
						), $pageTpl, 'scms');

			$content = $Parser->MakeView(array('content'	=> $inner), $sectionTpl, 'scms');
		}
		elseif ($subsectionTpl != 'empty' && $pageTpl != 'empty')
		{
			$inner = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'    => $customContent,
						), $pageTpl, 'scms');

			$content = $Parser->MakeView(array('content'	=> $inner), $subsectionTpl, 'scms');
		}
		elseif ($sectionTpl != 'empty')
		{
			$content = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'	=> $customContent,
						), $sectionTpl, 'scms');
		}
		elseif ($subsectionTpl != 'empty')
		{

			$content = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'    => $customContent,
						), $subsectionTpl, 'scms');

		}
		elseif ($pageTpl != 'empty')
		{

			$content = $Parser->MakeView(array(
						'content'	=> $this->curPage['content'],
						'custom'    => $customContent,
						), $pageTpl, 'scms');

		}
		else
		{
			$content = $this->curPage['content'].$customContent;
		}

		return $content;
	}
    
	function ProcessAutoCache()
	{
        $this->Cache->AutoCache = true;
		$this->Cache->Process($this);
	}
	
	function Page()
	{
		GLOBAL $Scms,$Db,$App;

		$Scms->values	= $this->curPage;
		$Scms->cl		= $this->cl;

		$Scms->CheckAccessLevel();
		$this->SetGlobalTemplate($this->curPage['global_tpl']);

		switch ($this->curPage['page_type'])
		{
			case SCMS_CONTENT:
			    $this->ProcessAutoCache();
				$content = $this->RenderContent();
				break;

			case SCMS_LINK:
				$link = $this->curPage['link_href'];
				$link = str_replace('[/]', $App->httpRoot, $link);
				$code = $this->curPage['link_redirect']==1 ? 301 : 302;
				Navigation::Jump($link, $code);
				break;

			case SCMS_PAGESET:
				// if overview
				if ($this->curPage['pageset_overview'] == 1)
				{
					$this->ProcessAutoCache();
				    $content = $this->RenderContent();
				}
				else
				{
					// get 1st child url and jump to it
					$jumpUrl = $Db->Select("parent_id={$this->cl}",'oder LIMIT 1', 'url', 'sys_ss');
					Navigation::Jump($App->httpRoot.$jumpUrl[0]);
				}
				break;

			case SCMS_MODULE:

				$iid = $this->curPage['instance_id'];
				$module = $App->GetModuleInstance($iid);

				$custom = $module->Render();
				$content = $this->RenderContent($custom);

				break;

			case SCMS_CUSTOM:
				// get custom handler html result
				$method = $this->curPage['site_handler'];
				if (!is_callable(array($this, $method))) $App->RaiseError('Method '.$method.' not found');
				
				$custom = $this->$method();

				// content
				$content = $this->RenderContent($custom);

				break;
		}

		// set Page Title
		$this->title = $this->curPage['title'];

		$this->LoadTemplate('scms');
		$this->SetVariable(array(
			'content'	=> $content,
		));


	}

	private function SetParameters()
	{
		GLOBAL $Db,$Debug,$App;

		$url = $GLOBALS['contentUrl'];
		$params = explode('/', $url);
		if (substr($url, -1, 1)=='/') unset($params[sizeof($params)-1]);

		//add "/" to all parametrs if needed except the last one if it hasn't
		// support name.htm and /section/name.htm pages...
		foreach ($params as $key=>$param)
		{
			if ($key==(sizeof($params)-1) && substr($url,-1,1)!='/') continue;
			else $params[$key] = $param.'/';
		}

		// create sql cond
		$whereCond	= '';
		$base		= '';
		foreach ($params as $param)
		{
			$base.=$param;
			$whereCond .= "url='$base' OR ";
		}
		$whereCond = substr($whereCond, 0, -4);

		// get cur page
		$curPage = $Db->Query("
		SELECT *
		FROM sys_ss
		WHERE $whereCond
		ORDER BY CHAR_LENGTH(url) DESC
		LIMIT 1");

		// if page not found
		if (!Utils::IsArray($curPage)) $this->Error404();

		// Page found, get params
		$curPage = $curPage[0];
		$url = substr($url,strlen($curPage['url']));
		$params = explode('/', $url);
		unset($params[sizeof($params)-1]);
		foreach ($params as $param)
		{
			$this->parameters[] = $param;
		}

		$this->curPage	= $curPage;
		$this->cl		= $curPage['id'];

		// add Debug
		if ($App->debug) $Debug->AddDebug('SCMS', 'PageInfo', $curPage, null);
	}
	
	function Error404()
	{
	    GLOBAL $App;
	    header('HTTP/1.0 404 Not Found', true, 404);
	    echo file_get_contents($App->tplPath.'404.htm');
	    exit();
	}
}

$Page = new ScmsHandlerPage();
$Page->Render();
?>