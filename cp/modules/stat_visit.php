<?php
chdir('../');
require_once 'init/init.php';

define ('DAY', 60 * 60 * 24);

// ================================================================================
// TOP STATISTICS DATA
// ================================================================================

class CpStatVisitPage extends CPModulePage
{
	private $urlPrefix = 'module_stat_visit.php?z=x';
	
	private $list;
	private $day;
	private $month;
	private $year;

	/**
	 * @var CMSStatVisit
	 */
	private $CmsStatVisit;

	function __construct()
	{
		parent::__construct(false);
		
		$this->App->LoadCss('css/lisk/stat_visit.css');

    	$this->App->Load('stat_visit', 'mod');
		$this->App->LoadModule('installed/stat_visit/stat_visit.cms.php');
		$this->CmsStatVisit = new CMSStatVisit();

		$this->AddBookmark('View Statistics', $this->urlPrefix.'&action=view', 'img/modules/stat_visit/view_stat.gif');
		$this->AddBookmark('Keywords', $this->urlPrefix.'&action=keywords', 'img/modules/stat_visit/report_2.gif');
		$this->AddBookmark('Tech Data', $this->urlPrefix.'&action=tech', 'img/modules/stat_visit/tech.gif');
		$this->AddBookmark('Top', $this->urlPrefix.'&action=top', 'img/modules/stat_visit/top.gif');
		$this->AddBookmark('Trends', $this->urlPrefix.'&action=trends', 'img/modules/stat_visit/trends.gif');
		$this->AddBookmark('Visitors', $this->urlPrefix.'&action=visitors','img/modules/stat_visit/stat_users.gif');
		$this->AddBookmark('Settings', $this->urlPrefix.'&action=settings', 'img/modules/stat_visit/report.gif');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');

		$this->SetGetAction('view', 'View');
		$this->SetGetAction('keywords', 'Keywords');
		$this->SetGetAction('tech', 'TechData');
		$this->SetGetAction('top', 'Top');
		$this->SetGetAction('trends', 'Trends');

		// Report
		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('settings', 'submit', 'SettingsSave');

		// Click Stream
		$this->SetGetAction('clickstream', 'ClickStream');

		// Visitors
		$this->SetGetAction('visitors', 'Visitors');


		$this->SetGetAction('list_users', 'StatisticsListUsers');
		$this->SetGetAction('add_user', 'StatisticsAddUser');
		$this->SetGetPostAction('add_user', 'submit', 'StatisticsAddUserSubmit');

		//$this->AddBookmark('Random', 'statistics.php?action=random', 'img/modules/statistics/orders_new.gif');
		//$this->SetGetAction('random', 'StatisticsRandom');

	}

	function GetSnippet()
	{
		$StatVisit = new StatVisit($this->GetIID());
		$code = $StatVisit->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}

	private function GetIID()
	{
		GLOBAL $Db;
		return $Db->Get("name='stat_visit'", 'id', 'sys_modules');
	}

	function Page()
	{
		Navigation::Jump($this->urlPrefix.'&action=view');
	}

	function Top()
	{
		GLOBAL $Db, $Parser;

		$this->currentBookmark = 'Top';

		$this->SetTitle('Top', 'modules/stat_visit/uho.gif');

		$curUrl = $_SERVER['REQUEST_URI'];

		$this->AddLink('Top Pages', $curUrl.'&view=pages', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Top Referrers', $curUrl.'&view=referrers', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Top Entry Pages', $curUrl.'&view=enter_pages', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Top Search Terms', $curUrl.'&view=search_terms', 'img/modules/stat_visit/0_link.gif');

		$this->listFilter = $GLOBALS['LIST_STAT_TIMES'];

		if (!isset($_GET['view'])) $_GET['view'] = 'pages';
		if (!isset($_GET['cond'])) $_GET['cond'] = 14 * DAY;

		$cond = "date >= '" . date('Y-m-d', time() - @$_GET['cond']) . "'";

		switch ($_GET['view'])
		{
			case 'pages':
				$query = "SELECT id, page, COUNT(id) AS visits FROM stat_visits_history WHERE $cond GROUP BY page ORDER BY visits DESC LIMIT 0, 50";
			    break;
			    
			case 'referrers':
				$query = "SELECT id, referrer AS page, COUNT(id) AS visits FROM stat_visits WHERE $cond AND referrer!='' GROUP BY page ORDER BY visits DESC LIMIT 0, 50";
			    break;
			    
			case 'enter_pages':
				$query = "SELECT id, page, COUNT(id) AS visits FROM stat_visits WHERE $cond GROUP BY page ORDER BY visits DESC LIMIT 0, 50";
			    break;
			    
			case 'search_terms':
				$query = "SELECT id, search_engine_keyword AS page, COUNT(id) AS visits FROM stat_visits WHERE $cond AND search_engine_keyword!='' GROUP BY page ORDER BY visits DESC LIMIT 0, 50";
			    break;
		}

		$values = $Db->query($query);
		$max = 0;
		if ($values)
		{
			foreach ($values as $value) $max += $value['visits'];
			
			foreach ($values as $key => $value)
			{
				$values[$key]['visits_'] = round($value['visits'] * 1000 / $max) / 10;
				$values[$key]['n'] = $key + 1;
				$values[$key]['page'] = Utils::RemoveSessionId($value['page']);
			}
		}

		//$Data = new Data('stat_top');
		//$Data->value = $_GET;
		$GLOBALS['form_action'] = 'top';

		$Parser->SetListDecoration('ListTD1', 'ListTD2');
		$this->pageContent .= $Parser->MakeList($values, 'modules/stat_visit/top');

	}

	function TechData()
	{
		$this->currentBookmark = 'Tech Data';

		$this->SetTitle('Tech Data', 'modules/stat_visit/uho.gif');

		$curUrl = $_SERVER['REQUEST_URI'];

		$this->AddLink('Operation System', $curUrl.'&view=os', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Browsers', $curUrl.'&view=browser', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Display colors', $curUrl.'&view=color', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Display resolution', $curUrl.'&view=resolution', 'img/modules/stat_visit/0_link.gif');

		if (!isset($_GET['view'])) $_GET['view'] = 'os';
		if (!isset($_GET['cond'])) $_GET['cond'] = 14 * DAY;

		$this->listFilter = $GLOBALS['LIST_STAT_TIMES'];

		$this->pageContent .= $this->CmsStatVisit->RenderTechData();

	}

	function View()
	{
		
		$this->AddLink('Daily', Navigation::AddGetVariable(array('view'=>'daily')), 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Monthly', Navigation::AddGetVariable(array('view'=>'monthly')), 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Annual', Navigation::AddGetVariable(array('view'=>'annual')), 'img/modules/stat_visit/0_link.gif');

		$this->currentBookmark = 'View Statistics';
		$this->SetTitle('Statistics', 'modules/stat_visit/uho.gif');

		$what = isset($_GET['view']) ? $_GET['view'] : 'daily';
		
		switch ($what)
		{
			case 'daily':
				$this->pageContent .= $this->CmsStatVisit->RenderDaily();
				break;

			case 'monthly':
				$this->pageContent .= $this->CmsStatVisit->RenderMonthly();
				break;

			case 'annual':
				$this->pageContent .= $this->CmsStatVisit->RenderAnnual();
				break;
				
			case 'keywords':
				$this->pageContent .= $this->CmsStatVisit->RenderKeywordsReport();
				break;
		}
	}
	
	function Keywords()
	{
		$this->currentBookmark = 'Keywords';
		$this->SetTitle('Statistics', 'modules/stat_visit/uho.gif');
		
		$this->pageContent .= $this->CmsStatVisit->RenderKeywordsReport();
	}

	function Trends()
	{
		$this->currentBookmark = 'Trends';
		$this->SetTitle('Trends', 'modules/stat_visit/uho.gif');

		$curUrl = $_SERVER['REQUEST_URI'];

		if (!isset($_GET['view'])) $_GET['view'] = 'visit_trend';

		$this->AddLink('Visits Trends', $curUrl.'&view=visit_trend', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Bounce Rate', $curUrl.'&view=bounce', 'img/modules/stat_visit/0_link.gif');

		switch ($_GET['view'])
		{
			case 'visit_trend':
				$this->listFilter = $GLOBALS['LIST_STAT_VISIT_TREND_PERIODS'];
				$this->pageContent .= $this->CmsStatVisit->RenderVisitTrends();
				break;

			case 'bounce':
				$this->listFilter = $GLOBALS['LIST_STAT_PERIODS'];
				$this->pageContent .= $this->CmsStatVisit->RenderBounceRate();
				break;
		}
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();


		$this->SetTitle('Settings');
		$this->currentBookmark = 'Settings';

		$StatVisit = new StatVisit($this->GetIID());

		$this->settingsFields = array(
			'send_report'	=> array(
				'label'			=> 'Send report by email',
				'type'			=> 'flag',
			),
			'report_email'	=> 'input'
		);

		$this->settingsFieldsValues=array(
			'send_report'	=> $StatVisit->config['send_report'],
			'report_email'	=> $StatVisit->confReportEmail
		);
		$this->customizableDI = array();

		$this->pageContent .= $this->RenderSettingsPage($StatVisit);
	}

	function SettingsSave()
	{
		$StatVisit = new StatVisit($this->GetIID());

		$StatVisit->confReport = (@$_POST['send_report_checked'] == 1);
		$StatVisit->confReportEmail = $_POST['report_email'];

		$StatVisit->SaveSettings();

		Navigation::Jump($this->urlPrefix.'&action=settings');
	}

	function ReportSave()
	{
		GLOBAL $Db;
		$Db->Update("param='send_report'", array(
			'value'		=> @$_POST['send_report_checked'],
		), 'stat_visit_settings');

		$Db->Update("param='report_email'", array(
			'value'	=> $_POST['email']
		), 'stat_visit_settings');

		$Db->Update("param='report_frequency'", array(
			'value'		=> @$_POST['report_frequency']
		), 'stat_visit_settings');

		Navigation::Jump($this->urlPrefix.'&action=report');
	}

	function ClickStream()
	{
		GLOBAL $Parser,$Db;
		$this->SetGlobalTemplate('0');
		$this->LoadTemplate('modules/stat_visit/visit_details');

		$visits=$Db->Select("visitor_id='".$_GET['visitor_id']."'", 'id desc', null, 'stat_visits');

		$siv = sizeof($visits);

		$this->Tpl->SetCurrentBlock('visits_row');
		$rez2 = array();
		$rez3 = array();
		foreach ($visits as $key=>$row)
		{

			$urls = $Db->Select("visit_id=$row[id]", 'id ASC', null, 'stat_visits_history');
			$rez = array();
			foreach ($urls as $url)
			{
				$rez2['page'] = $url['page'];
				$rez2['time'] = $url['date'];
				$rez[] = $rez2;
			}

			foreach ($rez as $key2=>$row2)
			{
				if (isset($rez[$key2+1])) $time = Format::TimeLength((strtotime($rez[$key2+1]['time']) - strtotime($row2['time'])), 'short');
				else $time = '';
				
				$rez3[$key2]['time'] = $time;
				$rez3[$key2]['page'] = Utils::RemoveSessionId($row2['page']);
			}

			$last_rec = sizeof($rez3);
			$rez3[$last_rec-1]['time'] = '';

			$pages=$Parser->MakeList($rez3, 'modules/stat_visit/visit_details', 'pages');

			$this->Tpl->SetVariable(array(
				'VISIT_N'	=> $siv-$key,
				'DATE'		=> $row['date'],
				'PAGES'		=> $pages
			));
			$this->Tpl->ParseCurrentBlock();
		}

		$this->Tpl->SetCurrentBlock('visits');
		$this->Tpl->SetVariable('visit_info', $this->__ClickStreamVisitInfoBlock($_GET['visit_id']));
		$this->Tpl->ParseCurrentBlock();
		$this->pageContent=$this->Tpl->Get();
	}

	function __ClickStreamVisitInfoBlock($id)
	{
		GLOBAL $Db,$Parser;

		$info = $Db->Get("id=$id", null, 'stat_visits');
		
		$country = $Db->Get("id='{$info['country']}'", null, 'stat_visit_countries');
		if ($country)
		{
			$info['country_name'] = $country['name'];
			$info['country_short'] = strtolower($country['short_name']);
		}
		else
		{
			$info['country_short'] = 'xx';
		}
		$info['referrer_name'] = Utils::GetDomainName($info['referrer']);
		return $Parser->MakeView($info, 'modules/stat_visit/visit_details', 'visit_info');
	}

	function Visitors()
	{
		$this->currentBookmark = 'Visitors';
		$this->SetTitle('Visitors', 'modules/stat_visit/uho.gif');

		if (!isset($_GET['view'])) $_GET['view'] = 'list';
		$curUrl = $_SERVER['REQUEST_URI'];

		$this->AddLink('List Visitors', $curUrl.'&view=list', 'img/modules/stat_visit/0_link.gif');
		$this->AddLink('Add Rule', $curUrl.'&view=add', 'img/modules/stat_visit/0_link.gif');

		$Rule = new Data('stat_visit_rule');

		switch ($_GET['view'])
		{
			case 'list':
				$List = new CMSList($Rule);
				$List->Load('stat_visit', 'mod');
				$List->Init();
				$this->SetBack();
				$this->pageContent .= $List->Render();
				break;

			case 'add':
				$Add = new CMSAdd($Rule);
				if (@$_POST['action'] == 'submit')
				{
					$Add->Insert();
					Navigation::Jump($this->urlPrefix.'&action=visitors');
				}
				$this->pageContent = $Add->Render();
				break;
		}
	}

	function StatisticsRandom()
	{
		global $Db, $Parser, $LIST_USER_AGENTS, $LIST_SCREEN_RESOLUTIONS, $LIST_SCREEN_COLORS;
		//top menu

		$this->currentBookmark = 'Random';


		if ($_GET['users'])
		{
			for ($i = 0; $i < $_GET['users']; $i ++)
			{

				$time = time() - rand(1, $_GET['from']);

				$id = $Db->insert(array (
					'date' => date('Y-m-d H:i:s', $time),
					'ip' => rand(1, 254) . "." . rand(1, 254) . "." . rand(1, 254) . "." . rand(1, 254),
					'referrer' => 'site' . rand(1, 99),
					'page' => "page" . rand(1, 9),
					'bw' => $LIST_USER_AGENTS[rand(0, count($LIST_USER_AGENTS) - 1)],
					'screen_size' => $LIST_SCREEN_RESOLUTIONS[rand(0, count($LIST_SCREEN_RESOLUTIONS) - 1)],
					'screen_color' => $LIST_SCREEN_COLORS[rand(0, count($LIST_SCREEN_COLORS) - 1)],
				), 'stat_visits');

				$jmax = rand(1, $_GET['visits']);
				for ($j = 1; $j < $jmax; $j ++) {

					$Db->insert(array (
						'date' => date('Y-m-d H:i:s', $time + rand(0, DAY / 2)),
						'visit_id' => $id,
						'page' => 'page' . rand(1, 9),
					), 'stat_visits_history');

				}
			}
		}

		$Data = new Data('stat_random');
		$Data->value = $_GET;
		$GLOBALS['form_action'] = 'random';

		$this->pageContent .= $Parser->MakeDynamicForm($Data, 'statistics', 'form');
	}

}

$Page = new CpStatVisitPage();
$Page->Render();
?>