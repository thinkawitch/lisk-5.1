<?php
/**
 * Lisk Type Password
 * @package lisk
 *
 */
class T_password extends LiskType
{
	/**
	 * how to display password, use *** for example
	 *
	 * @var string
	 */
	public $view;
	
	/**
	 * @var boolean
	 */
	public $md5Crypt;
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		
		$this->view	= isset($info['view']) ? $info['view'] : false;
		$this->type = LiskType::TYPE_PASSWORD;
		$this->tplFile = 'type/password';
		
		$this->md5Crypt = isset($info['md5_crypt']) ? $info['md5_crypt'] : false;
	}
	
	function Insert(&$values)
	{
		if (!isset($values[$this->name]) || !strlen($values[$this->name])) return false;
		
		return ($this->md5Crypt) ? md5($values[$this->name]) : $values[$this->name];
	}
	
	function Update(&$values)
	{
		if (!isset($values[$this->name]) || !strlen($values[$this->name])) return false;
		
		return ($this->md5Crypt) ? md5($values[$this->name]) : $values[$this->name];
	}
	
	function Delete(&$values)
	{
		return true;
	}

	
	function RenderFormView()
	{
		switch ($this->formRender)
		{
			case 'tpl':
				$tpl = new Template();
				$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
				$tpl->ParseVariable(array(
					'NAME'	=> $this->name,
					'PARAMS'=> $this->RenderFormParams(),
					), 'form');
				return $tpl->Get();
				break;
				
			default:
				return '<input type="password" name="'.$this->name.'" '.$this->RenderFormParams().' />';
				break;
			
		}
	}
	
	function RenderView($param1=null, $param2=null)
	{
		if ($this->view === true) return $this->value;
		else return $this->view;
	}
}

?>