<?php
chdir('../');
require_once('init/init.php');

class CpEventsPage extends CPModulePage
{
	/**
	 * @var Calendar
	 */
	private $Calendar;
	
	/**
	 * @var Events
	 */
	private $Events;

	function __construct()
	{
		parent::__construct(true);

		$this->App->Load(LiskModule::MODULE_EVENTS,'mod');
		$this->App->Load('calendar', 'class');

		$this->titlePicture = 'modules/events/uho.gif';
		$this->Events = new Events($this->iid);

		$this->AddBookmark('Events', '?action=calendar', 'img/modules/events/events.gif');
		$this->AddBookmark('All Events', '?action=list', 'img/modules/events/events_all.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/events/settings.gif');

		$this->SetGetAction('settings', 'Settings');
		$this->SetGetPostAction('event_types', 'delete_selected', 'EventTypesDeleteSelected');

		$this->SetGetAction('list', 'ListAllEvents');

		$this->SetGetAction('calendar', 'Calendar');

		//GET SNIPPET
		$this->SetGetAction('get_snippet','GetSnippet');

	}

	function Page()
	{
		$this->Calendar();
	}

	function Calendar()
	{
		GLOBAL $Parser;

		$this->SetBack();
		$this->ParseBack();
		$this->currentBookmark = 'Events';
		$this->SetTitle('Events');

		$diEvent = Data::Create($this->Events->confDIName, false);
		$Calendar = new Calendar('module_events.php', $diEvent->table);
		$Calendar->SetTplName('modules/events/calendar');

		$EventsList = new CMSList($this->Events->confDIName);
        $EventsList->Init();
		$EventsList->cond = "SUBSTRING(date, 1, 10)='{$Calendar->currentDate}'";

		$EventsList->MakeLinkButtons();

		$this->pageContent .= $Parser->MakeView(array(
			'calendar'	=> $Calendar->Render(),
			'list'		=> $EventsList->Render()
		), 'modules/events/events', 'view');
	}

	function ListAllEvents()
	{
		$this->ParseBack();
		$this->SetBack();
		$this->currentBookmark = 'All Events';
		$this->SetTitle('All Events');

		$EventsList = new CMSList($this->Events->confDIName);
        $EventsList->Init();
        
		$this->Paging->SwitchOn('cp');
		$EventsList->MakeLinkButtons();
		$this->pageContent = $EventsList->Render();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();
		$this->SetTitle('Events Settings');
		$this->currentBookmark = 'Settings';


		$this->customizableDI = array($this->Events->confDIName);

		$this->pageContent .= $this->RenderSettingsPage($this->Events);
	}


	function GetSnippet()
	{
		$code = $this->Events->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpEventsPage();
$Page->Render();
?>