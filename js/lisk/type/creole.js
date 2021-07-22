
function LiskCreole(name, controlId)
{
	this.name = name;
	this.controlIdx = controlId;
	this.selector = $('#idBlock' + controlId);
	
	this.panelHelp = this.selector.find('div.creole-help-panel');
	this.panelPreview = this.selector.find('.creole-preview-panel');
	
	this.Init = function()
	{
		//bind help
		this.selector.find('a.creole-help').click(function(objCr){
			return function()
			{
				objCr.ToggleHelp();
				return false;
			}
		}(this));
		
		//bind ask preview
		this.selector.find('a.creole-preview').click(function(objCr){
			return function()
			{
				objCr.AskPreview();
				return false;
			}
		}(this));
		
		//bind close preview
		this.selector.find('a.creole-close-preview').click(function(objCr){
			return function()
			{
				objCr.HidePreview();
				return false;
			}
		}(this));
	}
	
	this.ToggleHelp = function()
	{
		this.panelHelp.toggle();
	}
	
	this.AskPreview = function()
	{
		var text = this.selector.find('[name="' + this.name + '"]').val();
		
		$.ajax({
			cache: false,
			url:   liskBaseUri + 'creole-preview/',
			type:  'POST',
			data:  {text: text},
			dataType: 'text',
			timeout:  5000,
			error: function(){
				alert('Lost connection, please try again!');
			},
			success: function(objCr){
				return function(text)
				{
					objCr.ShowPreview(text);
				}
			}(this)
		});
	}
	
	this.ShowPreview = function(text)
	{
		this.panelPreview.find('.container').html(text);
		this.panelPreview.show();
	}
	
	this.HidePreview = function()
	{
		this.panelPreview.hide();
	}
}