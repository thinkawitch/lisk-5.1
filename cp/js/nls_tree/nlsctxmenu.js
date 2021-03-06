/*AddObject.com
*You are not allowed to download and use this script for any type of application unless you have obtain a license
*Copyright www.addobject.com 2005
*/
nlsctxmenu=new Object();

function NlsCtxMenuItem(key,capt,url,ico,enb){this.key=key;this.intKey="";this.capt=capt;this.url=(!url||url==""?"javascript:void(0);":url);this.ico=ico;this.enable=(enb==null?true:enb);return this}
function NlsCtxMenu(mId){this.lsItm=null;this.genMenu=genMenu;this.mId=mId;this.items=new Object();this.container=null;this.count=0;this.stlprf="";this.active=false;this.absWidth=0;this.add=addItem;this.addSeparator=addSeparator;this.enableItem=enableItem;this.itemClick=itemClick;this.showMenu=showMenu;this.hideMenu=hideMenu;this.menuOnClick=menuOnClick;this.menuOnShow=menuOnShow;nlsctxmenu[mId]=this;return this}
function itemClick(itemId) {
	if(!this.items[itemId].enable)return;
	this.hideMenu();
	var ids=itemId.split("_");
	return this.menuOnClick(this.container.selNd,ids[0],ids[1]);
}

function genMenu(){
	var smenu="";
	for(it in this.items){
		if(this.items[it].capt=="-"){
			smenu+="<tr><td class=\""+this.stlprf+"ctxsidebar\" align=\"center\" style=\"font-size:3px\">&nbsp;</td><td style=\"height:7px;vertical-align:center;padding-top:3px\"><div class=\"ctxseparator\">&nbsp;</div></td></tr>"
		} else { 
			smenu+="<tr id=\""+it+"\" onmouseover=\"ctxItemOver('"+it+"')\" onclick=\"return nlsctxmenu."+this.mId+".itemClick('"+it+"');\">"+"<td class=\""+this.stlprf+"ctxsidebar\" align=\"center\" nowrap>"+(this.items[it].ico?"<img src='"+this.items[it].ico+"' valign=middle>":"&nbsp;")+"</td>"+"<td class=\""+this.stlprf+"ctxitem\" nowrap><a class=\""+this.stlprf+"ctxtext"+(this.items[it].enable?"":"disable")+"\" href=\""+this.items[it].url+"\">"+this.items[it].capt+"</a></td></tr>"
		}
	}
	
	smenu="<table border=0 cellpadding=0 cellspacing=0 "+(this.absWidth==0?"":"width='"+this.absWidth+"'")+">"+smenu+"</table>";smenu="<div id='"+this.mId+"' class='"+this.stlprf+"ctxmenu' style='display:none'>"+smenu+"</div>";
	var isIE=(navigator.userAgent.indexOf("MSIE")>=0);
	var orgEvent=(isIE?document.body.onclick:window.onclick);
	if(!orgEvent||orgEvent.toString().search(/orgEvent/gi)<0){
		var newEvent=function(){
			if(orgEvent)orgEvent();
			hideAllMenu();
		};
		
		if(isIE)document.body.onclick=newEvent;
		else window.onclick=newEvent
	}
	
	return smenu
}
function addItem(key,capt,url,ico,enb){var intKey=this.mId+"_"+key;var it=new NlsCtxMenuItem(key,capt,url,ico,enb);it.intKey=intKey;this.items[intKey]=it;this.count++}
function addSeparator(){this.add("auto"+this.count,"-","","");}


function showMenu(x,y){
	hideAllMenu();
	if(this.lsItm!=null){
		setStyle(this.lsItm,"N");
		this.lsItm=null
	}

var flag=this.menuOnShow(this.container.selNd);
if(flag==false)return;
var ctx=NlsGetElementById(this.mId);
ctx.style.left=-500+"px";
ctx.style.visibility="hidden";
ctx.style.display="";
var scrOffX=window.scrollX?window.scrollX:document.body.scrollLeft;
var scrOffY=window.scrollY?window.scrollY:document.body.scrollTop;
var cW=(window.innerWidth?window.innerWidth:document.body.clientWidth);
var cH=(window.innerHeight?window.innerHeight:document.body.clientHeight);

var mW=ctx.childNodes[0].offsetWidth,mH=ctx.childNodes[0].offsetHeight;

if(x+mW>cW){
	if(x>=mW){
		ctx.style.left=x-mW+scrOffX+"px"
	}else{
		ctx.style.left=cW-mW-5+scrOffX+"px"
	}
}else{
	ctx.style.left=x+scrOffX+"px"
}

if(y+mH>cH)
	ctx.style.top=y-mH+scrOffY+"px";
else 
	ctx.style.top=y+scrOffY+"px";

ctx.style.visibility="visible";
this.active=true
}

function showMenuAbs(x,y){
	hideAllMenu();
	var ctx=NlsGetElementById(this.mId);
	ctx.style.top=y+"px";
	ctx.style.left=y+"px";
	ctx.style.display="";
	this.active=true
}

function hideMenu(){
	var ctx=NlsGetElementById(this.mId);
	ctx.style.display="none";
	this.active=false;
	if(this.lsItm!=null){
		setStyle(this.lsItm,"N");
		this.lsItm=null
	}
}

function hideAllMenu(){
	for(it in nlsctxmenu){
		if(nlsctxmenu[it].active)nlsctxmenu[it].hideMenu();
	}
}

function enableItem(key,b){var intKey=this.mId+"_"+key;this.items[intKey].enable=b;setStyle(NlsGetElementById(intKey),(b?"N":"D"))}
function setStyle(it,s){var suff=(s=="O"?"over":"");it.cells[0].className="ctxsidebar"+suff;it.cells[1].className="ctxitem"+suff;it.cells[1].childNodes[0].className="ctxtext"+(s=="D"?"disable":(s=="OD"?"overdisable":suff));}
function ctxItemOver(it){var m=it.split("_");var oIt=NlsGetElementById(it);var li=nlsctxmenu[m[0]].lsItm;if(li!=null&&li.intKey==it)return;if(li!=null)setStyle(li,(nlsctxmenu[m[0]].items[li.id].enable?"N":"D"));setStyle(oIt,(nlsctxmenu[m[0]].items[it].enable?"O":"OD"));nlsctxmenu[m[0]].lsItm=oIt}
function menuOnClick(selNode,menuId,itemId){}
function menuOnShow(selNode){}