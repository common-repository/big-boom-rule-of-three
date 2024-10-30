jQuery(document).ready(function($){
	// square dimensions on circle images on page load and window resize
	squareImages($);
	$(window).resize(function(){
		squareImages($);
	});
});
function squareImages($){
	// loop through <a> containers for circle images
	var containers = $("a.circle, a.nested");
	containers.each(function(){
		var width = $(this).css("width");
		// set dimensions of <a> to square
		$(this).css("height", width);	
		// set dimensions of <img> to 100% height and compensate width
		$(this).find("img")
			.css('height', '100%')
			.css('width', 'auto');		
	});
}