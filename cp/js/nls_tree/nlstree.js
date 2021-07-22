/*AddObject.com
*You are not allowed to download and use this script for any type of application unless you have obtain a license
*Copyright www.addobject.com 2005
*/

nlsTree=new Object();
nlsTreeIc=new Object();


function NlsTree(tId){
	this.tId=tId;
	this.opt=new StdOpt();
	this.ico=new StdIco();
	this.nLst=new Object();
	this.ctxMenu=null;
	this.nCtxMenu=new Object();
	
	this.tRef="";
	this.rt=null;
	this.selNd=null;
	this.tmId=null;
	this.nCnt=0;
	if(nlsTree[this.tId]!=null){
		alert("The tree with id "+this.tId+" already exist, please change the tree id.");
	}else{
		nlsTree[this.tId]=this
	}
	
	this.xmlOpn=function(){
		return"<tree id='"+this.tId+"' path='"+this.tRef+"'>\n"
	};
	
	this.xmlCls=function(){return"</tree>"};
	return this
}

function StdIco(path){
	var path = "img/cms/tree/js/";
	if(!path||path==""){
		var allScs=(document.getElementsByTagName?document.getElementsByTagName("SCRIPT"):document.scripts);
		for(var i=0;i<allScs.length;i++){
			if(allScs[i].src.toLowerCase().indexOf("nlstree.js")>=0){
				//path=allScs[i].src.replace(/nlstree.js/gi,"img/");
				path=allScs[i].src.replace(/nlstree.js/gi,"../../img/");
				break
			}
		}
	}
	
	//path = "../img/";
	
	this.pnb=path+"plusnb.gif";this.pb=path+"plusb.gif";this.mnb=path+"minusnb.gif";this.mb=path+"minusb.gif";this.opf=path+"folderopen.gif";this.clf=path+"folder.gif";this.chd=path+"leaf.gif";this.rot=path+"root.gif";this.lnb=path+"lineang.gif";this.lb=path+"lineints.gif";this.lin=path+"line.gif";this.bln=path+"blank.gif";
	
	preloadIcon(this.pnb,this.pb,this.mnb,this.mb,this.opf,this.clf,this.chd,this.rot,this.lnb,this.lb,this.lin,this.bln);
	
	this.toString=function() {
		return"Standard Icons"
	};
	return this
}

function StdOpt() {
	this.trg="_self";
	this.stlprf="";
	this.sort="no";
	this.icon=true;
	this.check=false;
	this.editable=false;
	this.selRow=false;
	this.editKey=113;
	this.oneExp=false;
	this.enableCtx=true;
	this.oneClick=true;
	this.enableCookie=false;
	
	this.xmlOpn=function() {
		return "<option>\n<target>"+this.trg+"</target>\n<sort>"+this.sort+"</sort>\n<icon>"+this.icon+"</icon>\n<check>"+this.check+"</check>\n<editable>"+this.editable+"</editable>\n<editkey>"+this.editKey+"</editkey>\n<selrow>"+this.selRow+"</selrow>\n</option>\n"
	};
	
	return this
}

function NlsNode(orgId,capt,url,ic,exp,chk,xtra){this.orgId=orgId;this.id="";this.capt=capt;this.url=(url==null||url=="")?"javascript:void(0)":url;this.ic=(ic==null||ic=="")?null:ic.split(",");this.exp=exp==null?false:exp;this.chk=(chk?chk:false);this.xtra=xtra==null?false:xtra;this.ctxMenu=null;this.cstStyle="";if(this.ic){preloadIcon(this.ic[0]);if(this.ic.length>1)preloadIcon(this.ic[1]);}this.nx=null;this.pv=null;this.fc=null;this.lc=null;this.pr=null;this.equals=function(nd){return(this.id==nd.id);};this.xmlOpn=function(){return"<node id='"+this.id+"' caption='"+this.capt+"' url='"+this.url+"' ic='"+this.ic+"' exp='"+this.exp+"' chk='"+this.chk+"'>"};this.xmlCls=function(){return"</node>"};return this}NlsTree.prototype.genIntId=function(id){return this.tId+id};NlsTree.prototype.genOrgId=function(intId){return intId.substr(this.tId.length);};NlsTree.prototype.compareNode=function(aN,bN){return(aN.capt>=bN.capt);};NlsTree.prototype.add=function(id,prn,capt,url,ic,exp,chk,xtra){var nNd=new NlsNode(((id==null||id=="")?("int"+(++this.nCnt)):id),capt,url,ic,exp,chk,xtra);nNd.id=this.genIntId(nNd.orgId);if(this.nLst[nNd.id]!=null){alert("Item with id "+id+" already exist");return}this.nLst[nNd.id]=nNd;if(this.rt==null){this.rt=nNd}else{var pnd=this.nLst[this.genIntId(prn)];if(pnd==null){alert("Parent node "+prn+" not found!!");return}nNd.pr=pnd;if(pnd.lc==null){pnd.fc=nNd;pnd.lc=nNd}else{var t=pnd.fc;if(this.opt.sort!="no"){do{if(this.opt.sort=="asc"?this.compareNode(t,nNd):this.compareNode(nNd,t))break;t=t.nx}while(t!=null);if(t!=null){if(t.pv==null){t.pv=nNd;pnd.fc=nNd}else{nNd.pv=t.pv;t.pv.nx=nNd;t.pv=nNd}nNd.nx=t}}if(this.opt.sort=="no"||t==null){nNd.pv=pnd.lc;pnd.lc.nx=nNd;pnd.lc=nNd}}}return nNd};NlsTree.prototype.addBefore=function(id,sib,capt,url,ic,exp,chk,xtra){var nd=this.getNodeById(sib);if(nd==null)return;var nNd=new NlsNode(((id==null||id=="")?("int"+(++this.nCnt)):id),capt,url,ic,exp,chk,xtra);nNd.id=this.genIntId(nNd.orgId);if(this.nLst[nNd.id]!=null){alert("Item with id "+id+" already exist");return}this.nLst[nNd.id]=nNd;nNd.pr=nd.pr;nNd.nx=nd;if(nd.pv==null){nd.pv=nNd;nd.pr.fc=nNd}else{nNd.pv=nd.pv;nd.pv.nx=nNd;nd.pv=nNd}return nNd};NlsTree.prototype.addAfter=function(id,sib,capt,url,ic,exp,chk,xtra){var nd=this.getNodeById(sib);if(nd==null)return;var nNd=new NlsNode(((id==null||id=="")?("int"+(++this.nCnt)):id),capt,url,ic,exp,chk,xtra);nNd.id=this.genIntId(nNd.orgId);if(this.nLst[nNd.id]!=null){alert("Item with id "+id+" already exist");return}this.nLst[nNd.id]=nNd;nNd.pr=nd.pr;nNd.pv=nd;if(nd.nx==null){nd.nx=nNd;nd.pr.lc=nNd}else{nNd.nx=nd.nx;nd.nx.pv=nNd;nd.nx=nNd}return nNd};

NlsTree.prototype.append=function(id,prn,capt,url,ic,exp,chk,xtra){
	var nd=this.add(id,prn,capt,url,ic,exp,chk,xtra);
	this.reloadNode(prn);
	return nd
};
	NlsTree.prototype.remove=function(id,reload){var rNd=(id!=null?this.nLst[this.genIntId(id)]:this.selNd);if(rNd!=null){if(this.rt.equals(rNd)){this.rt=null;this.nLst=new Object();this.selNd=null;return rNd};if(rNd.equals(this.selNd))this.selNd=null;var pr=rNd.pr;if(pr.lc.equals(rNd))pr.lc=rNd.pv;if(pr.fc.equals(rNd))pr.fc=rNd.nx;if(rNd.pv!=null)rNd.pv.nx=rNd.nx;if(rNd.nx!=null)rNd.nx.pv=rNd.pv;rNd.nx=null;rNd.pv=null;rNd.pr=null;var treeId=this.tId;this.loopTree(rNd,function(n){nlsTree[treeId].nLst[n.id]=null});if(reload==null||reload)this.reloadNode(this.genOrgId(pr.id));}return rNd};NlsTree.prototype.removeChilds=function(id,reload){var rNd=(id!=null?this.nLst[this.genIntId(id)]:this.selNd);if(rNd!=null){while(rNd.fc)this.remove(rNd.fc.orgId,false);if(reload==null||reload)this.reloadNode(id);}};NlsTree.prototype.getSelNode=function(){return this.selNd};NlsTree.prototype.genANode=function(sNd){var ev="";var st="";var cm="";var treeName=this.tRef+"nlsTree."+this.tId;var ip=(sNd.nx!=null?this.ico.lb:this.ico.lnb);var sv=treeName+".selectNode(\""+sNd.id+"\");";
var cm=treeName+".contextMenu(event, \""+sNd.id+"\");";
var cn=treeName+".checkNode(\""+sNd.id+"\");";if(sNd.fc){ev=treeName+".prepareToggle(\""+sNd.id+"\");"+treeName+".toggleNode(\""+sNd.id+"\");";st=treeName+".selNToggle(\""+sNd.id+"\");";ip=(sNd.nx!=null?(sNd.exp?this.ico.mb:this.ico.pb):(sNd.exp?this.ico.mnb:this.ico.pnb));}else sNd.exp=false;
var s=(sNd.pr==null?"":"<img id=ip_"+sNd.id+" src='"+ip+"' "+
(sNd.fc==null?"":"onclick='"+ev+"'")+">");if(this.opt.icon||sNd.equals(this.rt)){var evl="' onclick='"+(sNd.fc&&this.opt.oneClick?st:sv)+" return "+treeName+".treeOnClick(event);' oncontextmenu='return "+cm+"' ondblclick='"+st+" return "+treeName+".treeOnDblClick(event)' onmouseover='"+treeName+".treeOnMouseOver(event)' onmousemove='"+treeName+".treeOnMouseMove(event)' onmouseout='"+treeName+".treeOnMouseOut(event)' onmousedown='"+treeName+".treeOnMouseDown(event)' onmouseUp='"+treeName+".treeOnMouseUp(event)' >";
var isrc=(sNd.ic?sNd.ic[0]:sNd.equals(this.rt)?this.ico.rot:(sNd.fc)?this.ico.clf:this.ico.chd);
s+=("<img id=ic_"+sNd.id+" src='"+isrc+evl);
if(sNd.ic){isrc=sNd.ic.length>1?sNd.ic[1]:""}
else{isrc=(sNd.equals(this.rt)?"":sNd.fc?this.ico.opf:"");}
if(isrc!=""){s+=("<img id=ic2_"+sNd.id+" style='display:none' src='"+isrc+evl);}}s+=(this.opt.check?"<td valign='middle'><input style='height:14px;margin-top:0px;margin-bottom:0px;padding:0px' type='checkbox' id=cb_"+sNd.id+" "+(sNd.chk?"checked":"")+" onclick='"+cn+treeName+".treeOnCheck("+sNd.orgId+")'></td>":"")+"</td><td valign='middle' nowrap style='padding-left:1px'><a target=\""+this.opt.trg+"\" href=\""+sNd.url+"\" id=ac_"+sNd.id+" class='"+this.opt.stlprf+(sNd.fc?"prnnode":"node")+"' unselectable='on' onclick='"+(sNd.fc&&this.opt.oneClick?st:sv)+" return "+treeName+".treeOnClick(event);' oncontextmenu='return "+cm+"' ondblclick='"+st+" return "+treeName+".treeOnDblClick(event)' onmouseover='"+treeName+".treeOnMouseOver(event)' onmousemove='"+treeName+".treeOnMouseMove(event)' onmouseout='"+treeName+".treeOnMouseOut(event)' onmousedown='"+treeName+".treeOnMouseDown(event)' onmouseup='"+treeName+".treeOnMouseUp(event)'><span id='cstl_"+sNd.id+"' "+(sNd.cstStyle!=""?"class='"+sNd.cstStyle+"'":"")+" >"+sNd.capt+"</span></a></td>";var n=sNd.pr;while(n!=null&&!n.equals(this.rt)){s="<img src='"+(n.nx!=null?this.ico.lin:this.ico.bln)+"'>"+s;n=n.pr}s="<td nowrap align=left valign='top'>"+s;s="<table cellpadding='0' cellspacing='0' border='0'><tr>"+s+"</tr></table>";if(sNd.ctxMenu&&sNd.ctxMenu.mId&&!this.nCtxMenu[sNd.ctxMenu.mId]){s+=sNd.ctxMenu.genMenu();this.nCtxMenu[sNd.ctxMenu.mId]=sNd.ctxMenu.mId}return s};NlsTree.prototype.genNodes=function(sNd,incpar,wrt){var s=incpar?("<div id='"+sNd.id+"' class='"+this.opt.stlprf+"row'>"+this.genANode(sNd)+"</div><div style='display:"+(sNd.fc&&sNd.exp?"block":"none")+"' id='ch_"+sNd.id+"'>"):"";if(wrt)document.write(s);if(sNd.fc!=null){var chNode=sNd.fc;do{if(wrt)this.genNodes(chNode,true,wrt);else s+=this.genNodes(chNode,true,wrt);chNode=chNode.nx}while(chNode!=null)}if(wrt){if(incpar)document.write("</div>");return""}else{s=incpar?(s+"</div>"):s;return s}};NlsTree.prototype.genTree=function(){return this.genNodes(this.rt,true,false)+"<input id='ndedt"+this.tId+"' type='text' class='"+this.opt.stlprf+"nodeedit' style='display:none' value='' onblur='nlsTree."+this.tId+".liveNodeWrite()'>"+(this.ctxMenu?this.ctxMenu.genMenu():"")+(!NlsGetElementById("ddGesture")?"<div id='ddGesture' style='position:absolute;border:#f0f0f0 1px solid;display:none'>":"");};

NlsTree.prototype.render=function(plc){
	if(plc&&plc!=""){
		NlsGetElementById(plc).innerHTML=this.genTree();
	} else {
		this.genNodes(this.rt,true,true);
		document.write("<input id='ndedt"+this.tId+"' type='text' class='"+this.opt.stlprf+"nodeedit' style='display:none' value='' onblur='nlsTree."+this.tId+".liveNodeWrite()'>"+(this.ctxMenu?this.ctxMenu.genMenu():"")+(!NlsGetElementById("ddGesture")?"<div id='ddGesture' style='position:absolute;border:#f0f0f0 1px solid;display:none'>":""));
	}
	this.initEvent();
	if(this.opt.enableCookie&&this.getCookie){
		var sid=this.getCookie(this.tId+"_selnd");
		if(sid&&sid!="")this.selectNodeById(sid);
	}
};

NlsTree.prototype.initEvent=function(){var isIE=(navigator.userAgent.indexOf("MSIE")>=0);var orgEvent=(isIE?document.body.onkeydown:window.onkeydown);if(!orgEvent||orgEvent.toString().search(/orgEvent/gi)<0){var newEvent=function(e){if(nlsTree.selectedTree)nlsTree.selectedTree.liveNodePress(isIE?event:e);if(orgEvent)return orgEvent();};if(isIE)document.body.onkeydown=newEvent;else window.onkeydown=newEvent}};NlsTree.prototype.reloadNode=function(id){var intId=this.genIntId(id);var s=this.genNodes(this.nLst[intId],false);var dvN=NlsGetElementById("ch_"+intId);dvN.innerHTML=s;if(dvN.innerHTML=="")dvN.style.display="none";s=this.genANode(this.nLst[intId]);dvN=NlsGetElementById(intId);dvN.innerHTML=s;if(this.selNd!=null){var sId=this.selNd.id;this.selNd=null;this.selectNode(sId);}};NlsTree.prototype.selNToggle=function(id){this.toggleNode(id);if(!this.selNd||this.selNd.id!=id)this.selectNode(id);if(this.tmId!=null){clearTimeout(this.tmId);this.tmId=null}};NlsTree.prototype.selectNode=function(id){nlsTree.selectedTree=this;if(this.opt.editable){if(this.selNd!=null&&this.selNd.id!=id){if(this.tmId){clearTimeout(this.tmId);this.tmId=null}}if(this.selNd!=null&&this.selNd.id==id){this.tmId=setTimeout("nlsTree."+this.tId+".liveNodeEdit('"+id+"')",1000);}if(NlsGetElementById("ndedt"+this.tId).style.display==""){var edt=NlsGetElementById("ndedt"+this.tId);edt.style.display="none";edt.disabled=true}}var ac=null;var ic=null;var sNd=null;sNd=this.selNd;if(sNd!=null){if(this.opt.selRow)NlsGetElementById(sNd.id).className=this.opt.stlprf+"row";ac=NlsGetElementById("ac_"+sNd.id);if(this.opt.icon){var ic2=NlsGetElementById("ic2_"+sNd.id);if(ic2){ic2.style.display="none";NlsGetElementById("ic_"+sNd.id).style.display=""}}ac.className=this.opt.stlprf+(sNd.fc?"prnnode":"node");}sNd=this.nLst[id];this.selNd=sNd;if(this.opt.selRow)NlsGetElementById(id).className=this.opt.stlprf+"selrow";ac=NlsGetElementById("ac_"+id);if(this.opt.icon){var ic2=NlsGetElementById("ic2_"+id);if(ic2){NlsGetElementById("ic_"+id).style.display="none";ic2.style.display=""}}ac.className=this.opt.stlprf+(sNd.fc?"selprnnode":"selnode");if(this.opt.enableCookie&&this.setCookie)this.setCookie(this.tId+"_selnd",sNd.orgId);};NlsTree.prototype.selectNodeById=function(id){var node=this.getNodeById(id);if(!node)return;var tmp=node;while(tmp.pr!=null){this.expandNode(tmp.orgId);tmp=tmp.pr}this.selectNode(node.id);};NlsTree.prototype.isChild=function(c,p){var nd=this.getNodeById(c);if(!nd)return false;var tmp=nd.pr;while(tmp!=null){if(tmp.orgId==p)return true;tmp=tmp.pr}return false};NlsTree.prototype.hasChild=function(id){var nd=this.getNodeById(id);return(nd.fc!=null);};NlsTree.prototype.expandNode=function(id){var sNd=this.nLst[this.genIntId(id)];if(!sNd.exp)this.toggleNode(sNd.id);};NlsTree.prototype.collapseNode=function(id){var sNd=this.nLst[this.genIntId(id)];if(sNd.exp)this.toggleNode(sNd.id);};NlsTree.prototype.prepareToggle=function(id){var sNd=this.selNd;if(sNd==null){this.selectNode(id);return}if(sNd.id==id)return;while(sNd!=null&&sNd.id!=id){sNd=sNd.pr}if(sNd==null)return;if(sNd.id==id)this.selectNode(id);};NlsTree.prototype.toggleNode=function(id){var nd=NlsGetElementById("ch_"+id);var ip=NlsGetElementById("ip_"+id);var sNd=this.nLst[id];if(sNd.exp){sNd.exp=false;nd.style.display="none";if(ip!=null&&sNd.fc!=null)ip.src=sNd.nx?this.ico.pb:this.ico.pnb;this.treeOnCollapse(sNd.orgId);}else{if(this.opt.oneExp&&sNd.pr){var tNd=sNd.pr.fc;while(tNd){if(tNd.id!=id&&tNd.exp)this.collapseNode(tNd.orgId);tNd=tNd.nx}}sNd.exp=true;nd.style.display="block";if(ip!=null&&sNd.fc!=null)ip.src=sNd.nx?this.ico.mb:this.ico.mnb;this.treeOnExpand(sNd.orgId);}};NlsTree.prototype.expandAll=function(){var treeId=this.tId;this.loopTree(this.rt,function(n){if(n.fc)nlsTree[treeId].expandNode(n.orgId);});};NlsTree.prototype.collapseAll=function(incPr){var treeId=this.tId;this.loopTree(this.rt,function(n){if(n.fc&&(!nlsTree[treeId].rt.equals(n)||incPr))nlsTree[treeId].collapseNode(n.orgId);});};NlsTree.prototype.checkNode=function(intId){var nd=NlsGetElementById("cb_"+intId);var sNd=this.nLst[intId];sNd.chk=nd.checked};NlsTree.prototype.setNodeStyle=function(id,cls,rt){var nd=this.getNodeById(id);nd.cstStyle=cls;if(rt){var oNd=NlsGetElementById("cstl_"+nd.id);if(oNd)oNd.className=cls}};NlsTree.prototype.setNodeCaption=function(id,capt){var intId=this.genIntId(id);var nd=NlsGetElementById("ac_"+intId);var sNd=this.nLst[intId];nd.innerHTML=capt;sNd.capt=capt};NlsTree.prototype.getNodeById=function(id){return this.nLst[this.genIntId(id)]};NlsTree.prototype.setGlobalCtxMenu=function(ctx){this.ctxMenu=ctx;ctx.container=this};NlsTree.prototype.setNodeCtxMenu=function(id,ctx){var nd=this.nLst[this.genIntId(id)];nd.ctxMenu=ctx;if(ctx.mId)ctx.container=this};

NlsTree.prototype.contextMenu=function(ev,id){
	
/*	var str = ""; 
	for (prop in ev) { 
	   str += prop + " = "+ ev[prop] + ";<br>"; 
	} 
	var win = window.open (); 
	win.document.write (str);*/

	
	if(!this.opt.enableCtx) return false;
	var sNd=this.nLst[id];
	var ctx=null;
	
	if(sNd.ctxMenu&&sNd.ctxMenu.mId)ctx=sNd.ctxMenu;
	else if(sNd.ctxMenu=="DEFAULT")ctx=null;
	else if(sNd.ctxMenu=="NONE")return false;
	else ctx=this.ctxMenu;
	
	if(!ctx)return true;
	this.selectNode(id);
	if(this.tmId)clearTimeout(this.tmId);
	ctx.showMenu(ev.clientX,ev.clientY);
	return false
};

NlsTree.prototype.loopTree=function(sNd,act){act(sNd);if(sNd.fc!=null){var chNode=sNd.fc;do{this.loopTree(chNode,act);chNode=chNode.nx}while(chNode!=null)}};NlsTree.prototype.nodeXML=function(sNd){sNd=(sNd==null?this.rt:sNd);var n=sNd;var spc="";while(n!=null&&!n.equals(this.rt)){spc+="  ";n=n.pr}var s=(spc+sNd.xmlOpn()+"\n");if(sNd.fc!=null){var chNode=sNd.fc;do{s+=this.nodeXML(chNode);chNode=chNode.nx}while(chNode!=null)}s+=(spc+sNd.xmlCls()+"\n");return s};NlsTree.prototype.toXML=function(){var icxml="<icon>\n<plus>"+this.ico.pnb+":"+this.ico.pb+"</plus>\n"+"<minus>"+this.ico.mnb+":"+this.ico.mb+"</minus>\n"+"<ico>"+this.ico.opf+":"+this.ico.clf+"</ico>\n"+"<chd>"+this.ico.chd+"</chd>\n"+"<root>"+this.ico.rot+"</root>\n"+"<line>"+this.ico.lnb+":"+this.ico.lb+":"+this.ico.lin+"</line>\n"+"<blank>"+this.ico.bln+"</blank>\n"+"</icon>\n";return this.xmlOpn()+this.opt.xmlOpn()+icxml+this.nodeXML(this.rt)+this.xmlCls();};NlsTree.prototype.liveNodeEditStart=function(id){this.tmId=setTimeout("nlsTree."+this.tId+".liveNodeEdit('"+id+"')",0)};NlsTree.prototype.liveNodeEdit=function(id){if(this.tmId!=null){var edt=NlsGetElementById("ndedt"+this.tId);var ac=NlsGetElementById("ac_"+id);var sp=NlsGetElementById("cstl_"+id);var x=0,y=0,elm=ac;while(elm.tagName!="BODY"){x+=elm.offsetLeft;y+=elm.offsetTop;elm=elm.offsetParent}with(edt){disabled=false;style.top=y;style.left=x;style.display="";focus();value=sp.innerHTML}this.tmId=null}};NlsTree.prototype.liveNodeWrite=function(){var edt=NlsGetElementById("ndedt"+this.tId);if(edt.style.display=="none")return;var ac=NlsGetElementById("cstl_"+this.selNd.id);if(edt.value!=""){ac.innerHTML=edt.value;this.selNd.capt=edt.value}edt.style.display="none";edt.disabled=true};NlsTree.prototype.liveNodePress=function(e){if(!this.opt.editable)return;if(e.keyCode==13){this.liveNodeWrite();}else if(e.keyCode==27){var edt=NlsGetElementById("ndedt"+this.tId);edt.style.display="none";edt.disabled=true}else if(e.keyCode==this.opt.editKey){this.tmId=setTimeout("nlsTree."+this.tId+".liveNodeEdit('"+this.selNd.id+"')",10);}};NlsTree.prototype.setCookie=function(key,value,expire){document.cookie=escape(key)+"="+escape(value)+(expire?"; expires="+expire:"");};NlsTree.prototype.getCookie=function(key){if(document.cookie){var c=document.cookie.split(";")[0].split("=");if(unescape(c[0])==key){return unescape(c[1]);}}return""};NlsTree.prototype.removeCookie=function(){this.setCookie(this.tId+"_selnd","-1","Fri, 31 Dec 1999 23:59:59 GMT;");};NlsTree.prototype.treeOnClick=function(e){};NlsTree.prototype.treeOnDblClick=function(e){};NlsTree.prototype.treeOnMouseOver=function(e){};NlsTree.prototype.treeOnMouseMove=function(e){};NlsTree.prototype.treeOnMouseOut=function(e){};NlsTree.prototype.treeOnMouseDown=function(e){};NlsTree.prototype.treeOnMouseUp=function(e){};NlsTree.prototype.treeOnCheck=function(id){};NlsTree.prototype.treeOnExpand=function(id){};NlsTree.prototype.treeOnCollapse=function(id){};

function preloadIcon(){
	var arg=preloadIcon.arguments;
	for(var i=0;i<arg.length;i++){
		if(!nlsTreeIc[arg[i]]){
			nlsTreeIc[arg[i]]=new Image();
			nlsTreeIc[arg[i]].src=arg[i]
		}
	}
}

function NlsGetElementById(id) {
	if(document.all){
		return document.all(id);
	} else if(document.getElementById) {
		return document.getElementById(id);
	}
}