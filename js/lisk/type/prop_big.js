
function LiskPropBig(name, allowMultiples)
{
    this.name = name;
	this.selector = $('#idProp' + name);
    this.selectorAll = this.selector.find('select[name="' + name + '_all"]');
    
    this.allowMultiples = allowMultiples ? true : false;
    
    this.allowDuplicates = false;
    this.selBlockDups = this.selector.find('[rel="block-dups"]');

    this.Init = function()
    {
        //bind add button
		this.selector.find('[rel="add"]').click(function(objLPB)
        {
			return function()
			{
				objLPB.AddLine();
				return false;
			}
		}(this));
		
		
		//bind add-all button
		this.selector.find('[rel="add-all"]').click(function(objLPB)
        {
			return function()
			{
				objLPB.AddAllLines();
				return false;
			}
		}(this));
		
		//bind remove-all button
		this.selector.find('[rel="remove-all"]').click(function(objLPB)
        {
			return function()
			{
				objLPB.RemoveAllLines();
				return false;
			}
		}(this));
		
		if (this.allowMultiples)
		{
			this.selBlockDups.show();
			
			//bind duplicates checkbox
			this.selBlockDups.find('[rel="allow-dups"]').click(function(objLPB)
	        {
				return function()
				{
					objLPB.ToggleAllowDuplicates();
				}
			}(this));
		}

    }

    this.AddLine = function()
	{
        var val = this.selectorAll.val();
        var caption = this.selectorAll.find('option[value="'+ val +'"]').text();
        
        if (val.length == '') return;

        //check if this val is already added
        var idx = 'idVal' + this.name + val;
        
        if (!this.allowDuplicates)
        {
        	if (this.selector.find('[rel="'+ idx +'"]').length) return;
        }

        var line = '\
        <tr rel="idVal{name}{val}"> \
		    <td><input type="checkbox" name="{name}[]" value="{val}" id="idVal{name}{val}" checked="checked" /></td> \
		    <td><label for="idVal{name}{val}">{caption}</label></td> \
	    </tr>';

        line = $.nano(line, {val: val, caption: caption, name: this.name});
        
		this.selector.find('table[rel="list"] tbody').append(line);
		
	}
    
    this.AddAllLines = function()
    {
    	this.selectorAll.find('option').each(function(objLPB){
    		return function()
    		{
    			var cur = $(this).val();
    			objLPB.selectorAll.val(cur);
    			objLPB.AddLine();
    		}
    		
    	}(this));
    	
    	this.selectorAll.val('');
    }
    
    this.RemoveAllLines = function()
    {
    	this.selector.find('table[rel="list"] tbody').html('');
    }
    
    this.ToggleAllowDuplicates = function()
    {
    	this.allowDuplicates = this.selBlockDups.find('[rel="allow-dups"]').is(':checked');
    }
}