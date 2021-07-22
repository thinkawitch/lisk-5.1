<?php

$GLOBALS['LIST_FAQ_MODE'] = array(
	0	=> 'List',
	1	=> 'Tree'
);

$GLOBALS['FAQ_MODULE_INFO'] = array(
	'name'			=> 'FAQ',
	'sys_name'		=> LiskModule::MODULE_FAQ,
	'version'		=> '5.0',
	'description'	=> 'Frequently Asked Questions',
	'object_name'	=> 'Faq',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

/**
 * Faq Module Main Class
 *
 */
class Faq extends LiskModule
{
	/**
	 * indicates tree or list mode
	 * true if tree
	 *
	 * @var boolean
	 */
	public $confTreeMode;

	/**
	 * Faq section base url
	 * used in tree mode
	 *
	 * @var string
	 */
	public $confBaseUrl;

	public $confDICategoriesName;

	public $confDIQuestionsName;

	public $confDITreeName;

	public $tplPath = 'modules/faq_';

	/**
	 * Constructor
	 *
	 * @return Faq
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_FAQ;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath .= $instanceId.'/';

		$this->version = $GLOBALS['FAQ_MODULE_INFO']['version'];

		$this->confTreeMode	= ($this->config['tree_mode'] == 1) ? true : false;
		$this->confBaseUrl	= $this->config['base_url'];
		$this->confDICategoriesName = $this->config['categories_di'];
		$this->confDIQuestionsName	= $this->config['questions_di'];
		$this->confDITreeName		= $this->config['tree_di'];

		$this->Debug('confTreeMode', $this->confTreeMode);
		$this->Debug('confBaseUrl', $this->confBaseUrl);
		$this->Debug('categories DI', $this->confDICategoriesName);
		$this->Debug('questions DI', $this->confDIQuestionsName);
		$this->Debug('faq tree', $this->confDITreeName);
	}

	public function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['tree_mode'] = ($this->confTreeMode)?1:0;
		$this->config['base_url'] = $this->confBaseUrl;
		$this->config['categories_di'] = $this->confDICategoriesName;
		$this->config['questions_di'] = $this->confDIQuestionsName;
		$this->config['tree_di'] = $this->confDITreeName;
		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/faq/faq.install.mod.php', 1);
		installFaqModule($instanceId, $params['path'], $params['page_name']);
	}

	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/faq/faq.install.mod.php', 1);
		uninstallFaqModule($this->iid);
		parent::Uninstall();
	}

	/**
	 * Render FAQ page
	 *
	 * @return HTML
	 */
	public function Render()
	{
		if ($this->confTreeMode) return $this->RenderTree();
		else return $this->RenderList();
	}

	private function RenderTree()
	{
		GLOBAL $App,$Parser, $Scms;

		$App->Load('tree', 'utils');
		$Tree = new Tree($this->confDITreeName);
		$FaqCategory = new Data($this->confDICategoriesName);

		switch ($Tree->curMode)
		{
			case TREE_NODE_LIST:
				$FaqCategory->Select('parent_id=1');
				return $Parser->MakeList($FaqCategory, $this->tplPath.'faq', 'categories');
				break;
				
			case TREE_POINT_LIST:
				$FaqCategory->Get("id={$Tree->cl}");
				
				//fix not to render faq root category into navigation
				$originalParents = $Tree->parents;
				$Tree->parents = str_replace('<1>', '', $Tree->parents);
				$Scms->AddNavigation($Tree->GetNavigationRows());
				$Tree->parents = $originalParents;

				$result = $Parser->MakeView($FaqCategory, $this->tplPath.'faq', 'navigation');
				$FaqQuestion = Data::Create($this->confDIQuestionsName);
				$FaqQuestion->Select('parent_id='.$Tree->cl);
				$result	.= $Parser->MakeList($FaqQuestion, $this->tplPath.'faq', 'questions');

				$result	.= $Parser->MakeList($FaqQuestion, $this->tplPath.'faq', 'answers');
				return $result;
				
				break;
		}
	}

	private function RenderList()
	{
		GLOBAL $Parser;
		$FaqQuestion = Data::Create($this->confDIQuestionsName);
		$FaqQuestion->ReSet('list');
		$FaqQuestion->Select();
		$result	= $Parser->MakeList($FaqQuestion, $this->tplPath.'faq', 'questions');
		$result	.= $Parser->MakeList($FaqQuestion, $this->tplPath.'faq', 'answers');
		return $result;
	}
	
    public function UpdateBaseUrl($baseUrl)
	{
	    GLOBAL $Db;
	    
	    if (!isset($this->config['base_url'])) return;
	    if ($this->config['base_url'] == $baseUrl) return;
	    
	    $oldUrl = $this->config['base_url'];
        
	    //save module settings
	    $this->config['base_url'] = $baseUrl;
		$this->SaveConfig();
		
		$len = strlen($oldUrl) + 1;
		
		//update categories urls
		$di = Data::Create($this->confDICategoriesName, false);
		$table = $di->table;
		$sql = "UPDATE $table
			SET url = CONCAT('$baseUrl', SUBSTRING(url, $len))
		";
		$Db->Query($sql);
		
		//update questions urls
		$di = Data::Create($this->confDIQuestionsName, false);
		$table = $di->table;
		$sql = "UPDATE $table
			SET url = CONCAT('$baseUrl', SUBSTRING(url, $len))
		";
		$Db->Query($sql);
	}
	
}

?>