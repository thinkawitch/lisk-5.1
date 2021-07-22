/* lisk 5.1 */

var preSets = [
	['email', "^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$","Error. Incorrect value. Please check field FIELD_NAME."],
	['login', "^([a-zA-Z0-9_]{3,})$", "Error. Field FIELD_NAME contains illegal characters. You can use letters, numbers and '_' only."],
	['number',"^([0-9]+)$", "Error. The field FIELD_NAME must be a number."],
	['empty', "^.+", "Error. The field FIELD_NAME is empty. Please fill it in."]
];

var errorMessages = [
	["No errors found"],
	["Error. The field FIELD_NAME is empty. Please fill it in."],
	["Error. The field FIELD_NAME has to contain at least PARAM characters."],
	["Error. The field FIELD_NAME may contain maximum PARAM characters."],
	["Error. The field FIELD_NAME is entered incorrectly or contains illegal characters."],
	["Error. Please check field FIELD_NAME."],
	["Error. Please check field FIELD_NAME. At least PARAM option(s) should be selected."],
	["Error. Please check field FIELD_NAME. At least 1 option should be selected."],
	["Error. Please check field FIELD_NAME. You cannot select more than PARAM options."], 

];

/*
	// ['first_name','LISK TYPE','First Name','REGEXP','3','6','wrong values','custom error']
	// 0 first_name
	// 1 LISK TYPE
	// 2 label 
	// 3 REGEXP
	// 4 min 5 max
	// 6 wrong values 
	// 7 custom error
	// 8 is empty

*/


function __IsPreSet(name) 
{
	for (var i=0; i<preSets.length; i++) 
	{
		if (preSets[i][0] == name) return true;
	}
	return false;
}

function __GetPreSetErrorMessage(name) 
{
	for (var i=0; i<preSets.length; i++)
	{
		if (preSets[i][0] == name) return preSets[i][2];
	}	
}

function __CheckEmpty(element) 
{
	if (element.value=='') return 1;
	return 0;
}

function __CheckPreset(name,element) 
{
	var regExp;
	for (var i=0; i<preSets.length; i++) 
	{
		if (preSets[i][0] == name) regExp = preSets[i][1];
	}
	if (__CheckRegExp(regExp,element)==false) return 4;

	return 0;
}

function __CheckRegExp(regExp, element) 
{
	if (window.RegExp) 
	{
		var r = new RegExp(regExp);
		if (!r.test(element.value)) return false;
	}
	return true;
}

function __CheckMinMax(element, min, max) 
{
	var s = new String(element.value);
	if (min!='' && min>0 && s.length<min) 
	{
		return 2;
	}
	if (max!='' && max>0 && s.length>max) 
	{
		return 3;
	}	
	return 0;
}

function __CheckTextField(element, regExp, min, max, empty) 
{
	var result = 0;
	if (empty) 
	{
		var isEmpty = __CheckEmpty(element);
		if (isEmpty==1) return 1;
	}
	
	//Min Max check
	result = __CheckMinMax(element, min, max);
	if (result!=0) 
	{
		return result;
	}
	
	if (__IsPreSet(regExp)) 
	{
		result = __CheckPreset(regExp, element);
	} 
	else 
	{
		if (__CheckRegExp(regExp, element, min, max)==false) 
		{
			return 5;
		}
	}
	
	return result;
}

function __CheckDateField(element, regExp, min, max, empty) 
{
	var result = 0;
	if (empty) 
	{
		var isEmpty = false;
		var subs = new Array('_year', '_month', '_day');
		for (var i=0;i<subs.length ;i++ ) 
		{
			var objs = document.getElementsByName(element.name+subs[i]);
			if (objs) 
			{
				isEmpty = __CheckEmpty(objs[0]); 
			}

			if (isEmpty==1) return 1;
		}
	}

	return result;
}


function __CheckDatetimeField(element, regExp, min, max, empty) 
{
	var result = 0;
	if (empty) 
	{
		var isEmpty = false;
		var subs = new Array('_year', '_month', '_day', '_hour', '_minute');
		for (var i=0; i<subs.length; i++ ) 
		{
			var objs = document.getElementsByName(element.name+subs[i]);
			if (objs) 
			{
				isEmpty = __CheckEmpty(objs[0]); 
			}

			if (isEmpty==1) return 1;
		}
	}

	return result;
}

function __CheckBadValues(element, badValues) 
{
	var badValuesArr = badValues.substr(4).split(',');
	if (badValuesArr.length) 
	{
		for (var i=0; i<badValuesArr.length; i++) 
		{
			if (element.value==badValuesArr[i]) 
			{
				return errCode=5;
			}
		}
	}
}

function __CheckFlag(element, regExp, min, max, wrongValues, empty) 
{
	var errCode = 0;
	if (!element.checked) 
	{
		errCode = 5;
	}
	return errCode;
}

function __CheckInput(element, regExp, min, max, wrongValues, empty) 
{
	var errCode = __CheckTextField(element,regExp,min,max,empty);
	return errCode;
}

function __CheckPassword(element, regExp, min, max, wrongValues, empty) 
{
	var errCode = __CheckTextField(element, regExp, min, max, empty);
	return errCode;
}

function __CheckList(element, regExp, min, max, wrongValues, empty) 
{
	if(regExp.substr(0,4)=='not:') 
	{
		var errCode = __CheckBadValues(element, regExp);
	} 
	else
	{
		var errCode = __CheckTextField(element, regExp, min, max, empty);
	}
	return errCode;
}

function __CheckSuggest_list(element, regExp, min, max, wrongValues, empty) 
{
	if(regExp.substr(0,4)=='not:') 
	{
		var errCode = __CheckBadValues(element, regExp);		
	} 
	else 
	{
		var errCode = __CheckTextField(element,regExp,min,max,empty);
	}
	return errCode;
}

function __CheckProp_list(element, regExp, min, max, wrongValues, empty)
{
	return 0;
}

function __CheckCategory(element, regExp, min, max, wrongValues, empty) 
{
	return __CheckList(element, regExp, min, max, wrongValues, empty);
}

function __CheckDate(element, regExp, min, max, wrongValues, empty)
{
	return __CheckDateField(element, regExp, min, max, empty);
}

function __CheckDatetime(element, regExp, min, max, wrongValues, empty) 
{
	return __CheckDatetimeField(element, regExp, min, max, empty);
}

function __CheckProp(element, regExp, min, max, wrongValues, empty) 
{
	var total = 0;
	var errCode = 0;
	if (min || max || empty)
	{
		$('input[name="' + element + '[]"]:checked').each(function(i){												  													  
			total++;
		});
		if (empty&&total<1) errCode = 7;
		if (min&&total<min) errCode = 6;
		if (max&&total>max)	errCode = 8;
	}
	return errCode;			
}

function __CheckRadio(element, regExp, min, max, wrongValues, empty, form) 
{
	var result = 0;
	if (empty) 
	{
		var btnName=element.name;
		var isChecked=false;
		for (var i=0; i<form.elements.length; i++) 
		{
			if (form.elements[i].name==btnName) 
			{
				if (form.elements[i].checked) 
				{
					isChecked = true;
					break;
				} 
			}
		}	
		if (!isChecked) return 1;
	}
}

function __CheckHtml(element, regExp, min, max,wrongValues, empty) 
{
	return __CheckTextField(element, regExp, min, max, empty);
}

function __CheckText(element, regExp, min, max, wrongValues, empty)
{
	return __CheckTextField(element, regExp, min, max, empty);
}

function __CheckWiki(element, regExp, min, max, wrongValues, empty)
{
	return __CheckTextField(element, regExp, min, max, empty);
}

function __CheckListbox(element, regExp, min, max, wrongValues, empty) 
{
	return __CheckTextField(element, regExp, min, max, empty);
}

function __CheckStars(element, regExp, min, max, wrongValues, empty) 
{
	//todo
	return 0;
}

function RenderError(fieldName, errCode, customErrorMessage, param) 
{
	/*
	1 - empty
	2 - less a min value
	3 - max
	4 - preset Error
	5 - custom regexp error
	*/
	var errMsg = errorMessages[errCode];
	
	if (errCode==4) errMsg = __GetPreSetErrorMessage(param);
	if (errCode==5) 
	{ 
		errMsg = customErrorMessage;
		if (errMsg=="") 
		{
			errMsg = errorMessages[errCode];
		}
	}
	s = new String(errMsg);
	s = s.replace(/FIELD_NAME/g,fieldName);
	s = s.replace(/PARAM/g,param);

	ShowAlert(s);
}

function CheckForm(form, required) 
{
	//remember form fields, not to search for them each time
	var hash = new Object();
	for (var i=0; i<form.elements.length; i++) 
	{
		//skip disabled elements
		if (form.elements[i].disabled) continue;
		
		if (!hash[form.elements[i].name]) hash[form.elements[i].name] = i;
	}

	for (var i=0; i<required.length; i++) 
	{
		
		if (!hash.hasOwnProperty(required[i][0])&&!hash.hasOwnProperty(required[i][0]+'[]')) continue;
		var param1 = form.elements[ hash[required[i][0]] ];
		if(required[i][1]=='Flag') 
		{
			if (hash.hasOwnProperty(param1.name+'_checked'))
			{
				param1 = form.elements[ hash[param1.name+'_checked'] ];
			}
		}
		else if (required[i][1] == 'Prop')
		{
			param1 = required[i][0];
		}
		
		var empty = (required[i][8]==1) ? true : false;
		var errCode = eval('__Check' + required[i][1] + '(param1, required[i][3], required[i][4], required[i][5], required[i][6], empty, form);');
		
		if (errCode>0) 
		{
			var param = '';
			// min error
			if (errCode==2||errCode==6) param = required[i][4];
			// max error
			if (errCode==3||errCode==8) param = required[i][5];
			// pre set error
			if (errCode==4) param = required[i][3];
				
			RenderError(required[i][2], errCode, required[i][7], param);
			
			if (required[i][1]!='Date' && required[i][1]!='Datetime')
			{
				if (param1.focus) param1.focus();
			}
			else
			{
				var yearName = param1.name + '_year';
				var yearElem = form.elements[hash[yearName]];
				if (yearElem && yearElem.focus) yearElem.focus();
			}
			
			return false;
		}
		
		// confirmation
		for (var fieldName in hash)
		{
			if (hash.hasOwnProperty(fieldName+'_confirmation'))
			{
				var f1 = form.elements[ hash[fieldName] ];
				var f2 = form.elements[ hash[fieldName+'_confirmation'] ];
				
				if (f1.value!='' && f1.value != f2.value)
				{
					
					//search for field label index in 'required' array
					var idx = -1;
					for (var x=0; x<required.length; x++) 
					{
						if (required[x][0] + '_confirmation' == f2.name) 
						{
							idx = x;
							break;
						}
					}
					if (idx>-1) 
					{
						ShowAlert('Error. Field ' + required[idx][2] + ' confirmation failed.');
						f2.focus();
					}
					else
					{
						//label not found, field is not in required array
						ShowAlert('Error. Field ' + f1.name + ' confirmation failed.');
						f2.focus();
					}
					return false;
				}
			}
		}
		
	}
	return true;
}