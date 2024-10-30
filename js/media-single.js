var ds = ds || {};
(function($){
	var media;
	ds.media = media = {
		// MHULL edit: use class instead of ID
		buttonClass: '.open-media-button',
		// Keep track of button ID for insertion later
		init: function() {
			var clickedId;
			$( media.buttonClass ).on( 'click', function( e ) {
				e.preventDefault();				
				// MHULL edit: pass ID of button on click
				media.frame(this.id).open();
				media.buttonId = this.id;
			});
		},		
		buttonId: "",
		detailsContainerId: '#attachment-details',

		frame: function(id) {
			if ( this._frame )
				return this._frame;
			this.buttonId = id;
			this._frame = wp.media( {
				title: 'Select An Image',
				button: {
					text: 'Select'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			} );

			this._frame.on( 'ready', this.ready );

			this._frame.state( 'library' ).on( 'select', this.select );

			return this._frame;
		},

		ready: function() {
			$( '.media-modal' ).addClass( 'no-sidebar smaller' );
		},

		select: function() {
			var settings = wp.media.view.settings,
				selection = this.get( 'selection' ).single();

			media.showAttachmentDetails( selection );
		},

		showAttachmentDetails: function( attachment ) {

			// MHULL edit: use clicked button ID to find <input> where we'll insert the chosen image URL
			var fieldName = this.buttonId.replace('media-button-', '');
			$("input#"+fieldName).val(attachment.get('url'));
			// place image into thumbnail div
			$("div#"+fieldName+"-thumb-preview").html("");
			$("div#"+fieldName+"-thumb-preview").append("<img src='" + attachment.get('url') + "'/>");
		},
	};
	$( media.init );
})(jQuery);
