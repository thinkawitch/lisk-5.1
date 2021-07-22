
function LiskTypeFile(name)
{
	this.name = name;
	this.selector = $('#idField' + this.name);
	
	this.Init = function()
	{
		this.BindControls();
	}
	
	this.BindControls = function()
	{
		//http file
		this.selector.find('input[value="http"]').click(function(objTF){
			return function()
			{
				objTF.ShowPanelHttp();
			}
		}(this));
		
		//ftp file
		this.selector.find('input[value="ftp"]').click(function(objTF){
			return function()
			{
				objTF.ShowPanelFtp();
			}
		}(this));
		
		//ftp select file dialog
		this.selector.find('[rel="ftp_dialog"]').click(function(objTF){
			return function()
			{
				objTF.OpenFtpDialog();
			}
		}(this));
		
		//update actions: none, delete
		this.selector.find('input[value="none"], input[value="delete"]').click(function(objTF){
			return function()
			{
				objTF.HidePanelChange();
			}
		}(this));
		
		//update action: update
		this.selector.find('input[value="change"]').click(function(objTF){
			return function()
			{
				objTF.ShowPanelChange();
			}
		}(this));
		
	}
	
	this.ShowPanelHttp = function()
	{
		this.HidePanelFtp();
		this.selector.find('[rel="panel_http"]').show();
	}
	
	this.HidePanelHttp = function()
	{
		this.selector.find('[rel="panel_http"]').hide();
	}
	
	this.ShowPanelFtp = function()
	{
		this.HidePanelHttp();
		this.selector.find('[rel="panel_ftp"]').show();
	}
	
	this.HidePanelFtp = function()
	{
		this.selector.find('[rel="panel_ftp"]').hide();
	}
	
	this.OpenFtpDialog = function()
	{
		var elementBack = this.name + '_upload_ftp';
		var url = 'file_chooser.php?action=browse&dir=&elem_back=' + elementBack;
		popupWindow(url, 400, 500, true);
	}
	
	this.ShowPanelChange = function()
	{
		this.selector.find('[rel="panel_change_file"]').show();
	}
	
	this.HidePanelChange = function()
	{
		this.selector.find('[rel="panel_change_file"]').hide();
	}
}