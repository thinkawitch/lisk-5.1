<?php
/*** LISK Type [stars] v 5.0
    
    how to use:
    'stars'=>LiskType::TYPE_STARS,
    
    or
    'stars'=>array(
        'type'=>LiskType::TYPE_STARS,
        'label'=>'Rating',
        'def_value'=> 0.5 // double
        'stars_count'=> 5, // default 5
        'split'=> 5, // each star can be splitted on split count
    )
    
***/

class T_stars extends LiskType
{
    public $maxStars;
    public $split;
    public $sizeStar = 16;
    
    function __construct(array $info, Data $dataItem=null)
    {
        parent::__construct($info, $dataItem);
 
        $this->maxStars = isset($info['stars_count']) ? $info['stars_count'] : 5;
        $this->split = isset($info['split']) ? $info['split'] : 1;
        $this->params = $info;
        $this->type = LiskType::TYPE_STARS;
        $this->tplFile = 'type/stars';
  
        Application::LoadCss('[/]css/lisk/type/stars.css');
        Application::LoadJs('[/]js/lisk/type/stars.js');
    }
    
    function Insert(&$values)
    {
        return @$values[$this->name];
    }
    
    function Update(&$values)
    {
        return @$values[$this->name];
    }

    function Delete(&$values)
    {
        return true;
    }
    
    function RenderFormView()
    {
        $tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
        
        if ($this->value > $this->maxStars)
        {
            $this->value = $this->maxStars;
        }
        
        $tpl->SetCurrentBlock('form');
        $caption = array(
            'stars_container_width' => $this->maxStars * $this->sizeStar,
            'stars_active_width'    => $this->value * $this->sizeStar,
            'stars_count'           => $this->maxStars,
            'star_size'             => $this->sizeStar,
            'split'                 => isset($this->params['split']) ? $this->params['split'] : 1,
            'name'                  => $this->name,
            'value'                 => $this->value,
            'uid'                   => uniqid(),
        
        	'autosave_params' => $this->autoSave ? $this->RenderAutoSaveParams() : '',
			'autosave'  => $this->autoSave ? " autosave='{$this->asId}' " : '',
        );
        $tpl->SetVariable($caption);
        $tpl->ParseCurrentBlock();

        return $tpl->Get();
    }
    
 	private function RenderAutoSaveParams()
    {
        if (!$this->autoSave || !$this->dataItem->value['id'])
        {
            $this->autoSave = false;
            return false;
        }
        
        $tpl = new Template();
        $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
        $tpl->SetCurrentBlock('autosave_params');
        $tpl->ParseVariable(array(
            'autosave_uid'  => $this->asId,
            'callback'      => $this->asCallback ? ','.$this->asCallback : '',
            'dataitem_name' => $this->dataItem->name,
            'dataitem_id'   => $this->dataItem->value['id'],
            'dataitem_field'=> $this->name,
            'handler_url'   => $this->asHandlerUrl,
        ), 'autosave_params');
        
        return $tpl->Get('autosave_params');
    }
    
    function RenderView($param1=null, $param2=null)
    {
        if ($this->value > $this->maxStars)
        {
            $this->value = $this->maxStars;
        }
        
        $tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
        $tpl->SetCurrentBlock('view');
        $tpl->SetVariable(array(
            'stars_container_width' => $this->maxStars * $this->sizeStar,
            'stars_active_width'    => $this->value * $this->sizeStar,
            'name'                  => $this->name,
            'value'                 => $this->value
        ));
        $tpl->ParseCurrentBlock();

        return $tpl->Get();
    }
}

?>