
//bind hint elements, for framed pages of cp 
//processing is defined in cp/tpl/cms/menu.htm


// after menu frame is loaded
$(parent.menu).ready(function(){

	//bind hint elements
	$('[liskHint]').each(function(){
		
		var text = $(this).attr('liskHint');
		
		//hover
		$(this).hover(function(text2){
			return function()
			{
				parent.menu.liskHint.ShowHint(text2);
			}
		}(text));
		
		//live hover
		$(this).live('hover', function(text2){
			return function()
			{
				parent.menu.liskHint.ShowHint(text2);
			}
		}(text));
		
		//mouseout
		$(this).mouseout(function(){
			parent.menu.liskHint.ClearHint();
		});

		//live mouseout
		$(this).live('mouseout', function(){
			parent.menu.liskHint.ClearHint();
		});
	});
});