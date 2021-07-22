<?php
/**
 * Lisk Type PropList
 * each record contains own list of items
 * @package lisk
 *
 */
class T_prop_list extends LiskType
{
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
		$this->type = LiskType::TYPE_PROP_LIST;
		$this->tplFile = 'type/prop_list';
	}

	static public function StrToPropList($str)
	{
		$arr = explode('[|]', $str);
		$props = array();
		if (Utils::IsArray($arr))
		{
			foreach ($arr as $value)
			{
				 $item = explode('[*]', $value);
				 if (isset($item[0]) && isset($item[1]))
				 {
					$props[] = array('key' => $item[0], 'value' => $item[1]);
				 }
			}
		}

		return $props;
	}

	function Insert(&$values)
	{
		$newValues = self::StrToPropList(@$values[$this->name]);
		return serialize($newValues);
	}

	function Update(&$values)
	{
		$newValues = self::StrToPropList(@$values[$this->name]);
		return serialize($newValues);
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
				
				$propListResult = '';

				if ($this->value != '')
				{
					$propListResultArr = array();
					foreach (unserialize($this->value) as $val)
					{
						$val['key'] = htmlentities($val['key']);
						$val['value'] = htmlentities($val['value']);
						$propListResultArr[] = $val['key'].'[*]'.$val['value'];
					}
					$propListResult = implode('[|]', $propListResultArr);
				}

				$tpl = new Template();
				$tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));
				$tpl->ParseVariable(array(
    				'NAME'	=> $this->name,
    				'PROPLIST_RESULT' => $propListResult,
    				'PARAMS' => $this->RenderFormParams(),
    			), 'form');

				return $tpl->Get();
				break;
				
			default:
				return "<input name=\"{$this->name}\" value=\"{$this->value}\" ".$this->RenderFormParams()." />";
				break;
		}
	}

	function RenderView($param1=null, $param2=null)
	{
	    $rows = unserialize($this->value);
	    if (!Utils::IsArray($rows)) return;
	    
	    $tpl = new Template();
        $tpl->LoadTemplatefile($tpl->GetSystemTemplateFile($this->tplFile));

        foreach ($rows as $row)
        {
            $tpl->SetCurrentBlock('list_view_row');
            $tpl->SetVariable($row);
            $tpl->ParseCurrentBlock();
        }
        
        $tpl->SetCurrentBlock('list_view');
        $tpl->ParseCurrentBlock();
        
        return $tpl->Get();
	}
}
?>