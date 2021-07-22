/* must be in utf-8 charset */

// bind common handlers
$(document).ready(function(){
	
	// cmsList sort hovers
	$('[liskSortField]').each(function(){
		
		var field = $(this).attr('liskSortField');
		var image1 = $(this).attr('liskSortImage1');
		var image2 = $(this).attr('liskSortImage2');
		var url = $(this).attr('liskSortUrl');
		
		//hover
		$(this).hover(function(fieldName, imgSrc){
			return function()
			{
				$('img[name="' + fieldName + '"]').attr('src', imgSrc);
			}
		}(field, image1));
		
		//mouseout
		$(this).mouseout(function(fieldName, imgSrc){
			return function()
			{
				$('img[name="' + fieldName + '"]').attr('src', imgSrc);
			}
		}(field, image2));
		
		//click
		$(this).click(function(redirectUrl){
			return function()
			{
				window.location.href = redirectUrl;
				return false;
			}
		}(url));
	});
	
});

function ConvertRusToTranslit(str)
{
	str = ('' + str).toLowerCase();
	
	var cyr = new Array(
		"а", "б", "в", "г", "д", "е", "ё", "ж", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", 
		"у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы", "ь", "э", "ю", "я"
	);

	var lat = new Array(
		"a", "b", "v", "g", "d", "e", "jo","zh","z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", 
		"u", "f", "kh","c", "ch","sh","sch","", "y", "",  "e", "u", "ia"
	);

	for (var i=0; i<cyr.length; i++)
	{
		str = str.replace(new RegExp(cyr[i], 'ig'), lat[i]);
	}

	return str;
}

function FormatToSiteUrl(string, isCategory) 
{
	var value = new String(string);

	value = value.replace(/'/g, "");
	value = value.replace(/"/g, "");

	//make latin, if possible
	value = ConvertRusToTranslit(value);
	
	//remove danger chars
	value = $.trim(value);
	value = value.replace(/[^0-9A-za-z_\/\-.]/g, "_");

	if (isCategory) 
	{
		var len = value.length;
		if (len>0 && value.substr(len-1, 1) != '/')
		{
			value = value + '/';
		}
	};

	return value.toLowerCase();
}

// Show/hide panel
function PanelToggle(panelId) 
{
	var button = $('#' + panelId + '_button')[0];
	var panel = $('#' + panelId);

	if (panel.is(':hidden')) 
	{
		button.src = "img/cms/hide.gif";
		panel.show();
	} 
	else 
	{
		button.src = "img/cms/show.gif";
		panel.hide();
	}
}

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

function liskZeroPadd(number, length) 
{
	if (!length) length = 2;
	
	var zeros = '';
	for (var i=0; i<length; i++)
	{
		zeros += '0';
	}
	
	var str = '' + zeros + number;
	return str.substring(str.length - length)
};

function liskParseInt(str)
{
	var number = parseInt(str);
	if (isNaN(number)) number = -1;
	return number;
}