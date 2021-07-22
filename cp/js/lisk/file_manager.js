function fmMakeUpload(type)
{
    var allTabs = new Array('dir', 'file');

    for (var i=0; i<allTabs.length; i++)
    {
        var objId = 'upload_'+allTabs[i] + '_tab';

        if (allTabs[i]==type) $('#'+objId).toggle();
        else $('#'+objId).hide();
    }
}

function fmDeleteDir(DirPath, type)
{
    ShowConfirm(DEL_DIR, function(){ location.href = "file_manager.php?action=deldir&dir=" + DirPath + "&type=" + type });
}

function fmDeleteFile(FilePath, type)
{
    ShowConfirm(DEL_FILE, function(){ location.href = "file_manager.php?action=delfile&file=" + FilePath + "&type=" + type });
}

function fmSelectFileExt(ico_big, width, height, size, last_modified, ico, name) 
{
    $('#view_tab').show();
    $('#bottom_prop').show();

    $('#divImg').html("<img id='idImg' src='img/file_manager/" + ico_big + "' border='6' style='border : 4px solid white;'>");

    if (width=='') 
    {
        $('#width_span').hide();
        $('#height_span').hide();
    }
    else 
    {
        $('#width_span').show();
        $('#height_span').show();
    }

    $('#divImg').show();

    $('#f_title').html("<table border=0 cellpadding=0 cellspacing=0><tr><td><img src='img/file_manager/" + ico + "'> </td><td>&nbsp;&nbsp;&nbsp;</td><td><b>"+name+"</b></td></tr></table>");

    if (width != '') $('#f_width').html(width + 'px');
    else $('#f_width').html('n/a');

    if (height != '') $('#f_height').html(height + 'px');
    else $('#f_height').html('n/a');

    $('#f_size').html(size);

    $('#f_lastm').html(last_modified);
    
    var filesPath = location.href.split('/');
    filesPath.pop();filesPath.pop();
    filesPath = filesPath.join('/');
    fileName = filesPath+'/files/_system/'+folder+'/'+name;
    
    $('#f_download').html('<a id="download" target="_blank" href="'+fileName+'">Download</a>');
	$('#link').val($('#download').attr('href'));
    $('#select').click(function(){$('#link').get(0).select()});
}



function fmShowFile(sURL, width, height) 
{
    var isFlash = sURL.indexOf('.swf')>-1 ? true : false;

    if (height != '' && !isFlash) 
    {
        var id = 'id' + parseInt(Math.random()*1000);
        var href = sURL;

        $('body').append('<a href="' + href + '" id="' + id + '"> </a>');
        $('#'+id).fancybox();
        $('#'+id).click();
        $('#'+id).remove();
    } 
    else 
    {
        wnd = window.open(sURL, '', 'scrollbars=1,status=0');
        wnd.focus();
    }
}


function fmResizeImage(width1, height1, width2, height2, onlyMinimize)
{
    
    if (onlyMinimize == undefined) onlyMinimize = true;
    else onlyMinimize = (onlyMinimize) ? true : false;

    var k1 = width1 / height1;
    var k2 = width2 / height2;

    var q = k1 / k2;

    var width = width1;
    var height = height1;

    if (onlyMinimize && width1<width2 && height1<height2)
    {
        return {width: parseInt(width), height: parseInt(height)};
    }

    if (k1 >= 1) 
    {
        if (q >= 1) 
        {
            width = width2;
            height = width / k1;
        } 
        else 
        {
            width = width2 * q;
            height = height2;    
        }
    } 
    else 
    {
        if (q >= 1) 
        {
            height = height2 / q;
            width = height * k1;
        } 
        else 
        {
            height = height2;
            width = height * k1;
        }
    }

    return {width: parseInt(width), height: parseInt(height)};
}

function fmSelectFile(sURL, width, height, type, size, last_modified, name, ico) 
{
    $('#view_tab').show();
    $('#bottom_prop').show();

    if (width != '') $('#f_width').html(width + 'px');
    else $('#f_width').html('n/a');

    if (width != '') $('#f_height').html(height + 'px');
    else $('#f_height').html('n/a');


    $('#f_size').html(size);
    $('#f_lastm').html(last_modified);
    $('#f_title').html("<table border=0 cellpadding=0 cellspacing=0><tr><td><img src='img/file_manager/" + ico + "'> </td><td>&nbsp;&nbsp;&nbsp;</td><td><b> " + name + "</b></td></tr></table>");

    if (type == 'image') 
    {
        var resized = fmResizeImage(width, height, 280, 270);
        
        $('#divImg').html("<div style='border : 12px solid white;' id='idDivImg'><img id='idImg' src='" + sURL + "' border=3 style='border : 4px solid Silver;' width='" + resized.width + "' height='" + resized.height + "'></div><table><tr><td><img src='img/file_manager/ico_enlarge.gif' style='vertical-align:middle'></td><td><a href='" + sURL + "' liskZoom='true' liskHint=\""+VIEW_INPOPUP+"\">"+ENLARGE_PIC+"</a></td></tr></table>");

        $("a[liskZoom=true]").fancybox();

        $('#divImg').show();

    }
    else if (type == 'flash') 
    {
        var resized = fmResizeImage(width, height, 280, 270);

        $('#divImg').html("<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0' width='" + resized.width + "' height='" + resized.height + "'><param name='movie' value='" + sURL + "'><param name='quality' value='high'><param name='wmode' value='transparent'><embed src='" + sURL + "' quality='high' wmode='transparent' pluginspage='http://www.macromedia.com/go/getflashplayer' type='application/x-shockwave-flash' width='" + resized.width + "' height='" + resized.height + "'></embed></object><table><tr><td><img src='img/file_manager/ico_enlarge.gif'></td><td><a href='javascript: fmShowFile(\"" + sURL + "\", " + width + ", " + height + ")' liskHint=\"" + VIEWMOVIE_INPOPUP + "\">"+ENLARGE_MOVIE+"</a></td></tr></table>");

        $('#divImg').show();

    } 
    else if (type == 'media') 
    {
        var resizedWidth = 196;
        var resizedHeight = 196;
        var inner = "<div style='border : 7px solid white;'><EMBED src='" + sURL +  "' width=" + resizedWidth + " height=" + resizedHeight+" autostart=true controls=smallconsole></EMBED>";

        $('#divImg').html(inner);
        $('#divImg').show();
    }
    
    
    $('#f_download').html('<a id="download" target="_blank" href="'+sURL+'">Download</a>');
	$('#link').val('http://'+location.host+$('#download').attr('href'));
    $('#select').click(function(){$('#link').get(0).select()});
    
}


function fmCheckNewDir() 
{
    var obj = $('#idDirName').get(0);
    if (obj) 
    {
        obj.value = $.trim(obj.value);
        if (obj.value=='') 
        {
            ShowAlert(ENTER_FNAME);
            obj.focus();
            return false;
        }
    } 
    else return false;
}