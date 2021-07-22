<?php
/**
 * CLASS WikiParser
 * @package lisk
 *
 */

class WikiParser
{
	private $original;
	private $rows;
	private $arrRes;
	
	function __construct($text)
	{
		$this->original = $text;
	}
	
	function Parse()
	{
		// to array
		$this->rows = explode("\n", $this->original);
				
		// return nothing if empty
		if (!is_array($this->rows) || sizeof($this->rows) < 1)
		{
			return '';
		}
		
		//strip whitespaces
		foreach ($this->rows as $key=>$row)
		{
			$this->rows[$key] = (trim($row));
		}
		
		//print_r($this->rows);
		
		// GET OBJECTS
		$this->FindHeaders();
		
		$this->FindList('#');
		$this->FindList('*');
		
		//$this->FindLists();
		$this->FindParagraphs();
		
		$this->FindBreaks();
		
		//sort by key
		ksort($this->arrRes);
		
		//print_r($this->arrRes);
		
		//PARSE
		$rez = $this->RenderOutput();
		
		$rez = $this->ParseLinks($rez);
		return $rez;
	}
	
	function ParseLinks($text)
	{
		$regExp = "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i";
		$m = array();
		preg_match_all($regExp, $text, $m);
 
		if (Utils::IsArray($m[0]))
		{
			foreach ($m[0] as $link)
			 {
			 	if (substr($link,0,7) != 'http://' && substr($link,0,8) != 'https://')
			 	{
			 		$url = 'http://'.$link;
			 	}
			 	else
			 	{
			 		$url = $link;
			 	}
			 	
			 	$showLink = (strlen($link) > 100) ? substr($link, 0, 100).'...' : $link;
			 	
			 	$text = str_replace($link, '<a href="'.$url.'" target="_blank">'.$showLink.'</a>', $text);
			 }
		}

		return $text;
	}
	
	function FindHeaders()
	{
		foreach ($this->rows as $key=>$row)
		{
			$objType = '';
			$content = '';
			
			if (substr($row, 0, 3) == '===' && substr($row, -3) == '===')
			{
				$objType = 'h3';
				$content = substr($row, 3, -3);
			}
			elseif (substr($row, 0, 2) == '==' && substr($row, -2) == '==')
			{
				$objType = 'h2';
				$content = substr($row, 2, -2);
			}
			elseif (substr($row, 0, 1) == '=' && substr($row, -1) == '=')
			{
				$objType = 'h1';
				$content = substr($row,1,-1);
			}
			
			if ($objType != '')
			{
				$this->arrRes[$key] = array(
					'type'		=> $objType,
					'content'	=> $content
				);
				unset($this->rows[$key]);
			}
		}
	}
	
	function FindList($tag)
	{
		switch ($tag)
		{
			case '#':
				$type = 'ol';
				break;
				
			case '*':
				$type = 'ul';
				break;
		}
		
		$start = -1;
		$end = -1;
		$cur = array();
		$prevKey = null;
		
		foreach ($this->rows as $key=>$row)
		{
			if ($key-1 != $prevKey && $prevKey != null)
			{
				$end = $prevKey;
				$content = array();
				foreach ($cur as $id)
				{
					$content[] = substr($this->rows[$id], 1);
					unset($this->rows[$id]);
				}
				
				$this->arrRes[$start] = array(
					'type'		=> $type,
					'content'	=> $content
				);
				$cur = array();
				$start = $end = -1;
			}
			
			if ($row == '')
			{
				$prevKey = $key;
				continue;
			}
			
			if (substr($row, 0, 1) == $tag)
			{
				if ($start == -1)
				{
					//new list
					$start = $key;
					
				}
				else
				{
					//continue previous list
				}
				$cur[] = $key;
			}
			else
			{
				if ($start > -1)
				{
					$end = $prevKey;
					$content = array();
					foreach ($cur as $id)
					{
						$content[] = substr($this->rows[$id], 1);
						unset($this->rows[$id]);
					}
					
					$this->arrRes[$start] = array(
						'type'		=> $type,
						'content'	=> $content
					);
					$cur = array();
					$start = $end = -1;
				}
			}
			
			$prevKey = $key;
		}
		
		//If not finished and not empty
		if (is_array($cur) && sizeof($cur)>0)
		{
			$content = array();
			foreach ($cur as $id)
			{
				$content[] = substr($this->rows[$id], 1);
				unset($this->rows[$id]);
			}
			
			$this->arrRes[$start] = array(
				'type'		=> $type,
				'content'	=> $content
			);
			$cur=array();
			$start = $end = -1;
		}
	}
	
	function FindParagraphs()
	{
		$start = -1;
		$end = -1;
		$cur = array();
		$prevKey = null;
		
		foreach ($this->rows as $key=>$row)
		{
			//Break if break;
			if ($key-1 != $prevKey && $prevKey != null)
			{
				$end = $prevKey;
				
				$content = array();
				foreach ($cur as $id)
				{
					$content[] = $this->rows[$id];
					unset($this->rows[$id]);
				}
				
				$this->arrRes[$start] = array(
					'type'		=> 'p',
					'content'	=> $content
				);
				$start = -1;
				$end = -1;
				$cur = array();
			}
			
			//Start new
			if ($row != '' && $start == -1)
			{
				//start paragraph
				$start = $key;
			}
			
			//END Paragraph
			if ($row == '' && $start >= 0)
			{
				$end = $prevKey;
				
				$content = array();
				foreach ($cur as $id)
				{
					$content[] = $this->rows[$id];
					unset($this->rows[$id]);
				}
				
				$this->arrRes[$start] = array(
					'type'		=> 'p',
					'content'	=> $content
				);
				$start = -1;
				$end = -1;
				$cur = array();
			}
			elseif ($start >= 0)
			{
				//ADD
				$cur[] = $key;
			}
			$prevKey = $key;
		}
		
		//If not finished and not empty
		if (is_array($cur) && sizeof($cur)>0)
		{
			$content = array();
			foreach ($cur as $id)
			{
				$content[] = $this->rows[$id];
				unset($this->rows[$id]);
			}
			
			$this->arrRes[$start] = array(
				'type'		=> 'p',
				'content'	=> $content
			);
			$cur = array();
		}
	}
	
	function FindBreaks()
	{
		foreach ($this->rows as $key=>$row)
		{
			if ($row == '')
			{
				$this->arrRes[$key] = array(
					'type'	=> 'br',
				);
				unset($this->rows);
			}
		}
	}
	
	function RenderOutput()
	{
		Application::LoadCss('wiki.css');
		
		$rez = '';
		
		foreach ($this->arrRes as $row)
		{
			$content = @$row['content'];
			
			switch ($row['type'])
			{
				case 'p':
					$rez .= "<p>".$this->ProcessParagraph($content)."</p>";
					break;
					
				case 'br':
					//$rez.="<br />\n";
					break;
					
				case 'h1':
					$rez .= "<h1 class=\"wiki_h1\">$content</h1>";
					break;

				case 'h2':
					$rez .= "<h2 class=\"wiki_h2\">$content</h2>";
					break;

				case 'h3':
					$rez .= "<h3 class=\"wiki_h3\">$content</h3>";
					break;

				case 'ol':
					$rez .= "<ol class=\"wiki_ol\">".$this->ProcessList($content)."</ol>";
					break;

				case 'ul':
					$rez .= "<ul class=\"wiki_ul\">".$this->ProcessList($content)."</ul>";
					break;
				
				default:
					echo 'ERROR WRONG TYPE';
					break;
			}
		}
		
		return $this->ProcessComments($rez);
	}
	
	function ProcessParagraph($arr)
	{
		return implode('<br />', $arr);
	}
	
	function ProcessList($arr)
	{
		$rez = '';
		foreach ($arr as $row)
		{
			$rez.="<li class=\"wiki_li\">$row</li>\n";
		}
		return $rez;
	}

	function ProcessComments($text)
	{
		//$regExp = "/\[\[((.|\n|\r|)*?)\]\]/";
		$regExp = "@(?<=\[\[)[^\[\]]*(?=\]\])@s";
        $m = array();
		preg_match_all($regExp, $text, $m);
		
		$start = '<div style="padding:0px;"><div style="background-color:#C2DFED; padding:5px;">';
		$end = '</div></div>';
		
		if (is_array($m[0]))
		foreach ($m[0] as $key=>$row)
		{
			//$text = str_replace($row,$start.$m[1][$key].$end,$text);
			$text = str_replace('[['.$row.']]', $start.$row.$end, $text);
		}
		
		return $text;
	}
	
}
?>