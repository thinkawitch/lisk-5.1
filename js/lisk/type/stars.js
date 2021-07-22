(function($){
    $.liskTypeStar = function(params){
        var opts = $.extend({}, params);
        var name_uid = opts.name+opts.uid;

        var $input = $('#stars_'+ name_uid);
        var $starsReset = $('#stars_table_'+name_uid+' .liskStarsReset');
        var $starsContainer = $('#lisk_stars_'+name_uid);
        var $ratingBar = $starsContainer.find('div');

        var X0 = $starsContainer.offset()['left'];
        var Y0 = $starsContainer.offset()['top'];
        var width = opts.maxStars*opts.sizeStar;
        var step_width = 1/opts.split;

        $starsReset.click(function(){
            $input.val(0).data('oldValue',0);
            $ratingBar.css('width',0);
            
            Autosave(0);
        });

        $starsContainer.mouseenter(function(e){
            $(this).addClass('edit');
            if (X0 == 0){
                X0 = $starsContainer.offset()['left'];
                Y0 = $starsContainer.offset()['top'];
            }
            $(this)
            .bind('mousemove.star', setValues)
            .bind('click.star', setValues)
            .bind('mouseleave.star', unsetValues);
        });

        // set default values
        $ratingBar.data('oldWidth', $input.val()*opts.sizeStar+'px');
        $input.data('oldValue', $input.val());

        function calculateNumber(offset){
            var number = offset/opts.sizeStar;
            //calculate solid part
            var solid = Math.floor(number);
            //calculate mod part
            var mod = number - solid;
            var substeps = Math.ceil(mod/step_width);
            var value = solid+substeps*step_width;
            offset = opts.sizeStar * value;
            return {value: value, offset: offset};
        }

        function setValues(e){
            var offset = e.clientX - X0;
            var data = calculateNumber(offset);
            if (e.type == 'click')
            {
                $input.val(data.value).data('oldValue', data.value);
                $(this).unbind('.star');
                $ratingBar.removeClass('edit');
                
                Autosave(data.value);
            }
            else
            {
                $ratingBar.addClass('edit');
            }
            $ratingBar.css('width', data.offset+'px');
        }

        function unsetValues(e){
            $(this).unbind('.star');
            $ratingBar.css('width', $input.val()*opts.sizeStar+'px');
            $ratingBar.removeClass('edit');
        }
        
        function Autosave(value)
        {
        	if (!opts || !opts.autosave) return;
        	
        	//alert(value);
        	var selector = $('table[autosave="' + opts.autosave.autosave_uid + '"]');
        	
            $.post(
            	opts.autosave.handler_url, 
            	{
	                di:     opts.autosave.dataitem_name,
	                itemId: opts.autosave.dataitem_id,
	                field:  opts.autosave.dataitem_field,
	                value:  value,
	                action: 'autosave'
	            }, 
	            function(sel)
	            {
	            	return function()
	            	{
	            		sel.removeClass('stars_block_red').addClass('stars_block_green');
	            		window.setTimeout(function(){sel.removeClass('stars_block_green')}, 3000);
	            	}
	            }(selector)
            );
        }

        return this;
    };
})(jQuery);