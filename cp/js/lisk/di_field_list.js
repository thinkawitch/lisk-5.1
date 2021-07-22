/*
* functions used in di constuctor for list field
* requires selectbox.js
*/

function AddOpt(form) 
{
	var pos;
	name = form.add_value.value;
	if (name=='') return;
	pos = form.list_values.options.length;
	
	if (AutoKey()) id = Math.round((1+Math.random())*1000000);
	else id = form.add_key.value;

	form.list_values.options[pos] = new Option(name,id);
	form.add_value.value = '';
	form.add_key.value = '';

	UpdateRezList(form);
	RenderListBoxValues();
}


function RemOpt(form) 
{
	var del = form.list_values;
	if (del.length<1) return;
	for (var i=0; i<del.length; i++)
	{
		if (del[i].selected)
		{
			del.remove(i);
			i--; //for ff
		}
	}
	UpdateRezList(form);
}


function UpdateRezList(f) 
{
	obj = f.list_values;
	if (obj.options.length<1)
	{
		return false;
	}
	else
	{
		f.rezlist.value = '';
		for (var i=0; i<obj.options.length; i++)
		{
			var key = obj.options[i].value;
			var name = obj.options[i].text;
			
			//remove key, if key is displayed
			var parts = name.split("; key=");
			if (parts.length>1) name = parts[0];

			f.rezlist.value += key + '[*]' + name;
			if (i!=obj.options.length-1) f.rezlist.value += '[|]';
		}
		return true;
	}
}

function ReadRezList() 
{
	var f = jQuery('#idEditListForm').get(0);
	var str = jQuery('#rezlist').val();
	
	if (str=='') return;

	var recs = str.split("[|]");
	for (var i=0; i<recs.length; i++) 
	{
		var rec = recs[i].split("[*]");
		f.list_values.options[i] = new Option(rec[1], rec[0]);
	}
}

function RenderListBoxValues() 
{
	var f = jQuery('#idEditListForm').get(0);
	var obj = f.list_values;
	for (var i=0; i<obj.options.length; i++) 
	{
		if (AutoKey()) 
		{
			var rec = obj.options[i].text.split("; key=");
			obj.options[i].text = rec[0];
		} 
		else 
		{
			var rec = obj.options[i].text.split("; key=");
			obj.options[i].text = rec[0] + "; key=" + obj.options[i].value;
		}
	}
}

function AutoKey() 
{
	return document.getElementById('autokey').checked;
}

function AutoKeyClick() 
{
	if (AutoKey()) jQuery('#add_key_div').hide();
	else jQuery('#add_key_div').show();

	RenderListBoxValues();
}


function MoveListOptionUp()
{
	var f = jQuery('#idEditListForm').get(0);
	var list = f.list_values;

	moveOptionUp(list);

	UpdateRezList(f);
}

function MoveListOptionDown()
{
	var f = jQuery('#idEditListForm').get(0);
	var list = f.list_values;
	
	moveOptionDown(list);

	UpdateRezList(f);
}

function SortListOptions()
{
	var f = jQuery('#idEditListForm').get(0);
	var list = f.list_values;
	
	sortSelect(list);
	
	UpdateRezList(f);
}

jQuery(document).ready(function(){
	
	ReadRezList();

	//prevent form submit by [enter]
	jQuery('#idInputAddValue').keypress(function (e) {
		if (e.which==13) 
		{
			e.preventDefault();
			AddOpt(jQuery('#idEditListForm').get(0));
		}
	});
	jQuery('#idInputAddKey').keypress(function (e) {
		if (e.which==13) 
		{
			e.preventDefault();
			jQuery('#idInputAddValue').focus();
		}
	});
});