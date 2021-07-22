
function LiskTypeImage(name)
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
		this.selector.find('input[value="upload"]').click(function(objTF){
			return function()
			{
				objTF.ShowPanelHttp();
			}
		}(this));
		
		//my_pictures file
		this.selector.find('input[value="my_pictures"]').click(function(objTF){
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
		
		//thumbnails: auto
		var tc = this.selector.find('[rel="thumbnails_creation"]');
		tc.find('input[value="auto"]').click(function(objTF){
			return function()
			{
				objTF.HidePanelThumbnails();
			}
		}(this));
		
		//thumbnails: custom
		tc.find('input[value="custom"]').click(function(objTF){
			return function()
			{
				objTF.ShowPanelThumbnails();
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
		
		//existing thumbs: show
		this.selector.find('a[rel="existing-thumbs-show"]').click(function(objTF){
			return function()
			{
				objTF.ShowPanelExistingThumbs();
				return false;
			}
		}(this));
		
		
		//existing thumbs: hide
		this.selector.find('a[rel="existing-thumbs-hide"]').click(function(objTF){
			return function()
			{
				objTF.HidePanelExistingThumbs();
				return false;
			}
		}(this));
		
		//choose action
		this.selector.find('input[name="' + this.name + '_edit_action"]').change(function(objTF){
			return function()
			{
				var action = $(this).val();
				switch (action)
				{
					case 'none':
						objTF.EditActionEmpty();
						break;
						
					case 'change':
						objTF.EditActionChange();
						break;
					
					case 'delete':
						objTF.EditActionEmpty();
						break;
						
					case 'recreate':
						objTF.EditActionEmpty();
						break;
				}
			}
		}(this));
		
	}
	
	this.ResetControlsDefault = function()
	{
		this.selector.find('input[value="upload"]')[0].checked = true;
		this.selector.find('[rel="thumbnails_creation"] input[value="auto"]')[0].checked = true;
		this.selector.find('[rel="resizing_on"]')[0].checked = true;
	}
	
	this.ResetControlsUpload = function()
	{
		
	}
	
	this.ResetControlsMyPictures = function()
	{
		this.selector.find('[rel="thumbnails_creation"] input[value="auto"]')[0].checked = true;
	}
	
	this.ShowPanelHttp = function()
	{
		this.HidePanelFtp();
		this.ShowPanelThumbnailsCreation();
		this.HidePanelThumbnails();
		
		this.ResetControlsUpload();
		
		this.selector.find('[rel="panel_http"]').show();
	}
	
	this.HidePanelHttp = function()
	{
		this.selector.find('[rel="panel_http"]').hide();
	}
	
	this.ShowPanelFtp = function()
	{
		this.HidePanelHttp();
		this.HidePanelThumbnailsCreation();
		this.HidePanelThumbnails();
		
		this.ResetControlsMyPictures();
		
		this.selector.find('[rel="panel_ftp"]').show();
	}
	
	this.HidePanelFtp = function()
	{
		this.selector.find('[rel="panel_ftp"]').hide();
	}
	
	this.ShowPanelThumbnailsCreation = function()
	{
		this.selector.find('[rel="thumbnails_creation"]').show();
	}
	
	this.HidePanelThumbnailsCreation = function()
	{
		this.selector.find('[rel="thumbnails_creation"]').hide();
	}
	
	this.ShowPanelThumbnails = function()
	{
		this.selector.find('[rel="thumbnails"]').show();
	}
	
	this.HidePanelThumbnails = function()
	{
		this.selector.find('[rel="thumbnails"]').hide();
	}
	
	this.OpenFtpDialog = function()
	{
		var elementBack = 'ftp_' + this.name;
		var url = 'file_chooser.php?action=browse&dir=Image&elem_back=' + elementBack;
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
	
	this.ShowPanelExistingThumbs = function()
	{
		this.selector.find('[rel="thumbnails_view"]').hide();
		this.selector.find('[rel="thumbnails_hide"]').show();
		
		this.selector.find('[rel="thumbnails_info"]').show();
	}
	
	this.HidePanelExistingThumbs = function()
	{
		this.selector.find('[rel="thumbnails_view"]').show();
		this.selector.find('[rel="thumbnails_hide"]').hide();
		
		this.selector.find('[rel="thumbnails_info"]').hide();
	}
	
	this.EditActionEmpty = function()
	{
		this.HidePanelHttp();
		this.HidePanelThumbnailsCreation();
		this.HidePanelThumbnails();
		this.HidePanelFtp();
		
		this.ResetControlsDefault();
		
		this.selector.find('[rel="change_picture"]').hide();
	}
	
	this.EditActionChange = function()
	{
		this.EditActionEmpty();
		
		this.ShowPanelHttp();
		this.ShowPanelThumbnailsCreation();
		
		this.selector.find('[rel="change_picture"]').show();
	}
	
}