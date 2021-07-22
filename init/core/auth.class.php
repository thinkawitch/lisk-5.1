<?php
/**
 * CLASS Authorization
 * @package lisk
 *
 */
class Authorization
{
    
	/**
	 * user data record values
	 *
	 * @var array
	 */
	public $user;
    
	/**
	 * current page required page level
	 *
	 * @var integer
	 */
    public $curPageLevel;
    
    /**
     * true if authorized; false if not
     *
     * @var boolean
     */
    public $isAuthorized = false;
    
    /**
     * Array of page auth levels
     *
     * @vararray
     */
    public $pages;
    
    /**
     * Login page url
     *
     * @var string
     */
    public $loginPageUrl;
    
    /**
     * cron page, skip auth
     *
     * @var string
     */
    protected $cronJobUrl = 'init/cron/index.php';
    
    /**
     * Default page to which user should be redirected on login
     *
     * @var unknown_type
     */
    public $loggedPageUrl;
    
    /**
     * Default authorization level
     *
     * @var integer
     */
    private $defaultLevel;
    
    /**
     * if true user will be redirected to the page that sent user to login
     *
     * @var boolean
     */
    private $autoJump;
    
    /**
     * Authorisation users table
     *
     * @var string
     */
    private $table;
    
    /**
     * Session SID variable name
     *
     * @var string
     */
    private $sidName;
    
    /**
     * If to crypt password
     *
     * @var boolean
     */
    private $md5Crypt;

    /**
     * Flag to be used when autologin tries to login and password is md5 secured
     * @var boolean
     */
    private $flagSecuredPassword = false;

    
    /**
    * @desc constructor
    */
    function __construct()
    {
        GLOBAL $App;
        $this->Debug('Auth started', "Auth based on '".INIT_NAME."' init name");
        
        //read Auth configuration
        $this->ReadConfig(INIT_NAME);
        
        //skip authorization if cron job
        $url = $_SERVER['REQUEST_URI'];
        if (substr($url, 0, strlen($App->httpRoot)) == $App->httpRoot) $url = substr($url, strlen($App->httpRoot));

        if ($url==$this->cronJobUrl)
        {
        	$this->Debug('The current page is recognized as cron job. No Auth is required.');
        	return;
        }

        //skip authorization if login page
        $requestPage = Navigation::GetBaseName();
        if ($requestPage==$this->loginPageUrl && $requestPage!=$this->loggedPageUrl)
        {
        	$this->Debug('The current page is recognized as login page. No Auth is required.');
        	return;
        }
		
        //Get current page url
        //if it's not defined - work with base page
        // i.e. if we have page /profile/edit_info/
        // and we dont' have access level for this page we check url /profile/
        $curPageUrl = Navigation::GetBaseName();
        if (defined('STATIC_URLS') && constant('STATIC_URLS')==TRUE)
        {
        	if (!isset($this->pages[$curPageUrl]))
        	{
        		$arr = explode('/', $curPageUrl);
        		$curPageUrl = $arr[0].'/';
        	}
        }
        
        // check if authorization is required and what level
        $this->curPageLevel = isset($this->pages[$curPageUrl]) ? $this->pages[$curPageUrl] : $this->defaultLevel;
        
        $this->Debug("Auth page url = <b>$curPageUrl</b>. Required page auth level = <b>{$this->curPageLevel}</b>");

        //Authorize user
        if ($this->Authorize())
        {
        	$this->RemoveUrlRequested();
        }
        else
        {
			if ($this->curPageLevel==0)
			{
				$this->Debug("No auth required for this page (curPageLevel=0)");
			}
			else
			{
				$this->Debug('Auth REQUIRED but user not logged in','save page to go (auto jump) & goto login page','AUTH ERROR');
	        	
				//remember where to go after login
	       		$this->SetUrlRequested();
	            
	       		//Jump to login page
	       		Navigation::Jump($this->GetLoginPageUrl());
			}
        }
                
        $this->Debug('User authorized', $this->isAuthorized);
    }

    /**
    * @return boolean
    * @desc primary authorization method
    */
    public function Authorize()
	{
		GLOBAL $Db;

		$sid = isset($_SESSION[$this->sidName]) ? $_SESSION[$this->sidName] : null;

		if (strlen($sid))
		{
			$this->Debug("Start Auth based on SID (it's not empty)");
			$qqSid = Database::Escape($sid);
			$res = $Db->Get('sid='.$qqSid, null, $this->table);
			if ($res != false)
			{
				// check access level
				if ($this->curPageLevel > $res['level'])
				{
					$this->Debug('SID Auth passed BUT user level is smaller than page level.', null, 'Error');
					return false;
				}
				else
				{
	                $this->user = $res;
					// update lastdate
					$Db->update('id='.$res['id'], array(
						'lastdate' => Format::DateTimeNow()
					), $this->table);
					$this->isAuthorized = true;
					$this->Debug('SID auth passed.', $res);
					return true;
				}
			}
			else
			{
				$this->Debug('SID auth failed', 'No records found with cur. SID', 'Error');
			}
		}
		else
		{
			$this->Debug('Try authorize user based on SID', 'failed. SID is undefined or empty');
		}

		return $this->AutoLogin();
	}

	/**
    * @return boolean
    * @param $login string
    * @param $password string
    * @desc log user in
    */
	public function Login($login, $password, $rememberMe=false)
	{
        GLOBAL $Db;

		if ($login == '' || $password == '')
		{
			$this->Debug('Login', null, 'login or password is empty');
			return false;
		}

		$qLogin = Database::Escape($login);
		$qPass = Database::Escape($password);
		
		if ($this->md5Crypt)
		{   
		    if ($this->flagSecuredPassword)
		    {
		        //if password is stored in secured form and we trying login via autologin
		        //no need to encode
		    }
		    else
		    {
		        $qPass = Database::Escape($this->EncryptPassword($password));
		    }
		}
		
		$res = $Db->Get("login=$qLogin AND password=$qPass", null, $this->table);
		if ($res === false)
		{
			$this->Debug('Login', null, 'Incorrect login/password');
			return false;
		}

		$now = Format::DateTimeNow();
		$sid = md5($res['login'].$now.'secure');

		// save autologin data
		if ($rememberMe) $this->SetAutoLogin($res['login'], $res['password']);

		// update data
		if (!isset($_SESSION['AUTH_last_login']))
		{
			$Db->Update('id='.$res['id'], array(
				'sid'		=> $sid,
				'lastdate'	=> $now,
				'lastlogin'	=> $now
			), $this->table);
			$_SESSION['AUTH_last_login'] = $res['lastlogin'];
		}
		else
		{
			$Db->Update('id='.$res['id'], array(
				'sid'		=> $sid,
				'lastdate'	=> $now
			), $this->table);
		}

		// previous visit info
		$_SESSION['AUTH_last_visit'] = $res['lastdate'];
		$_SESSION[$this->sidName] = $sid;

		$this->user = $res;
		$this->isAuthorized = true;
		
		if (INIT_NAME == 'main') StatActionHandler::Set('STAT_OBJECT_USER', 'STAT_OBJECT_USER_LOGIN');

		return true;
	}
	
	/**
    * Changes user password
    *
    * @param mixed $userId
    * @param mixed $password   -  if empty, password (8 letters) will be generated automatically
    */
	public function ResetPassword($userId, $password='')
	{
		GLOBAL $Db;
		
		if (empty($password)) $password = $this->CreatePassword(8);

        $newPassword = ($this->md5Crypt) ? $this->EncryptPassword($password) : $password;
		
        if ($Db->Update('id='.Database::Escape($userId), array('password' => $newPassword), $this->table))
        {
        	return $password;
		}
		
		return false;
	}
    
    /**
     * Create new password
     *
     * @param number $length
     * @return string
     */
    private function CreatePassword($length)
    {
        $chars = 'abcdefghijkmnopqrstuvwxyz023456789';
        srand((double)microtime()*1000000);
        $i = 0;
        $pass = '' ;
        while ($i <= $length)
        {
            $num = rand() % 33;
            $tmp = substr($chars, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }

        return $pass;
    }
    	
	/**
	 * Jumps to login page
	 */
	public function GetLoginPageUrl()
	{
		$page = $this->loginPageUrl;
		if (STATIC_URLS && substr(strtolower($page), 0, 7) != 'http://' && substr($page, 0, 3) != '[/]') $page = '[/]'.$page;
	
		return $page;
	}

	/**
	 * Jumps to logged page
	 */
    public function GetLoggedPageUrl()
	{
		$page = $this->loggedPageUrl;
		
		if ($this->autoJump && isset($_SESSION['AUTH_url_requested']) && strlen($_SESSION['AUTH_url_requested']))
		{
			$page = $_SESSION['AUTH_url_requested'];
		}
		
		if (STATIC_URLS && substr(strtolower($page), 0, 7) != 'http://' && substr($page, 0, 3) != '[/]') $page = '[/]'.$page;
		
		return $page;
	}

	/**
    * @return void
    * @desc log user out
    */
	public function Logout()
	{
		$this->RemoveUrlRequested();
        $this->RemoveAutoLogin();
		$this->ClearSessionVariables();
	}

	/**
	* @return boolean
	* @desc checks user with the same login
	*/
	public function LoginExists($login)
	{
		GLOBAL $Db;
		$login = Database::Escape($login);
		$check = $Db->Get('login='.$login, 'id', $this->table);
		return ($check !== false);
	}

    /**
    * @return void
    * @param void
    * @desc remember URL requested the authorization
    */
    public function SetUrlRequested()
    {
        if (Navigation::GetBaseName() != $this->loginPageUrl
            && Navigation::GetBaseName() != @$_SESSION['AUTH_script_requested']
        )
        {
            $_SESSION['AUTH_url_requested'] = Navigation::GetCurUrl();
            $_SESSION['AUTH_script_requested'] = Navigation::GetBaseName();
        }
    }

    /**
    * @return void
    * @param void
    * @desc unset requested URI
    */

    private function RemoveUrlRequested()
    {
        $_SESSION['AUTH_url_requested'] = '';
        $_SESSION['AUTH_script_requested'] = '';
        unset($_SESSION['AUTH_uri_requested']);
        unset($_SESSION['AUTH_script_requested']);
    }

    /**
    * @desc unset session auth sid_name
    */
    private function ClearSessionVariables()
    {
        $_SESSION[$this->sidName] = '';
        unset($_SESSION[$this->sidName]);
        unset($_SESSION['AUTH_last_login']);
        unset($_SESSION['AUTH_last_date']);
    }

	/**
    * @return boolean
    * @param void
    * @desc log member in if autologin is set
    */

	private function AutoLogin()
	{
	    GLOBAL $Db;

	    if (!$this->IsSetAutoLogin())
	    {
	    	$this->Debug('Try autologin', 'Failed. No cookies are set');
	    	return false;
	    }

    	$login = $_COOKIE[$this->sidName.'login'];
    	$password = $_COOKIE[$this->sidName.'password' ];
    	
    	
        $res = $Db->Get('login='.Database::Escape($login), null, $this->table);
		if ($res === false || $password != $this->EncryptPasswordForCookies($res['password']))
		{    
			$this->RemoveAutoLogin();
			$this->Debug('Autologin', 'Failed', 'Incorrect login/password');
			return false;
		}
		
		$this->Debug('Autologin', 'Good, trying to login as '.$login);

        $this->flagSecuredPassword = true;
        $result = $this->Login($res['login'], $res['password']);
        $this->flagSecuredPassword = false;
	    if ($result)
	    {
			// check access level
			if ($this->curPageLevel > $res['level']) return false;
			else return true;
	    }
	    else
	    {
	        return false;
	    }
	    
	}

	/**
    * @return boolean
    * @desc check if autologin data is set (cookie login&password are not empty
    */

	private function IsSetAutoLogin()
	{
		return (isset($_COOKIE[$this->sidName.'login']) && isset($_COOKIE[$this->sidName.'password']));
	}

	/**
    * @return void
    * @param $login string
    * @param $password string
    * @desc store user autologin data
    */

	private function SetAutoLogin($login, $password)
	{
	    
		$tm = 31536000;
	    Utils::SetCookie($this->sidName.'login', $login, $tm);
	    //cookie value should differ from hash in database
	    Utils::SetCookie($this->sidName.'password', $this->EncryptPasswordForCookies($password), $tm);
	}

	/**
    * @return void
    * @param void
    * @desc unset user autologin data
    */
	private function RemoveAutoLogin()
	{
		$tm = 31536000;
		Utils::SetCookie($this->sidName.'login', '', -$tm);
		Utils::SetCookie($this->sidName.'password', '' , -$tm);
		unset($_COOKIE[$this->sidName.'login']);
		unset($_COOKIE[$this->sidName.'password']);
	}

	private function ReadConfig($name)
	{
		GLOBAL $App;
		$config = $GLOBALS['AUTH_'.strtoupper($name)];

		if (!$this->CheckConfig($config)) $App->RaiseError("Authorization configuration $name has errors");

        $this->table 		 = $config['table'];
        $this->defaultLevel  = $config['default_level'];
        $this->loginPageUrl	 = $config['login_page'];
        $this->loggedPageUrl = $config['logged_page'];
        $this->autoJump 	 = $config['auto_jump'];
        $this->sidName 		 = $config['sid_name'];
        $this->pages 		 = $config['pages'];
        $this->md5Crypt 	 = $config['md5_crypt'];
	}

    /**
    * @return boolean
    * @param array $struct
    * @desc check if auth structure is valid
    */
    private function CheckConfig($struct)
    {
        if (!Utils::IsArray($struct)) return false;
        
        $required = array('table', 'default_level', 'login_page', 'logged_page', 'auto_jump', 'sid_name', 'pages', 'md5_crypt');
        $structOk = true;
        $structKeys = array_keys($struct);

        foreach ($required as $v)
        {
            if (!in_array($v, $structKeys))
            {
                $structOk = false;
                break;
            }
        }

        return $structOk;
    }

	private function Debug($name, $value=null, $error=null)
	{
		GLOBAL $Debug, $App;
		if ($App->debug) $Debug->AddDebug('AUTH', $name, $value, $error);
	}
	
	private function EncryptPassword($password)
	{
	    return md5('s0mEsALT'.$password);
	}
	
	private function EncryptPasswordForCookies($password)
	{
	    return md5('salTforC00kieS'.$password);
	}

}

$GLOBALS['Auth'] = new Authorization();
?>