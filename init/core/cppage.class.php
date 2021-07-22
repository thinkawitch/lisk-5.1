<?php
/**
 * CLASS CPPage
 * @package lisk
 *
 */
class CPPage extends Page
{
	public $back;
	public $setBack;

	public $title;
	public $titlePicture = 'cms/l_settings.jpg';

	public $links;					// *links line array

	public $bookmarks;				// *bookmarks array
    public $currentBookmark = null;	// *current bookmark name

	public $backLink = null;

	public $pageContent;
	public $customLine;			// custom line content

	public $listFilter = array();

	function __construct()
	{
		parent::__construct();
		
		GLOBAL $App, $Tpl, $Parser;
		
		$Tpl->processLiskInclude = false;
		$Parser->tpl->processLiskInclude = false;

		// set backs
		$this->back = (isset($_GET['back'])) ? $_GET['back'] : null;
		$this->setBack = (isset($_GET['back'])) ? $this->back + 1 : 0;

		$App->Load('list', 'cms');
		$App->Load('add', 'cms');
		$App->Load('edit', 'cms');
		$App->Load('del', 'cms');
		$App->Load('view', 'cms');
		$App->Load('order', 'cms');
		$App->Load('tree', 'cms');
		$App->Load('edit_email', 'cms');
		$App->Load('group', 'cms');
		$App->Load('cross_list', 'cms');

		$App->Load('main', 'lang');

		$App->Load('scms', 'cms');
	}

	function RenderHelp()
	{
		$this->currentBookmark = 'Help';
		//TODO
	}

	function SetTitle($title, $picture=null)
	{
		$this->title = $title;
		if ($picture!=null) $this->titlePicture = $picture;
	}

	function AddLink($name, $url, $picture=null, $hint=null, $jsCode=null)
	{
		$this->links[] = array(
			'name'		=> $name,
			'url'		=> $url,
			'picture'	=> $picture,
			'hint'		=> $hint,
			'js_code'	=> $jsCode
		);
	}

	function AddBookmark($name, $url, $picture=null)
	{
		$this->bookmarks[$name] = array(
			'url'		=> $url.'&back='.$this->back,
			'picture'	=> $picture
		);
	}

	function MakeLinks()
	{
		GLOBAL $Parser;
		
		if (Utils::IsArray($this->links))
		{
			foreach ($this->links as $key=>$link)
			{
				$this->links[$key]['picture'] = $Parser->RenderImage($link['picture'], 'align="absmiddle" hspace="3"');
				if ($link['hint']!=null)
				{
					$this->links[$key]['hint'] = "liskHint=\"{$link['hint']}\"";
				}
				if ($link['js_code'] != null)
				{
					$this->links[$key]['hint'] = $link['js_code'];
				}
			}
		}

		if (Utils::IsArray($this->listFilter))
		{
			$Parser->SetCaptionVariables( array('list_filter' => $this->MakeListFilter()) );
		}

		return $Parser->MakeList($this->links, 'cms/blocks', 'links');
	}

	function MakeListFilter()
	{
		$list = $this->listFilter;
		$html = '';
		$addVars = array('cond'=>'');
		$href = Navigation::AddGetVariable($addVars);

		if (Utils::IsArray($list))
		{
			$html .= ' <select name="_filter" style="font-size:10;" onchange="location.href=\''.$href.'\'+this.value">';

			foreach ($list as $cond=>$label)
			{
				$selected = '';
				if (isset($_GET['cond']) && $_GET['cond']==$cond)
				{
					$selected = 'selected';
				}
				$html .= "<option value=\"$cond\" $selected>$label</option>";
			}
			$html .= '</select>';

		}

		return $html;
	}

	function ParseBack($back=null)
	{
		if ($back == null) $back = ($this->back==null) ? 0 : $this->back;
		
		$this->backLink = $this->Parser->MakeView(array(
			'url'	=> Navigation::GetBack($back)
		), 'cms/blocks', 'back_link');
	}

	function MakeBookmarks()
	{
		GLOBAL $Parser;
		if ($this->currentBookmark==null)
		{
			// 1 check na url sovpadenie esli ego netu -> pervaia samaia?
			// query string
			//TODO
		}

		$bookmarksToParse=array();
		foreach ($this->bookmarks as $name=>$info)
		{
			$tplBlock=($name==$this->currentBookmark) ? 'bookmark_on' : 'bookmark_off';
			$bookmarkHtml = $this->Parser->MakeView(array(
				'name'		=> $name,
				'url'		=> $info['url'],
				'picture'	=> $Parser->RenderImage($info['picture'], 'hspace="3" align="absmiddle"')
			), 'cms/blocks', $tplBlock);
			$bookmarksToParse[] = array('bookmark' => $bookmarkHtml);
		}
		return $this->Parser->MakeList($bookmarksToParse, 'cms/blocks', 'bookmarks');
	}

	function Output()
	{
		GLOBAL $Tpl,$App,$Parser,$Debug;

		$page = $Tpl->Get();
		unset($Tpl->blocklist['__global__']);
		$Tpl->Free();

		$Tpl->LoadTemplatefile($this->globalTemplate, true, true);

		// process Links line
		if (Utils::IsArray($this->links) || Utils::IsArray($this->listFilter))
		{
			$Tpl->SetVariable('LINKS', $this->MakeLinks());
		}

		// process Bookmarks
		if (Utils::IsArray($this->bookmarks))
		{
			$Tpl->SetVariable('BOOKMARKS', $this->MakeBookmarks());
		}

		if (strlen($this->customLine))
		{
			$Tpl->SetVariable(array(
				'CUSTOM_LINE'	=> $Parser->MakeView(array('content'=>$this->customLine), 'cms/blocks', 'custom_line')
			));
		}

		// Back Link
		$Tpl->SetVariable('BACK_LINK', $this->backLink);

		$Tpl->SetVariable( array(
			'TITLE'			=> $this->title,
			'TITLE_PICTURE'	=> $Parser->RenderImage('img/'.$this->titlePicture, 'align="absmiddle"'),
			'PAGE' 			=> $this->pageContent,
		));

		//errors
		$errorBlock = $this->RenderError();
		
		//notifications
		$errorBlock .= $this->RenderNotification();
		
		//growls
		$errorBlock .= $this->RenderGrowl();
		
		// execute block functions
		$page = $this->ExecuteBlockFunctions($Tpl->get());

		//additional resources
		$page = $this->LinkAdditionalResources($page);
		
		//Insert Lisk Debug && Insert Lisk Error
		if ($App->debug) $page = str_replace('<!-- LISK_DEBUG -->', $Debug->Render(), $page);
		if ($errorBlock != '') $page = str_replace('<!-- LISK_ERROR -->', $errorBlock, $page);
		
		//CP translate
		$page = $this->LangParse($page);
		
		// replace  all short markers
		$page = $this->ReplaceShortMarkers($page);

		// show page
		$Tpl->show($page);
		// destroy application
		$App->Destroy();
	}

	function Message($area, $key)
	{
		GLOBAL $App;
		$val = $GLOBALS['LANGUAGE_'.strtoupper($area)][$key];
		if (!isset($GLOBALS['LANGUAGE_'.strtoupper($area)][$key]))
		{
			$App->RaiseError("Translation is not defined. langArr=$area key='$key'");
		}
		return $val;
	}

	private function LangParse($page)
	{
		// [[[MENU]]]
		$regs = array();
		preg_match_all('/\[\[\[(.+?)\]\]\]/ms', $page, $regs);

		$blocks=array();
		if (0 != count($regs[1]))
		{
	        foreach ($regs[1] as $k => $var)
	        {
				list($langArr, $key) = explode('.', $var);

				$val = $this->Message($langArr,$key);
/*				$GLOBALS['LANGUAGE_'.strtoupper($langArr)][$key];
				if (!isset($GLOBALS['LANGUAGE_'.strtoupper($langArr)][$key])) {
					GLOBAL $App;
					$App->RaiseError("Translation is not defined. langArr=$langArr key='$key'");
				}*/
				$blocks[$regs[0][$k]] = $val;
			}
		}

		foreach ($blocks as $k=>$var)
		{
			$page = str_replace($k, $var, $page);
		}
		return $page;
	}

	function SetBack()
	{
		Navigation::SetBack($this->setBack);
	}
}

?>