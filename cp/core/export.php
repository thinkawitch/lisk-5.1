<?php
chdir('../');
require_once('init/init.php');

class CpExportPage extends CPPage
{
    /**
     * @var Export
     */
	private $export;
	
	private $cond = null;

	function __construct()
	{
		parent::__construct();
		
		// GET PARAMS
		if (isset($_GET['cond'])) $this->cond = $_GET['cond'];
		
		//load object, if necessary
	    if (isset($_GET['load_name']) && isset($_GET['load_type']))
        {
			$this->App->Load($_GET['load_name'], $_GET['load_type']);
        }
        
		$this->App->Load('export', 'class');
		$this->export = new Export($_GET['type']);

		$this->SetPostAction('submit', 'Export');
	}

	public function Page()
	{
		GLOBAL $Parser;
		
		$this->ParseBack();
		$this->SetTitle('Export '.$this->export->dataItem->name, 'cms/add/uho.gif');
		
		$fields = $this->export->GetFieldsFromDB();
		$DI = $this->export->dataItem;
		
		$arrFields = array();
		
		foreach ($fields as $row)
		{
			$fieldName = $row['Field'];
			$caption = (isset($DI->fields[$fieldName])) ? $DI->fields[$fieldName]->label : $fieldName;
			$newField = array(
				'field'		=> $fieldName,
				'caption'	=> $caption
			);
			
			$arrFields[] = $newField;
		}

		$this->pageContent .= $Parser->MakeList($arrFields, 'cms/export', 'export_form');
	}
	
	public function Export()
	{
		$fields = $_POST['fields_to_export'];
		$format = $_POST['export_format'];
		
		$this->export->SetCond($this->cond);
		$this->export->csvFields = @$_POST['csv_add_fields_names'] == 1;
		$this->export->realLists = @$_POST['real_lists'] == 1;
		
		set_time_limit(360);
		$this->export->MakeExport($format, $fields);
	}

}

$Page = new CpExportPage();
$Page->Render();
