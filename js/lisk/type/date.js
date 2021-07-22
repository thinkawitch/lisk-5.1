
function LiskTypeDate(name)
{
	this.name = name;
	
	this.selValue = $('#' + this.name);
	this.selInline = $('#idInlineCalendar_' + this.name);
	
	this.selYear =  $('#' + this.name + '_year');
	this.selMonth =  $('#' + this.name + '_month');
	this.selDay =  $('#' + this.name + '_day');
	
	this.selImage = $('a[rel="'+ this.name +'"]');
	
	this.dp = null;
	
	this.defaultSettings = {minDate: '1930-01-01', maxDate: '2030-01-01', dateFormat: 'yy-mm-dd', render:'form'};
	this.settings = {};
	
	this.Init = function(settins)
	{
		//init settings
		this.settings = $.extend({}, this.defaultSettings, settins);
		
		var bindTo = this.settings.render == 'inline' ? this.selInline : this.selValue;
		
		//create datepicker
		this.dp = bindTo.datepicker({
			dateFormat: this.settings.dateFormat,
			firstDay: 1, //monday
			minDate: this.settings.minDate,
			maxDate: this.settings.maxDate,
			onSelect: function(ctrlDate) 
			{
				return function(dateText, inst) 
				{
					ctrlDate.UpdateFields(dateText);
				}
			}(this)
		});
		
		//init value if inline
		if (bindTo == this.selInline)
		{
			this.dp.datepicker("setDate" , this.selValue.val());
		}
		
		this.BindControls();
	}
	
	this.BindControls = function()
	{
		//bind show calendar
		this.selImage.click(function(ctrlDate){
			return function()
			{
				ctrlDate.ShowCalendar();
			}
		}(this));
		
		//bind selects update
		var updateFunc = function(ctrlDate)
		{
			return function()
			{
				ctrlDate.UpdateCalendarValueFromSelects();
			}
			
		}(this);
		this.selYear.change(updateFunc);
		this.selMonth.change(updateFunc);
		this.selDay.change(updateFunc);
	}
	
	this.ShowCalendar = function()
	{
		this.dp.datepicker("show");
	}
	
	this.UpdateFields = function(dateText)
	{
		var y = dateText.substr(0, 4);
		var m = dateText.substr(5, 2);
		var d = dateText.substr(8, 2);
		
		this.selYear.val(y);
		this.selMonth.val(m);
		this.selDay.val(d);
	}
	
	this.UpdateCalendarValueFromSelects = function()
	{
		var dateText = this.selYear.val() + "-" + this.selMonth.val() + "-" + this.selDay.val();
		this.dp.datepicker("setDate" , dateText);
	}
}