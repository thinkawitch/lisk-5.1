
//is used in search form
function menuSlide(t)
{
	$("#"+t).slideToggle();
}


CmsList = function(id)
{
	this.id = id;
	this.selector = $('#' + this.id);
	
	this.isAllSelected = false;
	this.totalRows = 0;
	
	this.pagingPcp = 0;
	this.diName = '';
	
	this.msgDeleteSelected = 'Are you sure you want to delete all selected records?';
	this.msgDeleteRecord = 'Are you sure you want to delete?';
}

CmsList.prototype.Init = function(pagingPcp, diName)
{
	//count total rows
	var rowsCount = this.selector.find('.cl-row').length;
	rowsCount = liskParseInt(rowsCount);
	if (rowsCount>0) this.totalRows = rowsCount;
	
	
	this.pagingPcp = pagingPcp;
	this.diName = diName;
}

CmsList.prototype.SelectAll = function()
{
	this.selector.find('input[type=checkbox]').attr('checked', 'checked');
	this.selector.find('[rel="select-all"] img').attr('src', 'img/cms/list/unselect_all.gif');
	this.isAllSelected = true;
}

CmsList.prototype.ClearSelection = function()
{
	this.selector.find('input[type=checkbox]').removeAttr('checked');
	this.selector.find('[rel=select-all] img').attr('src', 'img/cms/list/select_all.gif');
	this.isAllSelected = false;
}

CmsList.prototype.ToggleSelection = function()
{
	if (this.isAllSelected) this.ClearSelection();
	else this.SelectAll();
}

CmsList.prototype.DeleteSelected = function()
{
	//check if there is at least one item to be deleted
	var count = this.selector.find('input[type=checkbox]:checked').length;
	if (count < 1) return;
	
	//submit form after confirmation
	ShowConfirm(this.msgDeleteSelected, function(objCL){
		return function()
		{
			$('#' + objCL.id).submit();
		}
	}(this));
}

CmsList.prototype.DeleteRecord = function(handler, recordId)
{
	ShowConfirm(this.msgDeleteRecord, function(objCL2, handler2, recordId2){
		
		return function()
		{
			$.get(handler2, function(objCL3, recordId3){
				return function(data) 
				{
					objCL3.HideRecord(recordId3);
				}
			}(objCL2, recordId2));
		}

	}(this, handler, recordId));
}

CmsList.prototype.HideRecord = function(recordId)
{
	this.totalRows--;
	
    if (this.totalRows <= 0) 
    {
     	$("tr#idRow_" + recordId).remove();
     	$("tr#idRowNoRecords").show();
    } 
    else 
    {
     	$("tr#idRow_" + recordId).css("backgroundColor", "#ffe672");	
     	$("tr#idRow_" + recordId).fadeOut("slow");
    }
}

CmsList.prototype.BindControls = function()
{
	//add mouse over for list rows
	this.selector.find('.cl-row').mouseover(function(){
		$(this).addClass('ListTDOn');
	});
	
	//add mouse out for list rows
	this.selector.find('.cl-row').mouseout(function(){
		$(this).removeClass('ListTDOn');
	});
	
	//select all 
	this.selector.find('[rel=select-all]').click(function(objCL){
			return function()
			{
				objCL.ToggleSelection();
				return false;
			}
	}(this));
	
	//delete selected
	this.selector.find('[rel=delete-selected]').click(function(objCL){
			return function()
			{
				objCL.DeleteSelected();
				return false;
			}
	}(this));
	
	//bind delete button for each row
	this.selector.find('.cl-row').each(function(objCL){
			return function()
			{
				var rowId = $(this).attr('id').substr(6, 200);
				
				//bind click event
				$(this).find('a.delete').click(function(objCL2, recordId){
					return function()
					{
						//do deletion
						var handler = $(this).attr('rel');
						objCL2.DeleteRecord(handler, recordId);
						return false;
					}
				}(objCL, rowId));

			}
	}(this));
	
	//bind inline order for list
	this.selector.find('.cl-table').sortable({ 
		axis: 'y', 
		cursor: 'move', 
		handle: '.order-handle-box', 
		items: 'tr[id]', 
		opacity: 0.85, 
		distance: 2,
		containment: 'parent',
		stop: function(objCL){
			return function(event, ui) 
			{ 
				objCL.SaveListOrder(event, ui) 
			}
		}(this)
	});
	
	//and finaly allow form to be submitted
	this.selector.attr('onsubmit', '');
}

CmsList.prototype.SaveListOrder = function(event, ui)
{
	var items = this.selector.find(".cl-table tr[id]");
	var newOrder = '';
	for (var x=0; x<items.length; x++)
	{
		var recordId =  items[x].id;
		if (recordId.substring(0,6)=='idRow_')
		{
			recordId = recordId.substring(6);
			newOrder += recordId + ';';                              
		}
	}

	if (newOrder !== '')
	{
		jQuery.ajax({
			url: 'order.php?action=inline_order',
			type: 'POST',
			data: { action: 'submit', new_order: newOrder, paging_pcp:this.pagingPcp, dataitem_name:this.diName },
			dataType: 'json',
			timeout:  3000,
			error: function(){
				alert('New order not saved!\nPlease reload this page and try again.');
			},
			success: function(data){
				//alert(1);
				return;
			}
		});
	}
}