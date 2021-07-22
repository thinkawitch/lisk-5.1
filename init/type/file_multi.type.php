<?php
/**
 * Lisk Type File Multi
 * @package lisk
 *
 */
class T_file_multi extends LiskType
{
	public $path;
	public $httpPath;
	public $maxUploadSize;
	
	public $storeTable;
	
	/**
	 * @var Data
	 */
	public $dataItem;

	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		GLOBAL $App;
		$App->Load('filesystem', 'utils');

		$info['path'] = FileSystem::NormalizeDirPath($info['path']);
		$this->httpPath = $info['path'];
		$this->path     = $App->sysRoot.$App->filePath.$info['path'];

		$this->maxUploadSize = ini_get('upload_max_filesize').'b';
		
		$this->storeTable = $info['store_table'];
		
		$this->type = LiskType::TYPE_FILE_MULTI;
		$this->tplFile = 'type/file_multi';
		
		Application::LoadJs('[/]js/lisk/type/file_multi.js');
	}

	/**
	 * Update files after dataItem is added/updated
	 * @param integer $parentId
	 */
	public function UpdateFiles($parentId)
	{
	    //do nothing if parent id is absent
	    if (!$parentId) return false;
	    
	    GLOBAL $Db, $FileSystem, $App;
	    $newValue = array();
	    
	    //prev files
	    $prevValue = isset($_POST[$this->name.'_prev']) ? Utils::StrToProp($_POST[$this->name.'_prev']) : array();
	    
	    //delete files
	    $deleteIds = isset($_POST[$this->name.'_delete']) ? $_POST[$this->name.'_delete'] : null;
	    if (Utils::IsArray($deleteIds))
	    {
	        $cond = 'id IN ('.implode(',', $deleteIds).')';
	        $toDelete = $Db->Select($cond, null, null, $this->storeTable);
	    
	        if (Utils::IsArray($toDelete))
	        {
    	        foreach ($toDelete as $one)
    	        {
    	            //delete from disk
        	        $FileSystem->DeleteFile($this->path.$one['store_name']);
        	        
        	        //delete from array
        	        foreach ($prevValue as $k=>$v)
        	        {
        	            if ($v == $one['id']) unset($prevValue[$k]);
        	        }
    	        }
	        }
	        
	        //delete from table
	        $Db->Delete($cond, $this->storeTable);
	    }
	    
	    //new files
		$uploaded = $this->UploadFiles($parentId);
		if (Utils::IsArray($uploaded))
		{
		    $newValue = $uploaded; //Utils::PropToStr($uploaded);
		}
		
		//update field value
		$newValue = Utils::MergeArrays($prevValue, $newValue);
		$Db->Update('id='.$parentId, array($this->name => Utils::PropToStr($newValue)), $this->dataItem->table);
	}
	
	public function Insert(&$values)
	{
	    //work is in UpdateFiles(), which should be called in TgerAfterInsert()
	}
	
	public function Update(&$values)
	{
		//work is in UpdateFiles(), which should be called in TgerAfterUpdate()
	}
	
	public function Delete(&$values)
	{
	    GLOBAL $Db, $FileSystem;
	    
        if (!Utils::IsArray($values)) return;

        foreach ($values as $row)
        {
            $fileIds = isset($row[$this->name]) ? Utils::StrToProp($row[$this->name]) : null;
    		if (!$fileIds) continue;
    		
    		$cond = 'id IN ('.implode(',', $fileIds).')';
    	    $toDelete = $Db->Select($cond, null, null, $this->storeTable);
    	    if (Utils::IsArray($toDelete))
    	    {
    	        foreach ($toDelete as $one)
    	        {
    	            //delete from disk
        	        $FileSystem->DeleteFile($this->path.$one['store_name']);
    	        }
    	    }
    	    
    	    $Db->Delete($cond, $this->storeTable);
        }
	}


	private function RenderFormTpl()
	{
		$fileIds = Utils::StrToProp($this->value);
        
		if ($fileIds) return $this->RenderFormTplUpdate();
		else return $this->RenderFormTplAdd();
	}
	
	private function RenderFormTplAdd()
	{
	    $tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		$tpl->SetCurrentBlock('new');
		$tpl->SetVariable(array(
			'name'	   => $this->name,
			'max_size' => $this->maxUploadSize,
			'params'   => $this->RenderFormParams(),
		));
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
	}
	
	private function RenderFormTplUpdate()
	{
	    GLOBAL $Db, $App;
	    
	    $fileIds = Utils::StrToProp($this->value);
	    
	    $tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		$fileIds = Utils::StrToProp($this->value);
	    $cond = 'id IN ('.implode(',', $fileIds).')';
	    $rows = $Db->Select($cond, null, null, $this->storeTable);
	    
	    if (Utils::IsArray($rows))
	    {
    	    foreach ($rows as $row)
    	    {
    	        $fullname = $this->path.$row['store_name'];
    	        if (!file_exists($fullname)) continue;
    
    	        $one = array(
    	            'file_id' => $row['id'],
    	            'file_name' => $row['store_name'],
    	            'file_link' => $App->httpRoot.$App->filePath.$this->httpPath.$row['store_name'],
    	            'file_size' => Format::FileSize(filesize($fullname)),
    	            'name' => $this->name,
    	        );
    	        
    	        $tpl->SetCurrentBlock('uploaded_files_row');
    	        $tpl->SetVariable($one);
    	        $tpl->ParseCurrentBlock();
    	    }
    	    
    	    $tpl->SetCurrentBlock('uploaded_files');
    	    $tpl->ParseCurrentBlock();
	    }
		
		$tpl->SetCurrentBlock('update');
		$tpl->SetVariable(array(
			'upload_new' => $this->RenderFormTplAdd(),
		    'name' => $this->name,
		    'value' => $this->value,
		));
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
	}
	

	public function RenderFormView()
	{
		return $this->RenderFormTpl();
	}

	public function RenderView($param1=null, $param2=null)
	{
	    if ($this->formRender == 'tpl')
		{
		    return $this->RenderTplView();
		}
		
		//TODO
	}

	public function RenderTplView()
	{
	    $files = $this->PrepareFormView();
	    if (!Utils::IsArray($files)) return;
	    
	    
		$tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		foreach ($files as $one)
		{
		    $tpl->SetCurrentBlock('view_row');
		    $tpl->SetVariable($one);
		    $tpl->ParseCurrentBlock();
		}
		
		$tpl->SetCurrentBlock('view');
		$tpl->ParseCurrentBlock();
		
		
		return $tpl->Get();
	}
	
	
	private function PrepareFormView()
	{
	    GLOBAL $Db, $App;
	    
	    $ids = Utils::StrToProp($this->value);
	    if (!Utils::IsArray($ids)) return;
	    
	    //select files from db
	    $cond = 'id IN ('.implode(',', $ids).')';
	    $rows = $Db->Select($cond, null, null, $this->storeTable);
	    if (!Utils::IsArray($rows)) return;
	    
	    $files = array();
	    
	    foreach ($rows as $row)
	    {
	        $fullname = $this->path.$row['store_name'];
	        if (!file_exists($fullname)) continue;

	        $files[] = array(
	            'name' => $row['store_name'],
	            'link' => $App->httpRoot.$App->filePath.$this->httpPath.$row['store_name'],
	            'size' => Format::FileSize(filesize($fullname)),
	        );
	    }
	    
	    
	    return $files;
	}
	
	
	/**
	 * @return array of file ids
	 */
	private function UploadFiles($parentId)
	{
		GLOBAL $FileSystem, $App, $Db;
		
		$formField = $this->name;
		if (!isset($_FILES[$formField])) return;
		
		$files = $_FILES[$formField];
        if (!Utils::IsArray($files)) return;
        
        // create folder(s) if not exist(s)
		if (!file_exists($this->path)) $FileSystem->CreateDir($this->path);
		
		$uploaded = array();
        
	    foreach ($files['name'] as $k=>$originalName)
        {
            $error = isset($files['error'][$k]) ? $files['error'][$k] : 4;
            $size = isset($files['size'][$k]) ? $files['size'][$k] : 0;
            $tmpName = isset($files['tmp_name'][$k]) ? $files['tmp_name'][$k] : '';
            
            //skip empty or not uploaded
            if ($error > 0 || $size == 0) continue;
            
            //skip not really uploaded
            if (!is_uploaded_file($tmpName)) continue;
            
            
		    $storeName = $this->CopyFile($tmpName, $this->path.$originalName);
		    
		    if (strlen($storeName))
		    {
		        $insert = array(
		        	'parent_id' => $parentId,
		        	'store_name' => $storeName,
		        	'original_name' => $originalName
		        );
		        
		        $fileId = $Db->Insert($insert, $this->storeTable);
		        if ($fileId) $uploaded[] = $fileId;
		    }
        }
        
        return $uploaded;
        
	}

	private function CopyFile($tmp, $dst)
	{
		GLOBAL $FileSystem;
		
		$pathParts = pathinfo($dst);
		$dstDir = FileSystem::NormalizeDirPath($pathParts['dirname']);
		$dstFile = $pathParts['basename'];
		$newName = FileSystem::MakeFilenameUnique($dstDir, $dstFile);
		
		$dstFile = $dstDir.$newName;
		
		//TODO proverit'
		if (!$FileSystem->CopyFile($tmp, $dstFile)) return '';
		else return basename($dstFile);
	}

}
?>