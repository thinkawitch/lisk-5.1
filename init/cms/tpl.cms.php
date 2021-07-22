<?php
/**
 * CMS Tpl Files
 * @package lisk
 *
 */

class CMSContentTreeFiles
{
	public $fileID = 1;
	public $back;
	public $developerMode = false;
	
	/**
	 * @var array
	 */
	private $treeJSStructure = array();
	
	function __construct()
	{
		GLOBAL $App,$Auth;
		$App->Load('cpmodules', 'lang');
		$this->developerMode = ($Auth->user['level'] == LISK_GROUP_DEVELOPERS) ? true : false;
		
		if (!$this->developerMode) Navigation::Jump('developers_only.php');
	}


	function MakeJSLinkButtons()
	{
		GLOBAL $Page;

		$Page->AddLink("Expand all", "javascript: expandAll();", 'img/cms/tree/link_collapse.gif');
		$Page->AddLink("Collapse all", "javascript: collapseAll();", 'img/cms/tree/link_expand.gif');
	}


	function RenderJS()
	{
		GLOBAL $Parser,$Page;

		return $Parser->makeView(array(
			'js_tree'			=> $this->GetJSStructure(),
			'view_check'		=> (@$_GET['checkboxes']=='true')? ' true' : 'false',
			'cur_nav_level'		=> $Page->back,
			'tree_name'			=> 'tree_name',
		), 'cms/tpl/tree_js', 'tree');
	}

	private function GetJSStructure()
	{
		GLOBAL $App;
		$this->ReadDirs($App->sysRoot.$App->tplPath, 1, 2);
		
		$rootName = 'Tpl';
		
		$str = "t.add(1, 0, \"&nbsp;$rootName\", \"javascript:DisplayPageInfo(1);\", \"\", true);\r\n";
		
		foreach ($this->treeJSStructure as $key=>$row)
		{
			$mode = 0;
			if ($row['type'] == 'node')
			{
				foreach ($this->treeJSStructure as $row2)
				{
					if ($row2['parent_id'] == $row['id'])
					{
						if ($row2['type'] == 'node') $mode = 1;
						if ($row2['type'] == 'point') $mode = 2;
					}
				}
				$this->treeJSStructure[$key]['mode'] = $mode;
			}
		}

		foreach ($this->treeJSStructure as $rec)
		{
			$rec['name'] = $this->EscapeJsStr($rec['name']);
			$images='';
			switch($rec['page_type'])
			{
				case SCMS_CONTENT:
					$images = 'img/cms/scms/type/2.gif';
					$str .= "t.add({$rec['id']}, {$rec['parent_id']}, \"&nbsp;{$rec['name']}\", \"javascript:DisplayPageInfo('{$rec['path']}');\", \"$images\", null);\r\n";
					break;
					
				case SCMS_PAGESET:
					$images = 'img/cms/scms/type/0.gif';
					$str .= "t.add({$rec['id']}, {$rec['parent_id']}, \"&nbsp;{$rec['name']}\", \"javascript:DisplayPageInfo('1');\", \"$images\", null);\r\n";
					break;
			}
		}
		return $str;
	}
	
	function ReadDirs($dir, $parent, $lvl)
	{
	    if (false == ($handle = opendir($dir))) return;
	    
	    $exclude = array(
	        '.',
	        '..',
	        'thumb.db',
	        '.htaccess',
	        '.svn',
	    );
	    while (false !== ($file = readdir($handle)))
	    {
	        if (in_array(strtolower($file), $exclude)) continue;
	        
	        $this->fileID++;
    		
            if(is_dir($dir.$file))
            {
	            $this->treeJSStructure[] = array(
					'id'		=> $this->fileID,
					'parent_id'	=> $parent,
					'name'		=> $file,
					'path'		=> '',
					'type'		=> 'node',
					'nesting'	=> $lvl,
					'page_type' => SCMS_PAGESET,
				);
				$this->ReadDirs($dir.$file.'/', $this->fileID, $lvl + 1);
            }
            else
            {
	            $this->treeJSStructure[] = array(
					'id'		=> $this->fileID,
					'parent_id'	=> $parent,
					'name'		=> $file,
					'path'		=> $dir.$file,
					'type'		=> 'node',
					'nesting'	=> $lvl,
					'page_type' => SCMS_CONTENT,
				);
            }
	    }
	    closedir($handle);
		
	}


	private function EscapeJsStr($str)
	{
		//for now
		return strtr($str, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
	}
	
	function RenderFile($name)
	{
		GLOBAL $Parser;
		$info['__file_name'] = $name;
		$info['__file_content'] = file_get_contents($name);
		$info['__file_content'] = str_ireplace('&', '&amp;', $info['__file_content']); //fix for TplEditor
		
		$block = $Parser->GetHtml('cms/tpl/file', 'info');
		
		foreach ($info as $var=>$value)
		{
		    $block = str_replace('{'.strtoupper($var).'}', $value, $block);
		}
		return $block;
	}
	
	function SaveFileContent($filename, $content)
	{
		if (is_file($filename) && is_writable($filename))
		{
		    file_put_contents($filename, $content);
		}
	}

}
?>