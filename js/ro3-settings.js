// main "Rule of..." selector
var $numBlocksSelect;

// font awesome size input
var $faSizeInput;

// fa icon size table row
var $faSizeTr;

// font awesome selection areas in blocks
var $faInputs;
// image selection areas in blocks
var $imgInputs;

jQuery(document).ready(function($){

	$numBlocksSelect = $('select#num_blocks');
	$faSizeInput = $('#fa_icon_size');
	$faSizeTr = $('#fa_icon_size').closest('tr');
	$faInputs = $('input.fa_icon');
	$imgInputs = $('input.image');

	/* onclick for 'Rule Of...' (num_blocks) select */
	$numBlocksSelect.on( 'change', function() {
		toggleNumBlocks( this, $ );
	} );
	toggleNumBlocks( $numBlocksSelect[0], $ );
	
	/* onclick for 'Style' radio buttons */
	$('input[name="ro3_options[style]"]').on('click', function(){
		toggleStyle(this, $);
	});
	/* initiate the selected style on page load */
	toggleStyle($('input[name="ro3_options[style]"]:checked')[0], $);
	
	/* on keyup for font awesome icon size */
	$('input#fa_icon_size').on('keyup', function(){
		changeIconSize($);
	});
	
	/* on keyup for font awesome icon inputs for blocks */
	$faInputs.on('keyup', function(){
		previewFA(this, $);
	});	
	
	// initiate the preview on page load
	$faInputs.each(function(){
		previewFA(this, $);
	});
	// initiate icon size and color
	changeIconSize($);
	changeIconColor($);
	
	// color picker
	$('.color-picker').iris({
		change: function(){ changeIconColor($); }
	});
	
	// onclick for post type radio buttons
	$('input.ro3-post-type-select').on('click', function(){
		// get post type clicked on
		var sType = $(this).val();
		// get section
		var nSection = $(this).attr('data-section');
		if('' == sType || '' == nSection) return;
		// ajax call to get posts and generate dropdown
		$.post(
			ajaxurl,
			{
				post_type: sType,
				action: 'ro3_get_posts_for_type',
				section: nSection,
			},
			function(data){
				$('#post-select-'+nSection)
				.css('display', 'block')
				.html(data);
			}
		);
	});
	// onclick for 'clear' radio buttons
	$(document).on('click', 'a.clear-post-type-select', function(){
		var nSection = $(this).attr('data-section');
		if ('' == nSection) return;
		var radios = $('input.ro3-post-type-select[name*=post_type' + nSection + ']');
		radios.each(function(){
			$(this).prop('checked', false);
			$('div#post-select-'+nSection)
				.css('display', 'none')
				.find('select').attr('value', '');
		});
	});
	
	// onclick for post select dropdown
	$(document).on('change', '.post-select select', function(){
		// newly selected value
		var id = $(this).attr('value');
		if('' == id) return;
		var nSection = $(this).attr('data-section');
		if('' == nSection) return;
		// fill in this block with select post's data
		$.post(
			ajaxurl,
			{
				action: 'ro3_get_block_data_for_post',
				post_id: id,
			},
			function(data){
				post = JSON.parse(data);
				// title
				if('' != post.post_title){
					$('input#title'+nSection).attr('value', post.post_title);
				}
				// thumbnail
				var imageInput = $('input#image'+nSection);
				var imageMsg = imageInput.closest('td').find('p.ro3-fail');
				if('' != post.thumb){
					imageInput.attr('value', post.thumb);
					$('div#image'+nSection+'-thumb-preview img').attr('src', post.thumb);
					// clear the warning message
					if(imageMsg.length > 0) imageMsg.html('');
				}
				// if no thumbnail exists
				else{
					imageInput.attr('value', '');
					$('div#image'+nSection+'-thumb-preview img').attr('src', '');
					// display a warning message
					if(imageMsg.length == 0)
						imageInput.closest('td').prepend('<p class="ro3-fail">This post doesn\'t have a featured image.</p>');
				}
				// description
				$('textarea#description'+nSection).html(post.post_excerpt);
				// link
				$('input#link'+nSection).attr('value', post.url);
			} // end: ajax success
		); // end: $.post
	}); // end: onchange for post select dropdown
}); // end: $(document).ready()

/**
 * Helper functions
 */

/**
 * Change the main 'Rule Of...' (num_blocks) setting
 */
function toggleNumBlocks( elem, $ ) {

	$elem = $( elem );

	var $blockFour = $( 'div.ro3-settings-block[data-block=4]' );

	if( 4 == $elem.val() ) $blockFour.show();
	else $blockFour.hide();


} // end: toggleNumBlocks()

// open or close the font awesome input
function toggleStyle(elem, $){
	// clear out all preview items
	$('#ro3-preview div[id^=preview]').css('display', 'none');
	// get new value
	var option = elem.value;
	// activate the preview for selected value
	$('#ro3-preview div#preview-' + option).css('display', 'block');
	
	/* for font awesome, open/close the main input area */
	if(elem.value == 'fa-icon'){
		// show font awesome size in main section
		$faSizeTr.css({display: 'table-row'});
		// show font awesome selection area in blocks
		$faInputs.closest('tr').css({display: 'table-row'});
		// hide post type selection radio buttons
		$('tr.post_type').css({display: 'none'});
		// hide image selection area in blocks
		$imgInputs.closest('tr').css({display: 'none'});
	}
	/* if not font awesome */
	else{ 
		// hide font awesome size in main section
		$faSizeTr.css({display: 'none'}); 
		// hide font awesome selection area in blocks
		$faInputs.closest('tr').css({display: 'none'});
		// show post type selection radio buttons
		$('tr.post_type').css({display: 'table-row'});		
		// show image selection area in blocks
		$imgInputs.closest('tr').css({display: 'table-row'});
	}
} // end: toggleStyle()

// change the fa icon size based on input value
function changeIconSize($){
	var size = $('input#fa_icon_size').val();
	$('i.fa').css({fontSize: size});
}
// change the fa icon color based on input value
function changeIconColor($){
	var color = $('input#main_color').val();
	$('i.fa').css({ color: color });
}
// preview the font awesome input value for a block, given the input element
function previewFA(elem, $){
	// the string we're going to preview
	var str = elem.value;
	
	// make sure we have a preview area 
	var $td = $(elem).closest('td');
	var $preview = $td.find('.ro3-fa-preview');
	if($preview.length == 0){
		$td.append('<div class="ro3-fa-preview"><i class=""></i></div>');
		$preview = $td.find('.ro3-fa-preview');
	} // end if: no preview exists
	
	// make sure the string starts with 'fa-'
	if(str.substring(0,3) != 'fa-') str = 'fa-' + str;
	// attach the class to the icon (note we'll get a broken icon if empty or mismatched)
	$preview.find('i').attr('class', 'fa ' + str);
}