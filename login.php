<?php
require_once('init/init.php');

class LoginPage extends Page
{
	function __construct()
	{
		parent::__construct();
		
		$this->SetPostAction('login', 'Login');
		$this->SetGetAction('logout', 'Logout');
	}
	
	function Page()
	{
		$this->LoadTemplate(false, false);
		
		
	}
	
	function Logout()
	{
		$this->Auth->Logout();
		Navigation::Jump($this->Auth->GetLoginPageUrl());
	}
	
    function Login()
    {
		$rememberMe = (isset($_POST['remember_me']) && $_POST['remember_me'] == 1);
		
		if (!$this->Auth->Login($_POST['login'], $_POST['password'], $rememberMe))
		{
			$this->SetError('Incorrect login or password.');
			Navigation::Jump($this->Auth->GetLoginPageUrl());
		}
		else
		{
			Navigation::Jump($this->Auth->GetLoggedPageUrl());
		}
	}
}

$Page = new LoginPage();
$Page->Render();
?>