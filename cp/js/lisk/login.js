if (top != self) top.location = self.location;

function parseError(id, info) 
{
    $('#'+id).html(info);
    $('#'+id+'_error').show();
}

function parseInfo(id, info) 
{
    $('#'+id).html(info);
    $('#'+id+'_error').hide();
    $('#'+id+'_img').attr('src', 'img/login/v.gif').css({width:13,height:9});
}
    

$(document).ready(function(){
    
    var isBrowserMatch = false;
    var allowed = new Array( 
        {name:'firefox', ver: 3.0}, 
        {name:'msie', ver: 7},
        {name:'opera', ver: 9.5},
        {name:'safari', ver: 4},
		{name:'chrome', ver: 9}
    );

    var browser = jQuery.browser;

    for (var i=0; i<allowed.length; i++)
    {
        var item = allowed[i];
        if (browser.name==item.name && browser.versionNumber>=item.ver)
        {
            isBrowserMatch = true;
        }    
    }
                
    // browser
    var name = browser.name;
    if (name=="msie") name = 'internet explorer';

    if (isBrowserMatch) parseInfo('browser', name + ' ' + browser.version);
    else parseError('browser', browser.name + ' ' + browser.version);

    // screen info
    if (screen.width>=1024 && screen.height>=768 || screen.width>=768 && screen.height>=1024) parseInfo('resolution', screen.width + 'x' + screen.height);
    else parseError('resolution',screen.width + 'x' + screen.height);
    
    //script
    parseInfo('script', 'enabled');
    
    //cookies
    if (navigator.cookieEnabled) parseInfo('cookies', 'enabled');
    else parseError('cookies', 'disabled');

});