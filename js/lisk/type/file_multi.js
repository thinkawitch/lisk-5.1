

function LiskFileMulti(name)
{
	this.name = name;
	this.selector = $('#idBlock' + name);
	this.limit = 4;
	this.controlIdx = 0;
	
	this.Init = function()
	{
		//bind add button
		this.selector.find('[rel="attach-more"]').click(function(objLFM){
			return function()
			{
				objLFM.AddLine();
				return false;
			}
		}(this));
	}
	
	this.IsLimitReached = function()
	{
		var len = this.selector.find('tbody tr').length;
		return len >= this.limit;
	}
	
	this.AddLine = function()
	{
		if (this.IsLimitReached())
		{
			alert('You can attach no more than ' + (this.limit + 1) + ' files!');
			return false;
		}
		
		this.controlIdx ++;
		
		//add control
		var line = '<tr rel="idx' + this.controlIdx + '"> \
		    <td><img src="' + liskBaseUri +'img/clip.gif" width="25" height="26" /></td> \
		    <td><input type="file" name="' + this.name + '[]"  /> <a href="#remove" rel="remove">remove</a></td> \
		  </tr>';
		this.selector.find('tbody').append(line);
		
		//bind remove
		this.selector.find('tr[rel="idx'+ this.controlIdx +'"] a').click(function(objLFM, idx){
			return function()
			{
				objLFM.RemoveLine(idx);
				return false;
			}
		}(this, this.controlIdx));
	}
	
	this.RemoveLine = function(idx)
	{
		this.selector.find('tr[rel="idx'+ idx +'"]').remove();
	}
}