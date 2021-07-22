<?php
require_once('init/init.php');

class CpFileManagerPage extends CPPage
{
	private $filesList;
	private $dirsList;
	
	/**
	 * manager object
	 *
	 * @var FileManager
	 */
	private $fm;
	
	private $selfUri = 'file_manager.php?z=x';
	private $dir;

	private $errorMessages = array(
	    FileManager::ERROR_WRONG_FILE_TYPE => 'Wrong file type for this directory',
	    FileManager::ERROR_WRONG_DIRECTORY => 'Wrong directory',
	    FileManager::ERROR_FILE_NOT_UPLOADED => 'File not uploaded',
	);

	function __construct()
	{
		parent::__construct();
		
		$this->App->Load('cp_filemgr', 'lang');

		$this->AddBookmark(LANG_CP_FILEMGR_IMAGES, $this->selfUri.'&action=image', 'img/file_manager/ico_images.gif');
		$this->AddBookmark(LANG_CP_FILEMGR_DOCUMENTS, $this->selfUri.'&action=document', 'img/file_manager/ico_docs.gif');
		$this->AddBookmark(LANG_CP_FILEMGR_MEDIA, $this->selfUri.'&action=media', 'img/file_manager/ico_media.gif');
		$this->AddBookmark(LANG_CP_FILEMGR_FLASH, $this->selfUri.'&action=flash', 'img/file_manager/ico_flash.gif');
		$this->AddBookmark(LANG_CP_FILEMGR_FILES, $this->selfUri.'&action=file', 'img/file_manager/ico_files.gif');
		
		$this->title = '';
		$this->titlePicture = 'cms/I_file_manager.jpg';

		
		$this->SetGetAction('deldir', 'DeleteDirectory');
		$this->SetGetAction('delfile', 'DeleteFile');

		$this->SetPostAction('create_dir', 'CreateDir');
		$this->SetPostAction('upload_file', 'UploadFile');
        
		$this->SetGetAction('image', 'Images');
		$this->SetGetAction('document', 'Documents');
		$this->SetGetAction('media', 'Media');
		$this->SetGetAction('flash', 'Flash');
		$this->SetGetAction('file', 'Files');
	}

    function Page()
    {
		Navigation::Jump($this->selfUri.'&action=image');
	}
	
    function Images()
    {
		$this->title = LANG_CP_FILEMGR_SIMG;
		$this->currentBookmark = LANG_CP_FILEMGR_IMAGES;
		
		Navigation::SetBack($this->setBack);
		
		$this->dir = isset($_GET['dir']) ? $_GET['dir'] : 'Image/';
		//if (isset($_POST['dir'])) $this->dir = $_POST['dir'];
		
        $this->fm = new FileManager($this->dir);
		
		$this->dirsList = $this->fm->GetDirectorySubdirs();
		$this->filesList = $this->fm->GetDirectoryFiles();
		
		$this->SetContent('image');
	}

	function Documents()
	{
		$this->title = LANG_CP_FILEMGR_SDOCS;
		$this->currentBookmark = LANG_CP_FILEMGR_DOCUMENTS;
		
		Navigation::SetBack($this->setBack);
		
		$this->dir = isset($_GET['dir']) ? $_GET['dir'] : 'Document/';
		$this->fm = new FileManager($this->dir);
		
		$this->dirsList = $this->fm->GetDirectorySubdirs();
		$this->filesList = $this->fm->GetDirectoryFiles();

		$this->SetContent('document');
	}

	function Media()
	{
		$this->title = LANG_CP_FILEMGR_SMEDIA;
		$this->currentBookmark = LANG_CP_FILEMGR_MEDIA;
		
		Navigation::SetBack($this->setBack);
		
		$this->dir = isset($_GET['dir']) ? $_GET['dir'] : 'Media/';
		$this->fm = new FileManager($this->dir);
		
		$this->dirsList = $this->fm->GetDirectorySubdirs();
		$this->filesList = $this->fm->GetDirectoryFiles();

		$this->SetContent('media');
	}

	function Flash()
	{
		$this->title = LANG_CP_FILEMGR_SFLASH;
		$this->currentBookmark = LANG_CP_FILEMGR_FLASH;
		
		Navigation::SetBack($this->setBack);
		
		$this->dir = isset($_GET['dir']) ? $_GET['dir'] : 'Flash/';
		$this->fm = new FileManager($this->dir);
		
		$this->dirsList = $this->fm->GetDirectorySubdirs();
		$this->filesList = $this->fm->GetDirectoryFiles();

		$this->SetContent('flash');
	}

	function Files()
	{
		$this->title = LANG_CP_FILEMGR_SFILES;
		$this->currentBookmark = LANG_CP_FILEMGR_FILES;
		
		Navigation::SetBack($this->setBack);
		
		$this->dir = isset($_GET['dir']) ? $_GET['dir'] : 'File/';
		$this->fm = new FileManager($this->dir);
		
		$this->dirsList = $this->fm->GetDirectorySubdirs();
		$this->filesList = $this->fm->GetDirectoryFiles();

		$this->SetContent('file');
	}
	
    function SetContent($type)
    {
		GLOBAL $Parser;

		$mainTplBlock = 'filestab';
		
		$dirsTab = '';
		$filesTab = '';

		$folder = '';
		
		switch ($type)
		{
			case 'image':
				$filesTplBlock = 'listimage';
				break;
				
			case 'document':
				$filesTplBlock = 'listdocument';
				$folder = "Document";
				break;
				
			case 'media':
				$filesTplBlock = 'list';
				break;
				
			case 'file':
				$filesTplBlock = 'listdocument';
				$folder = "File";
				break;
				
			case 'flash':
				$filesTplBlock = 'list';
				break;
		}

		$decor1 = " style='background-color:#ffffff'";
		$decor2 = " style='background-color:#F8F8FE'";
		
		$navigation = $this->fm->GetNavigation();
		
        if (count($navigation) > 1)
		{
		    
		    $dirsTab .= $Parser->MakeView(
		        array(
		            'dir_name' => '..',
        	        'dir_path' => $navigation[count($navigation)-2]['link_dir_path'],
        	        'type' => $this->fm->GetDirectoryType($this->dir)
		        ),
		        'cms/file_manager',
		        'dirs_up'
            );
		}
		
		//render navigation
		array_reverse($navigation);
        $last = array_pop($navigation);
        array_reverse($navigation);
		$Parser->SetCaptionVariables(array('current_name' => $last['link_name']));
		$navigation = $Parser->MakeList($navigation, 'cms/file_manager', 'navigation');
		//
		
		//render directories
        if (Utils::IsArray($this->dirsList))
		{
		    $Parser->SetListDecoration($decor1, $decor2);
			$dirsTab .= $Parser->MakeList($this->dirsList, 'cms/file_manager', 'dirs');
		}


		//render files
		if (Utils::IsArray($this->filesList))
		{
    		//change decoration for files
    		if (count($this->dirsList)%2 == 0) $Parser->SetListDecoration($decor1, $decor2);
		    else $Parser->SetListDecoration($decor2, $decor1);
		    
			$filesTab = $Parser->MakeList($this->filesList, 'cms/file_manager', $filesTplBlock);
		}


		$this->pageContent .= $Parser->MakeView(
             array(
				'dirstab' => $dirsTab,
                'filestab' => $filesTab,
                'current_dir' => $this->fm->GetDirectory(),
                'navigation' => $navigation,
                //'rootdir' => $this->fm->__categories[$_GET['action']],
                //'cur_link_name' => $this->fm->link_path_cur['link_name'],
                'max_file_size' => ini_get('upload_max_filesize').'b',
                'folder'=>$folder
             ),
             'cms/file_manager',
             $mainTplBlock
        );
        
	}

    function CreateDir()
	{
	    $dirName = isset($_POST['dir_name']) ? $_POST['dir_name'] : null;
	    $dir = $_POST['dir'];
	    
	    $this->fm = new FileManager($dir);
		$this->fm->CreateDirectory($dirName);

		Navigation::Jump(Navigation::GetBack());
	}
	
    function DeleteDirectory()
	{
	    $this->fm = new FileManager($_GET['dir']);
        $this->fm->DeleteDirectory($_GET['dir'], $_GET['type']);

		Navigation::Jump(Navigation::GetBack());
	}
	
	
	function UploadFile()
	{
	    $this->fm = new FileManager($_POST['dir']);
		$result = $this->fm->UploadFile();
		$this->ShowError($result);

		Navigation::Jump(Navigation::GetBack());
	}

	function DeleteFile()
	{
	    $this->fm = new FileManager(dirname($_GET['file']));
		$this->fm->DeleteFile($_GET['file']);

		Navigation::Jump(Navigation::GetBack());
	}
	
	private function ShowError($result)
	{
	    GLOBAL $App;
	    if ($result === true) return;
	    
	    if (!isset($this->errorMessages[$result]))
	    {
	        $this->SetError('Something goes wrong');
	        return;
	    }
	    
	    $this->SetError($this->errorMessages[$result]);
	}
}

$Page = new CpFileManagerPage();
$Page->Render();
?>