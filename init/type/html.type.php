<?php
/**
 * Lisk Type Html
 * @package lisk
 *
 */
function THtmlCallbackUniversalShortToUrl($matches)
{
	GLOBAL $App;
	$url = str_replace('[/]', $App->httpRoot, $matches[2]);
	return str_replace($matches[2], $url, $matches[0]);
}


class T_html extends LiskType
{
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		$this->type = LiskType::TYPE_HTML;
		$this->tplFile = 'type/text';
	}
	
	function Insert(&$values)
	{
	    $fieldValue = @$values[$this->name];
		$values[$this->name] = $this->ConvertUrlsToShort($fieldValue);
		return $values[$this->name];
	}
	
	function Update(&$values)
	{
	    $fieldValue = @$values[$this->name];
		$values[$this->name] = $this->ConvertUrlsToShort($fieldValue);
		return $values[$this->name];
	}

	function Delete(&$values)
	{
		return true;
	}
	
	
	function RenderFormTplView()
	{
		GLOBAL $App;
		$App->LoadModule('editors/ckeditor/ckeditor.php', 2);
		
		$editor = new CKEditor($App->httpRoot.$App->initPath.'editors/ckeditor/');
		$editor->returnOutput = true;
		
		$value = $this->ConvertShortToUrls($this->value);
		$value = str_replace('$', '\$', $value);
		
		return $editor->editor($this->name, $value);
	}
	
	function RenderFormHtmlView()
	{
		$value = $this->ConvertShortToUrls($this->value);
		return "<textarea rows=\"3\" name=\"{$this->name}\" ".$this->RenderFormParams().">{$value}</textarea>";
	}
	
	function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				return $this->RenderFormTplView();
				break;
				
			default:
				return $this->RenderFormHtmlView();
				break;
		}
	}
	
	function RenderView($param1=null, $param2=null)
	{
		return $this->ConvertShortToUrls($this->value);
	}
	
	private function ConvertShortToUrls($str)
	{
		//anchors
		$str = preg_replace_callback('/(?<=<a)([^>]+(?<=href=")(\[\/\][^"]+)(?=")[^>]+)(?=>)/i', 'THtmlCallbackUniversalShortToUrl', $str);
		//images
		$str = preg_replace_callback('/(?<=<img)([^>]+(?<=src=")(\[\/\][^"]+)(?=")[^>]+)(?=>)/i', 'THtmlCallbackUniversalShortToUrl', $str);
		// background images
		$str = preg_replace_callback('/(?<=<)([^>]+(?<=background=")(\[\/\][^"]+)(?=")[^>]+)(?=>)/i', 'THtmlCallbackUniversalShortToUrl', $str);
		// input type=image
		$str = preg_replace_callback('/(?<=<input)([^>]+(?<=src=")(\[\/\][^"]+)(?=")[^>]+)(?=>)/i', 'THtmlCallbackUniversalShortToUrl', $str);
		
		return  $str;
	}
	
	private function ConvertUrlsToShort($str)
	{
		$matches = array();
		// anchors
		preg_match_all('/(?<=<a)([^>]+(?<=href=")([^"]+)(?=")[^>]+)(?=>)/i', $str, $matches);
		$str = $this->ReplaceUrlsToShort($matches[2],$str);
		
		// images
		preg_match_all('/(?<=<img)([^>]+(?<=src=")([^"]+)(?=")[^>]+)(?=>)/i', $str, $matches);
		$str = $this->ReplaceUrlsToShort($matches[2],$str);
		
		// background images
		preg_match_all('/(?<=<)([^>]+(?<=background=")([^"]+)(?=")[^>]+)(?=>)/i', $str, $matches);
		$str = $this->ReplaceUrlsToShort($matches[2],$str);

		// input type=image
		preg_match_all('/(?<=<input)([^>]+(?<=src=")([^"]+)(?=")[^>]+)(?=>)/i', $str, $matches);
		$str = $this->ReplaceUrlsToShort($matches[2],$str);
		
		return $str;
	}
	
	private function ReplaceUrlsToShort($urls, $str)
	{
		GLOBAL $App;
		
		$root = 'http://'.$_SERVER['HTTP_HOST'].'/';
		$rootLength = strlen($root);
		
		$base = $App->httpRoot;
		$baseLength = strlen($App->httpRoot);
		
		if (Utils::IsArray($urls))
		{
			foreach ($urls as $one)
			{
				$url = $one;
				$replaceUrl = false;
				//full url
				if (substr($url,0,$rootLength)==$root)
				{
					$url = substr($url, $rootLength-1);
					$replaceUrl = true;
				}
				//replace base part
				if (substr($url,0,$baseLength)==$base)
				{
					$url = '[/]'.substr($url, $baseLength);
					$replaceUrl = true;
				}
				
				//replace
				if ($replaceUrl)
				{
					$str = str_replace($one, $url, $str);
				}
			}
		}
		
		return $str;
	}
	
}

?>