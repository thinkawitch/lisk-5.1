<?php
require_once('init/init.php');

class CurPage extends Page
{
	/**
	 * @var MembersZone
	 */
	private $MZ;
	
	function __construct()
	{
		parent::__construct();
		GLOBAL $App;
		
		$this->SetGetPostAction('login','submit','Login');
		$this->SetGetAction('login','LoginForm');
		
		$this->SetGetAction('logout','Logout');
		
		$this->SetGetPostAction('forgot_password', 'submit', 'RecoverPassword');
		$this->SetGetAction('forgot_password', 'RecoverPasswordForm');
		
		$this->SetGetPostAction('register', 'submit', 'Register');
		$this->SetGetAction('register', 'RegisterForm');
		
		$this->SetGetAction('password_sent', 'PasswordSent');
		
		$this->SetGetAction("change_password",'ChangeUserPassword');

		$this->LoadTemplate('modules/members_zone/members_zone');
		
		$App->Load('members_zone', 'mod');
		$this->MZ = new MembersZone();
		
	}
	
	function Page()
	{
		$this->ShowMembersHomePage();
	}
	
	function ShowMembersHomePage()
	{
		GLOBAL $Parser, $Auth, $App;
		
		if (!$Auth->isAuthorized) {
			Navigation::Jump($App->httpRoot.$Auth->loginPageUrl);
		}
		
		$DI = Data::Create($this->MZ->confDIMemberName);
		$DI->value = $Auth->user;
		
		$view['content'] = $Parser->MakeView($DI, $this->MZ->tplPath.'members_zone', 'home');
		
		$Parser->ParseView($view, 'view');
	}
	
	
	function Logout()
	{
		$this->Auth->Logout();
		Navigation::Jump($this->Auth->GetLoginPageUrl());
	}
	
	function LoginForm()
	{
		GLOBAL $Parser;
		
		$DI = Data::Create($this->MZ->confDIMemberName);
		
		$view['content'] = $Parser->MakeForm($DI, $this->MZ->tplPath.'login', 'login_form');
		
		$Parser->ParseView($view, 'view');
	}
	
	
    function Login()
    {
		$rememberMe = (@$_POST['remember_me']==1);
		
		if (!$this->Auth->Login($_POST['login'], $_POST['password'], $rememberMe))
		{
			$this->SetError("Incorrect login or password.");
			Navigation::Jump($this->Auth->GetLoginPageUrl());
		}
		else
		{
			Navigation::Jump($this->Auth->GetLoggedPageUrl());
		}
	}
	
	function RecoverPasswordForm()
	{
		GLOBAL $Parser;
		
		$DI = Data::Create($this->MZ->confDIMemberName);
		
		$view['content'] = $Parser->MakeForm($DI, $this->MZ->tplPath.'forgot_password', 'forgot_password_form');
		
		$Parser->ParseView($view, 'view');
	}
	
	function RecoverPassword()
	{
		GLOBAL $App;
		$DI = Data::Create($this->MZ->confDIMemberName);
		$email = Database::Escape($_POST['email']);
		$user = $DI->GetValue('email='.$email);
		if (Utils::IsArray($user))
		{
			$user['members_zone_url'] = 'http://'.$_SERVER['HTTP_HOST'].$App->httpRoot.'members_zone/';
			$App->Load('mail','utils');
			$EMail = new EMail('members_zone_forgot_password');
			$EMail->ParseVariables($user);
			$EMail->Send();
		}
		Navigation::Jump('[/]members_zone/password_sent/');
	}
	
	function PasswordSent()
	{
		GLOBAL $Parser;
		
		$view['content'] = $Parser->GetHTML($this->MZ->tplPath.'forgot_password', 'password_sent');
		
		$Parser->ParseView($view, 'view');
	}
	
	function RegisterForm()
	{
		GLOBAL $Parser;
		
		$DI = Data::Create($this->MZ->confDIMemberName);
		
		$view['content'] = $Parser->MakeForm($DI, $this->MZ->tplPath.'register', 'register_form');
		
		$Parser->ParseView($view, 'view');
	}
	
	function Register()
	{
		GLOBAL $App, $Auth;
		$member = $_POST;
		
		$uniq = $this->MZ->LoginIsUnique($member['login']);
		
		if (!$uniq)
		{
			$this->SetError('This login already exists.\\\nPlease try another.');
			Navigation::Jump($App->httpRoot.'members_zone/register/');
		}
		
		$this->MZ->RegisterMember($member);
		
		$Auth->Login($member['login'], $member['password']);
		
		Navigation::Jump('[/]members_zone/');
	}
	
	function ChangeUserPassword() {
		GLOBAL $Parser,$Auth,$App;
		$DI = new Data($this->MZ->confDIMemberName);
		
		if (isset($_POST['action'])) {
			if ($_POST['action']=='submit') {
				$DI->Update("id={$Auth->user['id']}",$_POST);
				$this->SetError('Password Changed!');
				Navigation::Jump($App->httpRoot.'members_zone/');
			}
		}
		$DI->value = $Auth->user;
		
		$view['content'] = $Parser->MakeForm($DI,$this->MZ->tplPath.'change_password','form');
		
		$view['navigation'] = $Parser->MakeView(array('name'=>'Change Password'), $this->MZ->tplPath.'members_zone', 'nav_page');
		$view['title'] = 'Change Password';
		
		$Parser->ParseView($view);
	}

}

$Page = new CurPage();
$Page->Render();
?>