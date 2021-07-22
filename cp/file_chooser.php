<?php
require_once('init/init.php');

class CpFileChooserPage extends CPPage
{
    private $selfUri = 'file_chooser.php?z=x';
	private $dir;
	
	/**
	 * @var FileManager
	 */
	private $fileManager;
	
	private $elementBack;

	function __construct()
	{
		parent::__construct();

		$this->title = 'File Chooser';
		$this->titlePicture = 'cms/I_file_manager.jpg';

		if (isset($_GET['dir'])) $this->dir = $_GET['dir'];
        
		$this->elementBack =& $_SESSION['elem_back'];
		if (isset($_GET['elem_back'])) $this->elementBack = $_GET['elem_back'];

		$this->fileManager = new FileManager($this->dir);

		$this->SetGetAction('browse', 'Browse');
		
		$this->SetGlobalTemplate('cms/file_chooser/global');

	}

	function Page()
	{
		Navigation::Jump($this->selfUri.'&action=browse&dir='.$this->dir);
	}

	function Browse()
	{
		GLOBAL $Parser;
        
		$dirsList = $this->fileManager->GetDirectorySubdirs();
		$filesList = $this->fileManager->GetDirectoryFiles();
		
		$dirsTab = '';
		$filesTab = '';
        
		$Parser->SetListDecoration(" style='background-color:#ffffff'", " style='background-color:#F8F8FE'");
		
	    $navigation = $this->fileManager->GetNavigation();
	    
		if (count($navigation) > 1)
		{
		    array_unshift(
		        $dirsList,
		        array(
        	        'dir_name' => '..',
        	        'dir_path' => $navigation[count($navigation)-2]['link_dir_path'],
        	        'type' => ''
            ));
		}
		elseif (count($navigation) > 0)
		{
		    array_unshift(
		        $dirsList,
		        array(
		        	'dir_name' => '..',
		        	'dir_path' => '',
		        	'type' => $this->fileManager->GetDirectoryType($this->dir)
		    ));
		}
		
		if (Utils::IsArray($dirsList))
		{
			$dirsTab .= $Parser->MakeList($dirsList, 'cms/file_chooser/file_chooser', 'dirs');
		}


		if (Utils::IsArray($filesList))
		{
		    if (count($dirsList) % 2 == 0) $Parser->SetListDecoration(" style='background-color:#ffffff'", " style='background-color:#F8F8FE'");
		    else $Parser->SetListDecoration(" style='background-color:#F8F8FE'", " style='background-color:#ffffff'");
		    
		    $filesTab = $Parser->MakeList($filesList, 'cms/file_chooser/file_chooser', 'listdocument');
		}
		
		//render navigation
		array_reverse($navigation);
        $last = array_pop($navigation);
        array_reverse($navigation);
		$Parser->SetCaptionVariables(array('current_name' => $last['link_name']));
		$navigation = $Parser->MakeList($navigation, 'cms/file_chooser/file_chooser', 'navigation');
		//

		$this->pageContent .= $Parser->MakeView(
		    array(
                'dirstab'  => $dirsTab,
                'filestab' => $filesTab,
                'current_dir' => $this->fileManager->GetDirectory(),
                'navigation' => $navigation,
                'root_path' => '',
                'elem_back' => $this->elementBack,
            ),
            'cms/file_chooser/file_chooser',
            'filestab'
        );

	}
}

$Page = new CpFileChooserPage();
$Page->Render();

?>