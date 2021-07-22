<?php
/**
 * CLASS Navigation
 * @package lisk
 *
 */
class Navigation
{

	public static function Referer()
	{
		return $_SERVER['HTTP_REFERER'];
	}

	public static function Back()
	{
		$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		if ($referrer != $_SERVER['REQUEST_URI']) $_SESSION['SYS_NAV_link_back'] = $referrer;
		return $_SESSION['SYS_NAV_link_back'];
	}

	// =========================== JUMP & BACK FUNCTIONS ==========================

	/**
	 * @desc Jump to specified url
	 * @param string $url target url
	 * @param integer $code status code for header
	 */
	public static function Jump($url, $code=302)
	{
		GLOBAL $App, $Debug;

		// [/] replace
		$url = str_replace('[/]', $App->httpRoot, $url);
		// [./] replace
		$url = str_replace('[./]', $_SERVER['REQUEST_URI'], $url);

		$Debug->Render();
		
		header('Location: '.$url, true, $code);
		
		echo '<a href="'.$url.'">CLICK HERE</a>  if you have not been redirected automatically.';
		
		$App->Destroy();
	}

	/**
	 * @desc Set Back Url with specified depth's level
	 * @param int $level - depth's level
	 * @param string $query - addtional GET parameters
	 */
	public static function SetBack($level=0, $query='')
	{
		if ($query != '')
		{
			if ($_SERVER['QUERY_STRING'] != '') $query = '?'.$_SERVER['QUERY_STRING'].'&'.$query;

			$_SESSION['SYS_NAV_'.INIT_NAME.'_back_'.$level] = $_SERVER['PHP_SELF'].$query;
		}
		else
		{
			if ($_SERVER['QUERY_STRING'] != '') $query = '?'.$_SERVER['QUERY_STRING'];

			$_SESSION['SYS_NAV_'.INIT_NAME.'_back_'.$level] = $_SERVER['REQUEST_URI'];
		}
	}

	/**
	 * @desc Get Back Url with specified depth's level
	 * @param int $level - depth's level
	 * @return string back url
	 */
	public static function GetBack($level=0)
	{
		if (!isset($_SESSION['SYS_NAV_'.INIT_NAME.'_back_'.$level])) return '';
		else return $_SESSION['SYS_NAV_'.INIT_NAME.'_back_'.$level];
	}

	/**
	 * @desc Jump to Back Url with specified depth's level
	 * @param int $level - depth's level
	 */
	public static function JumpBack($level=0)
	{
		if (!strlen($level)) $level=0;
		$back_url = Navigation::GetBack($level);
		
		if ($back_url != '') Navigation::Jump($back_url);
	}

	/**
	 * Adds var=val into current quesry sting
	 *
	 * @param array $variable
	 * @return string
	 */
	public static function AddGetVariable($variable)
	{

		$staticUrls = defined('STATIC_URLS') && constant('STATIC_URLS')==true;

		if (Utils::IsArray($variable))
		{
			// create url with get variables that are not in $variable array
			$return = Navigation::GetBaseName();
			if (!$staticUrls) $return .= '?';

			if (Utils::IsArray($_GET))
			{
				foreach ($_GET as $key=>$value)
				{
					if ((!in_array($key, array_keys($variable))) && strpos($_SERVER['QUERY_STRING'], $key.'=') !== false)
					{
						$return.=$key.'='.urlencode($value).'&';
					}
				}
			}

			if ($staticUrls)
			{
				foreach ($variable as $key=>$value)
				{
					$return .= $key.'_'.$value.'/';
				}
			}
			else
			{
				foreach ($variable as $key=>$value)
				{
					$return .= $key.'='.urlencode($value).'&';
				}
				$return = substr($return, 0, -1);
			}

			return $return;

		}
		else
		{
			return Navigation::GetCurUrl();
		}
	}

	/**
	* @return string current url with get parameters
	* @desc Return current url
	*/
	public static function GetCurUrl()
	{
		$rez = Navigation::GetBaseName();
		if ($_SERVER['QUERY_STRING'] != '')
		{
			$rez .= '?'.$_SERVER['QUERY_STRING'];
		}
		return $rez;
	}

	/**
	* @return string script filename
	* @desc Return current script filename only
	*/
	public static function GetBaseName($url='cur')
	{
		if (defined('STATIC_URLS') && constant('STATIC_URLS') == true)
		{
			return isset($GLOBALS['url']) ? $GLOBALS['url'] : null ;
		}
		else
		{
			if ($url == 'cur')
			{
				//for cp/module_[module name].php and because of cgi-mode
				if (isset($_SERVER['REDIRECT_URL']) && strlen($_SERVER['REDIRECT_URL']))
				{
					$url = $_SERVER['REQUEST_URI'];
					$url = str_replace($_SERVER['QUERY_STRING'], '', $url);
					$url = str_replace('?', '', $url);
				}
				else $url = $_SERVER['PHP_SELF'];
			}

			return basename($url);
		}
	}
}

?>