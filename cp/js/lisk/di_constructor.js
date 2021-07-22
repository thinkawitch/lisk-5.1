
// file
FilePathChecker = function(path)
{
	this.path = path;
	
	this.Check = function()
	{
		//use enclosure to protect self object
		
		(function(checkerObj){
			
			$.ajax({
				type: 'GET',
				url: 'ajax.php',
				data: {handler: 'FileExists', param1: checkerObj.path},
				dataType: 'json',
				timeout: 5000,
				success: function(data)
				{
					if (typeof data == "object" && data.result == "available")
					{
						checkerObj.ShowPathAvailable();
					}
					else
					{
						checkerObj.ShowPathExists();
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown)
				{
					checkerObj.ShowIncorrectResponse();
				}
			});
			
		})(this);
		
	}
	
	this.ShowPathAvailable = function()
	{
		ShowAlert("This folder is available", "notify");
	}
	
	this.ShowPathExists = function()
	{
		ShowAlert("Not available. A folder with such name already exists. Please choose another name.");
	}
	
	this.ShowIncorrectResponse = function()
	{
		alert("General error. Please try again later.");
	}
}

// image
function SwitchThumbnails(value) 
{
    switch (value) 
    {
        case '0':
            $('#image_thumbnails').hide();
            break;

        case '1':
            $('#image_thumbnails').show();
            break;
    }
}

//listbox
function CheckListFields(f) 
{
    if (f.list2.options.length<1) 
    {
        ShowAlert('Error. List fields should contain at least one record.');
        return false;
    }
    else
    {
        f.rezlist.value = '';
        for (var i=0; i<f.list2.options.length; i++) 
        {
            f.rezlist.value += f.list2.options[i].value;
            if (i!=f.list2.options.length-1) f.rezlist.value += ',';
        }
        return true;
    }
}


//link interface elements, after document is ready
jQuery(document).ready(function(){
	
	//file path checker
	$('#idBtnCheckPath').click(function(){

		var path = $('#idFieldPath').val();
		var fpc = new FilePathChecker(path);
		fpc.Check();
	})
	
	//toggle thumbnails block
	$('#idSelectThumbnails').change(function(){
		var value = $(this).val();
		SwitchThumbnails(value);
	})
	
});