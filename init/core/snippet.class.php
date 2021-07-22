<?php
/**
 * CLASS LiskSnippet
 * @package lisk
 *
 */
class LiskSnippet
{

	private $regExp = '/<lisk:snippet\s+([^>]*)>/e';
	public $page;

	//<lisk:snippet name="snippet_name" var1="value1" var2="value2" />

	function __construct($page)
	{
		$this->page = $page;
	}

	function ExecuteSnippets()
	{
		$this->page = preg_replace($this->regExp, '\$this->ExecuteSnippet("\1");', $this->page);
	}

	private function ExecuteSnippet($values)
	{
		GLOBAL $App, $Db;
		$matches = array();
		preg_match_all('/([\w\d]+)="([^"]*)"/', $values, $matches, PREG_SET_ORDER);

		$result = array();

		foreach ($matches as $match)
		{
			$key = $match[1];
			$value = $match[2];
			$result[$key] = $value;
			$result[strtolower($key)] = $value;
		}

		if ($App->cache && !$App->cachedFlag)
		{
			if (isset($result['cache']) && $result['cache'] == 'false')
			{
				$GLOBALS['isCachedSnippets'] = true;
				return "<lisk:snippet {$values}>";
			}
		}
		
		$name = $result['name'];

		if (!$name) $App->RaiseError('Lisk Snippet name attribute not set!');

		if (isset($result['src']))
		{
			if (substr($result['src'], 0, 6) == 'module')
			{
				$instanceId = $result['instanceid'];

				$sys = $Db->Get("id='{$instanceId}'", '', 'sys_modules');

				if (!Utils::IsArray($sys)) $App->RaiseError('Lisk Snippet "'.$name.'" instanceId attribute incorrect!');

				$App->load($sys['name'], 'mod');
				$obj = new $sys['object_name']($instanceId);

				$code = $obj->Snippet($result);

			}
			elseif (strlen($result['src']))
			{
				ob_start();
				// if file not found load snippet file
				if (!file_exists($result['src'])) $result['src'] = 'init/snippet/'.$result['src'].'.snippet.php';

				include_once($result['src']);
				$code = $name($result);
				if (!strlen($code))
				{
					$code = ob_get_contents();
				}
				ob_clean();
			}

		}
		else
		{
			eval ("\$code = $name(\$result);");
		}

		return $code;
	}
}
?>