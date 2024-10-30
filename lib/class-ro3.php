<?php
/**
 * Helper class to handle plugin functionality. Static variables are set below class definition
 *
 * @since 	1.0.0
 */
class RO3{

	/**
	 * @param 	array 	The classes this plugin depends on
	 * @since 	1.0.0
	 */
	static $classes = array("ro3-options");

	/**
	 * @param 	string 	Which style is chosen (shortcut for RO3_Options::$options['style'])
	 * @since 	1.0.0
	 */	
	static $style;

	/**
	 * The allowed values for hover effect
	 *
	 * @param 	array
	 * @since 	1.1.0
	 */
	static $hover_effects = array( 'hvr-grow', 'hvr-float-shadow' );

	/**
	 * @param 	string 	The hex color selected as the main front end color (shortuc for RO3_Options::$options['color'])
	 */
	static $color;


	/**
	 * Back end routines
	 * 
	 * - admin_enqueue()
	 * - select_post_for_type()
	 */

	/**
	 * Enqueue styles and scripts for /wp-admin
	 *
	 * @since 	1.0.0
	 */
	public static function admin_enqueue(){

		# make sure we only load our scripts on the ro3_settings page
		$screen = get_current_screen();
		if($screen->id == "toplevel_page_ro3_settings"){

			# js
			wp_enqueue_media();	
			wp_enqueue_script("media-single-js", ro3_url('js/media-single.js'), array('media-views', 'jquery'));
			wp_enqueue_script("ro3-settings-js", ro3_url('js/ro3-settings.js'), array('jquery'));

			# css
			wp_enqueue_style('ro3-admin-css', ro3_url('css/admin_comp.css'));
			
			# iris color picker
			wp_enqueue_script( 'iris' );
		
			# font awesome
			wp_enqueue_style('ro3-fa', ro3_url('/assets/font-awesome/css/font-awesome.min.css'));
		}

	} # end: admin_enqueue()

	/**
	 * Output HTML for a <select> dropdown menu of posts for a given post type
	 *
	 * @param 	string 	$post_type 		The post type for which we're displaying posts
	 * @return 	null
	 * @since 	1.0.0
	 */

	public static function select_post_for_type($post_type, $section = ''){
		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
			'orderby' => 'post_title',
			'order' => 'ASC'
		);
		$posts = get_posts($args);
		$choices = array(
			array('value' => '', 'label' => '- Select -')
		);
		foreach($posts as $post){
			$choice = array('value' => $post->ID, 'label' => $post->post_title);
			$choices[] = $choice;
		}
		RO3_Options::do_settings_field(
			array('name' => 'post_id'.$section, 'type' => 'select', 'label' => '',
				'data' => array('section' => $section),
				'choices' => $choices,
				'description' => '<b>Note:</b> Below, you have the option to alter the title, description, image, etc. for the post you choose.  Changes to the post do not auto update here.'
			)
		);
		?></div>
		<?php	
	} # end:  select_post_for_type()


	/**
	 * Front end routines
	 *
	 * - WP hook callbacks
	 * - Shortcodes
	 * - Shortcode helpers
	 * - General helper functions
	 */
	
	/**
	 * WP hook callbacks
	 *
	 * - enqueue()
	 */

	/**
	 * Callback for 'wp_enqueue_scripts' (fires on the front end only)
	 *
	 * Note that we are only registering our styles/scripts here so that they don't load on every single page
	 * load across the site.  When the shortcode is called on a particular page, then we go ahead and enqueue
	 * the necessary scripts for that page view.
	 *
	 * @since 	1.0.0
	 */
	public static function enqueue(){

		wp_register_style('ro3-css', ro3_url('/css/comp.css'));
		wp_register_script('ro3-js', ro3_url('/js/rule-of-three.js'), array('jquery'));

		# font awesome
		wp_register_style('ro3-fa', ro3_url('/assets/font-awesome/css/font-awesome.min.css') );

	} # end: enqueue()


	/**
	 * Shortcodes
	 * 
	 * - container_html()
	 */

	/**
	 * Callback for shortcode [rule-of-three]
	 * @since 	1.0.0
	 */
	public static function container_html(){

		extract(RO3_Options::$options);

		if( empty( $num_blocks ) || ! in_array( $num_blocks, array( '3', '4' ) ) ) $num_blocks = '3';

		# do nothing if we don't have at least 1 title set
		if(empty($title1) && empty($title2) && empty($title3)) return;

		# number of columns we'll have
		$n = 3;
		# string to return
		$s = '';
		$s .= "<div id='ro3-container' class='" . $style . "-container " .  ( 4 == $num_blocks ? 'four' : 'three' ) . "-blocks'>";
		for($i = 1; $i <= $num_blocks; $i++){
			$s .= self::block_html($i);
		}
		$s .= "</div>"; // #ro3-container

		# enqueue styles and scripts for the content blocks
		wp_enqueue_style( 'ro3-css' );
		wp_enqueue_script( 'ro3-js' );

		if( 'fa-icon' == RO3_Options::$options['style'] ) {
			wp_enqueue_style( 'ro3-fa' );
		}

		return $s;
		
	} # end: container_html()
	

	/**
	 * Shortcode helpers
	 *
	 * - block_html()
	 * - block_html_basic()
	 * - block_html_bar()
	 * - block_html_fa()
	 * - header_html()
	 * - description_html()
	 * - fa_icon_html()
	 * - pad_fa()
	 */
	# get HTML for block $i
	public static function block_html($i){
		# the string we'll return
		$s = '';
		# plugin options
		extract(RO3_Options::$options);
		
		# make sure we have a title set before starting the block
		$titlename = 'title'.$i;		
		if(!($title = $$titlename)) return $s;
		
		# get other settings for this block
		## image
		$imgname = 'image'.$i;
		$image = isset($$imgname) ? $$imgname : '';
		
		## font awesome icon
		$faname = 'fa_icon'.$i;
		$fa_icon = isset($$faname) ? $$faname : '';

		## link
		$linkname = 'link'.$i;
		$link = isset($$linkname) ? $$linkname : '';
		
		## description
		$descname = 'description'.$i;
		$description = isset($$descname) ? $$descname : '';
		
		# store this block's settings in an array
		$block = array(
			'title' => $title,
			'image' => $image,
			'fa_icon' => $fa_icon,
			'link' => $link,
			'description' => $description,
		);
		
		# generate the HTML string
		$s .= "<div class='ro3-block {$i} " 
			. ( $style == 'drop-shadow' ? "shadow-container " : '')
			. ( $style == 'nested' ? 'nested-container' : '')
			. "'>";
			
			# styles
			## basic
			if(in_array($style, array('none', 'drop-shadow', 'nested', 'circle'))){
				$s .= self::block_html_basic($block);
			}
			## bar
			elseif('bar' == $style){
				$s .= self::block_html_bar($block);
			}
			## font awesome
			elseif('fa-icon' == $style){
				$s .= self::block_html_fa($block);
			}
			# read more
			if(('nested' != $style) && $link && isset(RO3_Options::$options['read_more_yes'])){
				$s .= '<p class="ro3-read-more"><a href="'.$link.'" '. (isset($main_color) ? 'style="color: '. $main_color .'"' : '' ) .'>Read More &raquo;</a></p>';
			}
		$s .= "</div>"; # .ro3-block
		return $s;
	} # end: block_html()
	
	# Basic block (none, drop-shadow, nested, circle)
	public static function block_html_basic($block){
		$s = '';
		extract($block);

		# image
		if($image){
			# if link exists, wrap it around the image
			if($link) $s .= "<a class='ro3-link " . ((RO3::$style == "none") ? "" : RO3::$style ) . "' href='{$link}'>";
				
				# open image tag
				$s .= "<img src='{$image}' ";

				# hover effect				
				$s .= self::get_hover_class_attribute();
			
				# close image tag
				$s .= "/>";
			if($link) $s .= "</a>";
		}
		# header (with link if it's set)
		$s .= "<div class='ro3-description'>";
		$s .= self::header_html($block);
		
		# description
		if($description)
			$s .= "<p>$description</p>";
		$s .= "</div>"; # .ro3-description
		return $s;
	}

	# block for Bar style
	public static function block_html_bar($block){
		$s = '';
		extract($block);
		
		#$s .= "<h2 style='border-bottom-color: ". $color ."'>" . ( $link ? "<a href='{$link}'>" : "" ) .$title . ($link ? "</a>" : "") . "</h2>";
		$s .= self::header_html($block);
		
		# image
		if($image){
			# if link exists, wrap it around the image
			if($link) $s .= "<a class='ro3-link " . ((RO3::$style == "none") ? "" : RO3::$style ) . "' href='{$link}'>";
				$s .= "<img src='{$image}' ";

					#hover
					$s .= self::get_hover_class_attribute();

				$s .= " />";
			if($link) $s .= "</a>";
		}
		# header (with link if it's set)
		$s .= "<div class='ro3-description'>";
		# description
		if($description)
			$s .= "<p>$description</p>";
		$s .= "</div>"; # .ro3-description
		return $s;
	}
	# block for Font Awesome style
	public static function block_html_fa($block){
		$s = '';
		extract($block);
		
		if($fa_icon) $s .= self::fa_icon_html($block);
		$s .= self::header_html($block);
		$s .= self::description_html($block);
		return $s;
	}
	
	# return header HTML, with link if necessary
	public static function header_html($block){
		extract($block);		
		$s = '<h2';
			if(RO3::$style == 'bar') $s .= ' style="border-bottom-color: '. RO3::$color .'; color: '. RO3::$color .'"';
		$s .= '>';
		$s .= $link ? "<a href='{$link}'>" : "";
		$s .= $title;
		$s .= $link ? "</a>" : "";
		$s .= '</h2>';
		return $s;	
	} # end: header_html()
	
	public static function description_html($block){
		$s = '';
		extract($block);
		if(!$block['description']) return $s;
		$s = '<div class="ro3-description">';
			$s .= '<p>'. $block['description'] .'</p>';
		$s .= '</div>';
		return $s;
	} # end: description_html()
	
	# return HTML for font awesome icon
	# we can be given a string or an array for a block
	public static function fa_icon_html($block){
		$size = RO3_Options::$options['fa_icon_size'];
		$color = RO3::$color;
		
		$fa_icon = is_string($block) ? $block : $block['fa_icon'];
		if(is_array($block)) extract($block);
		
		$s = '';
		if($link) $s .= '<a href="'. $link .'">';
		$s .= '<i ';
			$s .= 'style="color: '. $color .'; font-size: '. $size .'" ';
			$s .= 'class="fa '. self::pad_fa($fa_icon) . ' ';
			$s .= self::get_hover_effect();
			$s .= '"';
		$s .= '></i>';
		if($link) $s .= '</a>';
		return $s;
	}

	# prepend 'fa-' to font awesome icon name if necessary
	public static function pad_fa($str){
		if('fa-' != substr($str,0,3)) $str = 'fa-'.$str;
		return $str;
	}
	
	/**
	 * General helper functions
	 * 
	 * - get_hover_effect()
	 * - get_hover_class_attribute()
	 * - req_file()
	 * - clean_str_for_url()
	 * - clean_str_for_field()
	 * - get_field_array()
	 * - get_choice_array()
	 */

	/**
	 * Check if hover effect exists and is valid and return either the value or an empty string
	 *
	 * @since 	1.1.0
	 */
	public static function get_hover_effect() {
		
		if( ! isset( RO3_Options::$options['hover_effect'] ) ) return '';
		if( ! in_array( RO3_Options::$options['hover_effect'], self::$hover_effects ) ) return '';

		return RO3_Options::$options['hover_effect'];
	}

	/**
	 * Return the HTML class attribute text for the hover effect
	 *
	 * @since 	1.1.0
	 */
	public static function get_hover_class_attribute() {
		
		$hover = self::get_hover_effect();

		if( empty( $hover ) ) return '';

		return ' class="' . $hover . '" ';
	}

	# require a file, checking first if it exists
	static function req_file($path){ if(file_exists($path)) require_once $path; }
	# return a permalink-friendly version of a string
	static function clean_str_for_url( $sIn ){
		if( $sIn == "" ) return "";
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		$sOut = preg_replace( "/[^a-zA-Z0-9 -]/" , "",$sOut );	
		$sOut = preg_replace( "/--+/" , "-",$sOut );
		$sOut = preg_replace( "/ +- +/" , "-",$sOut );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );	
		$sOut = preg_replace( "/\s/" , "-" , $sOut );
		$sOut = preg_replace( "/--+/" , "-" , $sOut );
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "-" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;
	}
	static function clean_str_for_field($sIn){
		if( $sIn == "" ) return "";
		$sOut = trim( strtolower( $sIn ) );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );					
		$sOut = preg_replace( "/[^a-zA-Z0-9 -_]/" , "",$sOut );	
		$sOut = preg_replace( "/--+/" , "-",$sOut );
		$sOut = preg_replace( "/__+/" , "_",$sOut );
		$sOut = preg_replace( "/ +- +/" , "-",$sOut );
		$sOut = preg_replace( "/ +_ +/" , "_",$sOut );
		$sOut = preg_replace( "/\s\s+/" , " " , $sOut );	
		$sOut = preg_replace( "/\s/" , "-" , $sOut );
		$sOut = preg_replace( "/--+/" , "-" , $sOut );
		$sOut = preg_replace( "/__+/" , "_" , $sOut );
		$nWord_length = strlen( $sOut );
		if( $sOut[ $nWord_length - 1 ] == "-" || $sOut[ $nWord_length - 1 ] == "_" ) { $sOut = substr( $sOut , 0 , $nWord_length - 1 ); } 
		return $sOut;		
	}
	# Generate a label, value, etc. for any given choice 
	## input can be a string or array and a full, formatted array will be returned
	## If $field is a string we assume the string is 
	## if $field is an array we assume that at least a label exists
	## optionally, the parent field's name can be passed for better labelling
	static function get_field_array( $field, $parent_name = ''){
		$id = $parent_name ? $parent_name.'_' : '';
		if(!is_array($field)){
			$id .= self::clean_str_for_field($field);
			$out = array();
			$out['type'] = 'text';
			$out['label'] = $field;
			$out['value'] = $id;
			$out['id'] .= $id;
			$out['name'] = $id;
		}
		elseif(is_array($field)){
			# do nothing if we don't have a label
			if(!array_key_exists('label', $field)) return $field;
			
			$id .= array_key_exists('name', $field) ? $field['name'] : self::clean_str_for_field($field['label']);
			$out = $field;
			if(!array_key_exists('id', $out)) $out['id'] = $id;
			if(!array_key_exists('name', $out)) $out['name'] = $id;
			# make sure all choices are arrays
			if(array_key_exists('choices', $field)){
				$out['choices'] = self::get_choice_array($field);
			}
		}
		return $out;
	}
	# Get array of choices for a setting field
	## This allows choices to be set as strings or arrays with detailed properties, 
	## so that either way our options display function will have the data it needs
	static function get_choice_array($setting){
		extract($setting);
		if(!isset($choices)) return;
		$out = array();
		if(!is_array($choices)){
			$out[] = array(
				'id' => $name.'_'.self::clean_str_for_field($choices),
				'label' => $choices, 
				'value' => self::clean_str_for_field($choices)
			);
		}
		else{
			foreach($choices as $choice){
				if(!is_array($choice)){
					$out[] = array(
						'label' => $choice,
						'id' => $name . '_' . self::clean_str_for_field($choice),
						'value' => self::clean_str_for_field($choice)
					);
				}
				else{
					# if choice is already an array, we need to check for missing data
					if(!array_key_exists('id', $choice)) $choice['id'] = $name.'_'.self::clean_str_for_field($choice['label']);
					if(!array_key_exists('value', $choice)) $choice['value'] = $name.'_'.self::clean_str_for_field($choice['label']);
					$out[] = $choice;
				}
			}
		}
		return $out;
	} # end: get_choice_array()

} # end class RO3

# require files for plugin
foreach(RO3::$classes as $class){ RO3::req_file(ro3_dir("lib/class-{$class}.php")); }

# load static variables for RO3 class
RO3::$style = RO3_Options::$options['style'];
RO3::$color = RO3_Options::$options['main_color'];
