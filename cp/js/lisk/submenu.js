
/*
 * confing {type: '', button: '' [, left: 0] [, top: 0] [,near: '']}
 */

function LiskSubmenu(config)
{
	this.config = config;
	
	this.id = config.id;
	this.selector = $('#' + this.id);
	this.buttonSelector = $('#' + this.config.button); 
	
	this.timer = null;
	this.timeout = 500;
	
	this.type = null;
	
	this.Init = function()
	{
		this.type = this.config.type == 2 ? 2 : 1;
		this.BindControls();
	}
	
	this.BindControls = function()
	{
		//button click
		this.buttonSelector.click(function(){
			return false;
		});
		
		//button mouseover
		this.buttonSelector.mouseover(function(objSm){
			return function()
			{
				objSm.Show();
				return false;
			}
		}(this));
		
		//button mouseout
		this.buttonSelector.mouseout(function(objSm){
			return function()
			{
				objSm.HideDelayed();
			}
		}(this));
		
		//mouseover
		this.selector.mouseover(function(objSm){
			return function()
			{
				objSm.Show();
			}
		}(this));
		
		//mouseout
		this.selector.mouseout(function(objSm){
			return function()
			{
				objSm.HideDelayed();
			}
		}(this));
	}
	
	this.Show = function()
	{		
		clearTimeout(this.timer);
		
		this.SetPosition();
		
		this.selector.show();
	}
	
	this.Hide = function()
	{
		clearTimeout(this.timer);
		this.selector.hide();
	}
	
	this.HideDelayed = function()
	{
		this.timer = setTimeout(function(objSm){
			return function()
			{
				objSm.Hide();
			}
		}(this), this.timeout);
	}
	
	this.SetPosition = function()
	{
		if (this.type == 1)
		{
			// show submenu near this element
			var offset = $(this.config.near).offset();
			var height = $(this.config.near).height();
			
			this.selector.css('top', offset.top + height + 'px');
			this.selector.css('left', offset.left + 'px');
		}
		else if (this.type == 2)
		{
			// show submenu as defined in config
			this.selector.css('top', this.config.top + 'px');
			this.selector.css('left', this.config.left + 'px');
		}
	}
}
