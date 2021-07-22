<?php
require_once('init/init.php');

class CpLoginPage extends CPPage
{
	private $incorrectLogin;
	
	function __construct()
	{
		parent::__construct();
		$this->SetPostAction('login', 'Login');
		$this->SetGetAction('logout', 'Logout');
		
		Application::LoadJs('../js/jquery/jquery.browser.js');
	}

	function Page()
	{
		$this->LoginForm();
	}

	function LoginForm()
	{
		GLOBAL $Parser;
		$this->SetGlobalTemplate('global_home');
		$arr['IP_ADDRESS'] = $_SERVER['REMOTE_ADDR'];
		
		if ($this->incorrectLogin) $arr['incorrect_login'] = '';
		
		$this->pageContent .= $Parser->MakeView($arr, 'login', 'view');
	}
	
	function Logout()
	{
		GLOBAL $Auth;
		$Auth->Logout();
		Navigation::Jump($Auth->loginPageUrl);
	}

    function Login()
	{
		GLOBAL $Auth, $Db;

		// add data to cp logins table
		$login = trim($_POST['login']);
		$password = trim($_POST['password']);
		
		if (!$Auth->Login($login, $password))
		{
			$this->incorrectLogin = true;
			$this->LoginForm();
		}
		else
		{
			$Db->Insert(array(
				'date'	=> Format::DateTimeNow(),
				'login'	=> $login,
				'ip'	=> $_SERVER['REMOTE_ADDR'],
			    'level' => $Auth->user['level']
			), 'sys_cp_logins');
			
			Navigation::Jump($Auth->GetLoggedPageUrl());
		}
	}
}

$Page = new CpLoginPage();
$Page->Render();
?>