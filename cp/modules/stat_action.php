<?php
chdir('../');
require_once('init/init.php');

class CpStatActionPage extends CPModulePage
{
    /**
     * @var CMSStatAction
     */
	private $CMSStatAction;
	
	private $selfUri = 'module_stat_action.php?z=x';
	
	function __construct()
	{
		parent::__construct(false);
		
		GLOBAL $App;
	
		$App->Load('stat_action', 'mod');
		$App->LoadModule('installed/stat_action/stat_action.cfg.php', 1);
		$App->LoadModule('installed/stat_action/stat_action.cms.php');
		$this->CMSStatAction = new CMSStatAction();
		
		$this->AddBookmark('Statistics', $this->selfUri.'&action=default', 'img/modules/stat_action/view.gif');
		$this->AddBookmark('Report', $this->selfUri.'&action=report', 'img/modules/stat_action/report.gif');
		$this->AddBookmark('Settings', $this->selfUri.'&action=settings', 'img/modules/stat_action/notification.gif');
		
		$this->SetGetPostAction('default', 'submit', 'Page');
		$this->SetGetAction('default', 'Page');
		
		$this->SetGetPostAction('report', 'submit', 'ReportSubmit');
		$this->SetGetAction('report', 'Report');
		
		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');
		
		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SettingsSave');
	}
	
	private function GetIid()
	{
		return $this->Db->Get("name='stat_action'", 'id', 'sys_modules');
	}
	
	function Page()
	{
		$this->SetBack();
		$this->currentBookmark = 'Statistics';
		
		$this->SetTitle("Action Statistics", 'modules/stat_action/uho.gif');
        
		$form = Utils::IsArray($_POST) ? $_POST : $_GET;
		
		$object = @$form['object'];
		$action = @$form['_action'];
		$year = @$form['year'];
		$month = @$form['month'];
		$day = @$form['day'];
		
		if ($this->CMSStatAction->InitFilter($object, $action, $year, $month, $day))  $this->pageContent .= $this->CMSStatAction->Render();
	}
	
	function Report()
	{
		GLOBAL $Parser;
		
		$this->SetBack();
		$this->currentBookmark = 'Report';
		$this->SetTitle("Action Statistics", 'modules/stat_action/uho.gif');
        
		$values = Utils::IsArray($_POST) ? $_POST : null;
		$view = $this->CMSStatAction->RenderReport($values, true);
		
		$this->pageContent .= $Parser->MakeView($view, 'modules/stat_action/stat_action', 'report');
		
	}
	
	function ReportSubmit()
	{
		GLOBAL $Parser;
		
		$this->currentBookmark = 'Report';
		$this->SetTitle('Action Statistics', 'modules/stat_action/uho.gif');
		
		$values = Utils::IsArray($_POST) ? $_POST : null;
		$view = $this->CMSStatAction->RenderReport($values, true);
		
		$this->pageContent .= $Parser->MakeView($view, 'modules/stat_action/stat_action', 'report');
		
	}

	
	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Settings');
		$this->currentBookmark = 'Settings';

		$StatAction = new StatAction($this->GetIid());

		$this->settingsFields = array (
			'send_report'	=> array(
				'label'			=> 'Send report by email',
				'type'			=> 'flag',
			),
			'report_email'	=> 'input',
			'report_period'	=> array(
				'type'   => 'radio',
				'object' => 'def_stats_report_period',
				'label'	 => 'Report Periodicity',
			),
		);

		$this->settingsFieldsValues = array(
			'send_report'	=> $StatAction->config['send_report'],
			'report_email'	=> $StatAction->confReportEmail,
			'report_period'	=> $StatAction->confReportPeriod,
		);
		$this->customizableDI = array();
		
		$this->pageContent .= $this->RenderSettingsPage($StatAction);
	}

	function SettingsSave()
	{
		$statAction = new StatAction($this->GetIid());

		$statAction->confReport = (isset($_POST['send_report_checked']) && $_POST['send_report_checked'] == 1);
		$statAction->confReportEmail = $_POST['report_email'];
		$statAction->confReportPeriod = $_POST['report_period'];

		$statAction->SaveSettings();
		
		//update cron job
		$this->Db->Update("name='stat_action'", array('periodicity'=> 1440 * $statAction->confReportPeriod), 'sys_cron_jobs');

		Navigation::Jump($this->selfUri.'&action=settings');
	}
}

$Page = new CpStatActionPage();
$Page->Render();

?>