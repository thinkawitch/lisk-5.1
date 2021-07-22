<?php
/**
 * CLASS Cache
 * @package lisk
 *
 */
Class Cache
{

	/**
	 * Switch On/Off caching on URL's with sys_cache or manual settings
	 * To set manual settings, please use SetAutoCacheSetting method.
	 */
	public	$AutoCache = false;
	
	/**
	 * Switch On/Off manual cache
	 */
	public	$ManualCache = false;
	
	public	$cache_filepath; //Path to cache file
	public	$cache_type; //level1...level10
	public	$cache_time = 0; //Time in minutes
	
	/**
	 * Not for use
	 *
	 * @var boolean
	 */
	public	$isCachedSnippets = false;
	
	private	$cache_url; //Caching url
	private $cache_level = 1;
	private $path; //path to cache folder
	
	/**
	 * @var Page
	 */
	private $refPage;
	
	/**
	 * @var Template
	 */
	private $Tpl;
	
	/**
	 * @var Database
	 */
	private $Db;
	
	/**
	 * @var Application
	 */
	private $App;
	
	/**
	 * @var Authorization
	 */
	private $Auth;

	function __construct()
	{
		GLOBAL $Db,$App,$Auth;
		$this->App = $App;
		$this->Db = $Db;
		$this->Auth = $Auth;
		$this->path = $App->filePath.$App->cachePath;
		$this->cache_url = isset($GLOBALS['url']) ? $GLOBALS['url'] : '';
		$this->cache_level = $this->Auth->isAuthorized ? $this->Auth->user['level'] : 1;
		$this->cache_type = 'level'.$this->cache_level;
		
		if ($this->cache_url == '')
		{
			$this->cache_url = 'main/';
		}
	}

	/**
	 * Auto cache on URLs
	 * @return Content
	 */
	private function GetAutoCache()
	{
		$this->GetDefaultSettings();

		if ($this->cache_time > 0)
		{
			return $this->GetCachedFile();
		}
		
		return '';
	}
	
	/**
	 * Get settings for autocache
	 */
	private function GetDefaultSettings()
	{
		static $rows = null;
		if ($rows == null) $rows = $this->Db->Select("cache_level = {$this->cache_level} OR cache_level = '*'", null, null, 'sys_cache');
		
		if (!Utils::IsArray($rows))
		{
			$this->cache_time = 0;
			return;
		}
		foreach ($rows as $row)
		{
			if (strcmp($this->cache_url, $row['url']) == 0)
			{
				$this->cache_time = $row['cache_time'];
				return;
			}
		}
		foreach ($rows as $row)
		{
			$_regexp = str_replace( '*', '[\d\w_]+', $row['url'] );
			if (preg_match('{^'.$_regexp.'$}', $this->cache_url))
			{
				$this->cache_time = $row['cache_time'];
				return;
			}
		}
	}

	private function GetCachedFile()
	{
		//path to cache without snippets
		$filepath0 = $this->App->sysRoot.$this->path.'no_snip.'.$this->cache_type.'.'.str_replace('/', '.', $this->cache_url).'html';
		//path to cache with snippets
		$filepath1 = $this->App->sysRoot.$this->path.'have_snip.'.$this->cache_type.'.'.str_replace('/', '.', $this->cache_url).'html';
		
		if (file_exists($filepath0))
		{
			$filepath = $filepath0;
			$this->isCachedSnippets = false;
		}
		elseif (file_exists($filepath1))
		{
			$filepath = $filepath1;
			$this->isCachedSnippets = true;
		}
		else
		{
			$filepath = $filepath0;
		}
	
		if (!file_exists($filepath))
		{
			$this->cache_filepath = $filepath;
		}
		else
		{
			$date = filemtime($filepath);
			if($date > (time()-($this->cache_time * 60)))
			{
				return $this->PrepareTemplate(file_get_contents($filepath));
			}
			else
			{
				$this->cache_filepath = $filepath;
			}
		}
		
		return '';
	}
	
	private function PrepareTemplate($page)
	{
		GLOBAL $Debug, $App;
		
		//debug
		if ($this->App->debug)
		{
			$page = str_replace('<!-- LISK_DEBUG -->', $Debug->Render(), $page);
		}

		$error = $this->refPage->ParseError();
		$error .= $this->refPage->ParseNotify();
		
		if ($error != '')
		{
			$page = str_replace('<!-- LISK_ERROR -->', $error, $page);
		}
		
		if ($this->isCachedSnippets)
		{
			$App->cachedFlag = true;
			$LiskSnippet = new LiskSnippet($page);
			$LiskSnippet->ExecuteSnippets();
			$page = $LiskSnippet->page;
		}
		
		return $page;
	}
	
	public function SaveToCache($page)
	{
		$snippet = (isset($GLOBALS['isCachedSnippets']) && $GLOBALS['isCachedSnippets'] == true) ? 'have_snip' : 'no_snip';
		$path = $this->App->sysRoot.$this->path."{$snippet}.".$this->cache_type.'.'.str_replace('/', '.', $this->cache_url).'html';
		file_put_contents($path, $page);
	}
	
	public function Render()
	{
		if (!$this->App->cache) return '';
		
		if ($this->ManualCache)
		{
			return $this->GetCachedFile();
		}
		elseif ($this->AutoCache)
		{
			return $this->GetAutoCache();
		}
		
		return '';
	}
	
	public function Process($page)
	{
		$this->refPage = $page;
		if (!$this->App->cache) return false;
		if (!$this->ManualCache && !$this->AutoCache) return false;
		if ($this->ManualCache && $this->cache_time <= 0) return false;
		
		if ($this->ManualCache)
		{
			$content = $this->GetCachedFile();
		}
		elseif ($this->AutoCache)
		{
			$content = $this->GetAutoCache();
		}
		
		if ($content == '' || strlen($this->cache_filepath) > 0)
		{
			return false;
		}
		else
		{
			echo $content;
			$this->App->Destroy();
		}
	}
}

$GLOBALS['Cache'] = new Cache();
?>