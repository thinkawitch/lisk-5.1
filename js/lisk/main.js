
/* Nano Templates (Tomasz Mazur, Jacek Becela) */
(function($){
  $.nano = function(template, data) {
    return template.replace(/\{([\w\.]*)\}/g, function (str, key) {
      var keys = key.split("."), value = data[keys.shift()];
      $.each(keys, function () { value = value[this]; });
      return (value === null || value === undefined) ? "" : value;
    });
  };
})(jQuery);

/**
 * append system blocks to the page
 */
$(document).ready(function(){

	//container for confrim dialog
	var comfirmTpl = '\
		<div id="idDialogConfirm" style="display:none;" title="Confirm"> \
		</div>';
	$("body").append(comfirmTpl);
	
	//container for errors
	var errorTpl = '\
		<div id="idDialogError" style="display:none;" title="Warning"> \
		</div>';	
	$("body").append(errorTpl);

	//container for notifications
	var notifyTpl = '\
		<div id="idDialogNotification" style="display:none;" title="Notification"> \
		</div>';	
	$("body").append(notifyTpl);
	
	//zoom, ie6 throws exception
	try
	{
		$("a[liskZoom=true]").fancybox({hideOnContentClick:true, overlayColor: '#000', overlayOpacity: 0.5, titlePosition: 'inside'});
	}
	catch (ex) {  }

});

/**
 * Show modal confirm box, if user clicks "yes" run the callback 
 * 
 * @param string message
 * @param callback
 * @return false
 */
function ShowConfirm(message, callback) 
{
	var sel = $('#idDialogConfirm');
	
	//remove old, or other
	sel.dialog('destroy');
	
	//add message
	var html = '<p><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 7px 0;"></span> ' + message +  '</p>';
	sel.html(html);
	
	//show dialog
	sel.dialog(
	{
		//dialogClass: 'ui-state-highlight',
		modal: true, 
		resizable: false, 
		height: 'auto', 
		minHeight: 50, 
		position: 'center',
		buttons: 
		{
			'Yes': function(paramCallback) 
			{
				return function()
				{
					$(this).dialog('close');
					
					//check callback
					if (eval("(typeof(paramCallback) != 'function') && (typeof(paramCallback) != 'object')")) return false;
					
					//link
					if (callback.href)
					{
						location.href = paramCallback.href;
						return;
					}
					
					//function
					if ($.isFunction(paramCallback)) 
					{
						paramCallback.apply();
						return
					}
					
					//form
					//todo
					
				}
			}(callback),
			
			'No': function() 
			{
				$(this).dialog('close');
			}
		},
		open: function (event, ui)
		{
			//! removes focus form all dialogs' buttons
	        $('.ui-dialog :button').blur();
	    }
	});
	
	return false;
}

/**
 * Show alert modal alert box,
 * set type to render as error or notification
 * 
 * @param string message
 * @param string type
 * @return
 */
function ShowAlert(message, type)
{
	if (!type) type = 'error';
	
	if (type == 'error') ShowErrorDialog(message);
	else ShowNotificationDialog(message);
}

function ShowErrorDialog(message)
{
	var sel = $('#idDialogError');
	
	//remove old, or other
	sel.dialog('destroy');
	
	//render error messages
	var lineTpl = '<p><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 7px 0;"></span> {message} </p>';
	var htmlLines = '';
	
	if (message.push)
	{
		//array of messages
		for (var i=0; i<message.length; i++)
		{
			htmlLines += $.nano(lineTpl, {message: message[i]})
		}
	}
	else
	{
		//single message
		htmlLines += $.nano(lineTpl, {message: message})
	}
	
	sel.html(htmlLines);
	
	//show dialog
	sel.dialog({
		dialogClass: 'ui-state-error',
		modal: true, 
		resizable: false, 
		height: 'auto', 
		minHeight: 50, 
		position: ['center', 200]
	});
}

function ShowNotificationDialog(message)
{
	var sel = $('#idDialogNotification');
	
	//remove old, or other
	sel.dialog('destroy');
	
	//render error messages
	var lineTpl = '<p class=""><span class="ui-icon ui-icon-info" style="float:left; margin:0 7px 7px 0;"></span> {message} </p>';
	var htmlLines = '';
	
	if (message.push)
	{
		//array of messages
		for (var i=0; i<message.length; i++)
		{
			htmlLines += $.nano(lineTpl, {message: message[i]})
		}
	}
	else
	{
		//single message
		htmlLines += $.nano(lineTpl, {message: message})
	}
	
	sel.html(htmlLines);
	
	
	//show dialog
	sel.dialog({
		//dialogClass: 'ui-state-highlight',
		modal: true, 
		resizable: false, 
		height: 'auto', 
		minHeight: 50, 
		position: ['center', 200]
	});
}

/**
 * Open popup window
 * 
 * @param url
 * @param width
 * @param height
 * @param scroll
 * @return
 */
function popupWindow(url, width, height, scroll) 
{
	var popUpWin = 0; //modify this to open new popup in the same window
	//if (window.popUpWin && !popUpWin.closed) popUpWin.close();

	var left = (screen.width/2) - width/2;
  	var top = (screen.height/2) - height/2;
  	var scrolling = (scroll) ? 'yes' : 'no';

	popUpWin = open(url, 'popUpWinName', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=' + scrolling + ',resizable=no,copyhistory=yes,width=' + width + ',height=' + height + ',left=' + left + ', top=' + top + ',screenX=' + left + ',screenY=' + top);
	popUpWin.focus();
}

/**
 * Alert object's propertis,
 * set showValues to show properties' values
 * 
 * @param obj
 * @param showValues
 * @return
 */
function alertObj(obj, showValues)
{
	showValues = (showValues) ? true : false;
	var buf = '';
	for (var prop in obj)
	{
		if (showValues) buf += ' ' + prop + '=' + obj[prop] + ', ';
		else buf += ' ' + prop + ' ';
	}
	alert(buf);
}