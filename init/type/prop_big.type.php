<?php

class T_prop_big extends T_prop    
{
    public $allowMultiples;
    
    function __construct(array $info, Data $dataItem=null)
	{
        parent::__construct($info, $dataItem);
        
        $this->allowMultiples = isset($info['allow_multiples']) ? $info['allow_multiples'] : false;

        $this->tplFile = 'type/prop_big';
    }

    function RenderFormHtmlView()
    {
        return $this->RenderFormTplView();
    }
    
    function RenderFormTplView()
    {
        Application::LoadJs('[/]js/lisk/type/prop_big.js');
        
        $tpl = new Template();
		$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
		
		$value = is_array($this->value) ? $this->value : Utils::StrToProp($this->value);
		
        //render selected and all

        $allOptions = '';
        
		if (Utils::IsArray($this->values))
		{
		    $tpl->SetCurrentBlock('form_prop_row');
		    
			foreach ($this->values as $key=>$name)
			{
				if (is_array($name))
				{
					$key = $name['id'];
					$name = $name['name'];
				}
				
				$allOptions .= '<option value="'.$key.'">'.$name.'</option>';
				
				//add all multiples
				foreach ($value as $v1)
				{
				    if ($key != $v1) continue;
				    
				    $tpl->SetVariable(array(
                        'name'		=> $this->name,
                        'value'		=> $key,
                        'caption'	=> $name,
                    ));
                    $tpl->ParseCurrentBlock();
				}
			}
		}


        $tpl->SetcurrentBlock('form');
		$tpl->SetVariable(array(
			'name'          => $this->name,
			'params'        => $this->RenderFormParams(),
            'all_options'   => $allOptions,
		    'allow_multiples' => $this->allowMultiples ? 1 : 0
		));
		$tpl->ParseCurrentBlock();
		
		return $tpl->Get();
    }

}
