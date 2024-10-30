<?php
/**
 * Handles the display, saving, and init/retrieval of options for the plugin
 * Generates HTML for the main plugin settings page
 *
 * Static variables are set after class definition below
 *
 * @since 	1.0.0
 */
class RO3_Options{

	/**
	 * The available settings for the plugin.
	 * 
	 * @type array 	$settings{
	 *		@type array ...,
	 * 		@type array ...,
	 *		...
	 * }
 	 */
	static $settings;
	
	/**
	 * Saved options by the user. In addition to the defaults below, the array contains
	 * a key for each corresponding `name` in the self::$settings array
	 *
	 * Default values:
	 *
	 * @type array 	$options{
	 *
	 *		@type string 	$num_blocks		The number of blocks to use (3 or 4) (Default: '3')
	 * 		@type string  	$style 			The display style for the plugin (Default: 'none')
	 * 		@type string 	$main_color		A valid CSS color to use as the main color on the front end (Default: '#333')
	 * 		@type string 	$fa_icon_size 	A valid CSS font size for the Font Awesome Icon, if used (Default: '2em')
	 * }
	 */
	static $options = array();
	
	/**
	 * Class methods
	 *
	 * 		- do_settings_field()
	 * 		- text_field()
	 * 		- textarea_field()
	 * 		- checkbox_field()
	 *		- select_field()
	 * 		- radio_field()
	 * 		- image_field()
	 * 		- register_settings()
	 * 		- settings_page()
	 */
	
	/**
	 * Display a plugin settings form element
	 *
	 * @param 	string|array 	$setting{
	 *
	 *		Use string for simple fields. Array will contain information about the
	 *		setting that we'll use to create the form element
	 *
	 *		@type 	string 			$label 		Required. The label for the form element	 
	 * 		@type 	string 			$name 		Optional. The HTML name attribute. Will be auto-generated from label if empty
	 * 		@type 	string 			$id 		Optional. The HTML `id` attribute for the form element. Will be auto-generated from label if empty
	 *		@type 	string 			$type 		Optional. The type of form element to display (text|textarea|checkbox|select|single-image|radio) (Default: 'text')
	 * 		@type 	string 			$value 		Optional. The value of the HTML `value` attribute
	 * 		@type 	array|string 	$choices 	Optional. The choices for the form element (for select, radio, checkbox)
	 * }
	 *
	 * @since 	1.0.0
	 */
	public static function do_settings_field($setting){

		# auto-complete the setting keys that may not be set
		$setting = RO3::get_field_array($setting);

		extract(RO3_Options::$options);

		# call one of several functions based on what type of field we have
		switch($setting['type']){
			case "textarea":
				self::textarea_field($setting);
			break;
			case "checkbox":
				self::checkbox_field($setting);
			break;
			case 'select':
				self::select_field($setting);
			break;
			case "single-image":
				self::image_field($setting);
			break;
			case "radio":
				self::radio_field($setting);
			break;
			default: self::text_field($setting);
		}
		# field description
		if(array_key_exists('description', $setting)){
		?>
			<p class='description'><?php echo $setting['description']; ?></p>
		<?php
		}
		# preview for different RO3 styles
		if($setting['name'] == 'style'){
		?>
			<div id="ro3-preview">		
			<?php
				foreach($setting['choices'] as $a){
					$choice = $a['value'];
				?>
				<div 
					id="preview-<?php echo $choice; ?>"
					style="display: <?php echo ($style==$choice)?'block':'none'; ?>"
				>
					<?php if('nested' == $choice){
					?>
						<p><em>Note: this choice works best with a short description</em></p>
					<?php
					}
					?>
					<img src="<?php echo ro3_url('/images/'. $choice .'.jpg'); ?>" />
				</div>			
				<?php
				}
				?>
			</div>
		<?php
		}
		# Subcontent for 'Existing Content' radio buttons
		if(strpos($setting['name'], 'post_type') === 0){
			# get the section number 
			$section = $setting['section'];
			
			# 'Clear' link
		?>
			<a href="javascript:void(0)" class='clear-post-type-select' data-section="<?php echo $section; ?>">Clear</a>
		<?php	
			# Post select area for specific post type
		?>
			<div 
				id="post-select-<?php echo $section; ?>"
				class='post-select' 
				style="display: <?php echo (!empty(RO3_Options::$options['post_type'.$section])) ? 'block' : 'none';?>;"
			><?php RO3::select_post_for_type(RO3_Options::$options['post_type'.$section], $section); ?>
			</div>
		<?php
		}
		# Child fields (for conditional logic)
		if(array_key_exists('choices', $setting)){
			$choices = RO3::get_choice_array($setting);
			# keep track of which fields we've displayed (in case two choices have the same child)
			$aKids = array();

			# Loop through choices and display and children
			foreach($choices as $choice){
				if(array_key_exists('children', $choice)){
					foreach($choice['children'] as $child_setting){
						# add this child to the array of completed child settings
						if(!in_array($child_setting['name'], $aKids)){
							$aKids[] = $child_setting['name'];
							# note the child field div is hidden unless the parent option is selected
						?><div 
							id="child_field_<?php echo $child_setting['name']; ?>"
							style="display: <?php echo (RO3_Options::$options[$setting['name']] == $choice['value']) ? 'block' : 'none'?>"
						>
							<h4><?php echo $child_setting['label']; ?></h4>
							<?php self::do_settings_field($child_setting); ?>
						</div>
						<?php
						}
					}
				} # end if: choice has children

			} # end foreach: choices

		} # end if: setting has choices

	} # end: do_settings_field()

	/**
	 * Display a text input element
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @return 	null
	 * @since 	1.0.0
	 */
	public static function text_field( $setting ) {
		extract($setting);
		?><input 
			id="<?php echo $name; ?>" 
			name="ro3_options[<?php echo $name; ?>]" 
			class="regular-text <?php if(isset($class)) echo $class; ?>" 
			type='text' 
			value="<?php echo self::$options[$name]; ?>" 
		/>
		<?php	
	} # end: text_field()

	/**
	 * Display a textarea element
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @return 	null
	 * @since 	1.0.0
	 */
	public static function textarea_field($setting){
		extract($setting);
		?><textarea id="<?php echo $name; ?>" name="ro3_options[<?php echo $name; ?>]" 
			cols='40' rows='7' class='<?php echo $class ? $class : ''; ?>'><?php echo self::$options[$name]; ?></textarea>
		<?php
	} # end: textarea_field()

	/**
	 * Display one or more checkboxes
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @return 	null
	 * @since 	1.0.0
	 */
	public static function checkbox_field($setting){
		extract($setting);
		foreach($choices as $choice){
		?><label class='checkbox' for="<?php echo $choice['id']; ?>">
			<input 
				type='checkbox'
				id="<?php echo $choice['id']; ?>"
				name="ro3_options[<?php echo $choice['id']; ?>]"
				value="<?php echo $choice['value']; ?>"
				class="<?php if(array_key_exists('class', $setting)) echo $setting['class']; ?>"
				<?php checked(true, array_key_exists($choice['id'], self::$options)); ?>						
			/>&nbsp;<?php echo $choice['label']; ?> &nbsp; &nbsp;
		</label>
		<?php
		}
	
	} # end: checkbox_field()

	/**
	 * Display a <select> dropdown element
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @return 	null
	 * @since 	1.0.0
	 */
	public static function select_field($setting){
		extract($setting);
		?><select 
			id="<?php echo $name; ?>"
			name="ro3_options[<?php echo $name; ?>]"
			<?php if(isset($class)) echo  "class='{$class}'"; ?>
			<?php
				if(array_key_exists('data', $setting)){
					foreach($setting['data'] as $k => $v){
						echo " data-{$k}='{$v}'";
					}
				}
			?>
		>
			<?php 
				# if we are given a string for $choices (i.e. single choice)
				if(is_string($choices)) {
					?><option 
						value="<?php echo RO3::clean_str_for_field($choices); ?>"
						<?php selected(RO3_Options::$options[$name], RO3::clean_str_for_field($choice) ); ?>
					><?php echo $choices; ?>
					</option>
				<?php
				}
				# if $choices is an array
				elseif(is_array($choices)){
					foreach($choices as $choice){
						# if $choice is a string
						if(is_string($choice)){
							$label = $choice;
							$value = RO3::clean_str_for_field($choice);
						}
						# if $choice is an array
						elseif(is_array($choice)){
							$label = $choice['label'];
							$value = isset($choice['value']) ? $choice['value'] : RO3::clean_str_for_field($choice['label']);
						}
					?>
						<option 
							value="<?php echo $value; ?>"
							<?php if(isset(RO3_Options::$options[$name])) selected(RO3_Options::$options[$name], $value ); ?>					
						><?php echo $label; ?></option>
					<?php
					} # end foreach: $choices
				} # endif: $choices is an array
			?>
			
		</select><?php
	
	} # end: select_field()

	/**
	 * Display a group of radio buttons
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @return 	null
	 * @since 	1.0.0
	 */
	public static function radio_field($setting){
		extract($setting);
		$choices = RO3::get_choice_array($setting);
		foreach($choices as $choice){
				$label = $choice['label']; 
				$value = $choice['value'];
			?><label class='radio' for="<?php echo $choice['id']; ?>">
				<input type="radio" id="<?php echo $choice['id']; ?>" 
				name="ro3_options[<?php echo $name; ?>]" 
				value="<?php echo $value; ?>"
				class="<?php if(array_key_exists('class', $setting)) echo $setting['class']; ?>"
				<?php
				# add data attributes
				if(array_key_exists('data', $choice)){
					foreach($choice['data'] as $k => $v){
						echo " data-{$k}='{$v}'";
					}
				}
				# add checked property if we need to
				if(isset(self::$options[$name])) checked($value, self::$options[$name]); ?>
				autocomplete='off'
			/>&nbsp;<?php echo $label; ?></label>&nbsp;&nbsp;
			<?php
		}

	} # end: radio_field()

	/**
	 * Display an image upload element that uses the WP Media browser
	 *
	 * @param 	array 	$setting 	See `do_settings_field()`. Has been filtered through `RO3::get_field_array()`
	 * @return 	null
	 * @since 	1.0.0
	 */
	public static function image_field($setting){
		# this will set $name for the field
		extract($setting);
		# current value for the field
		$value = self::$options[$name];		
		?><input 
			type='text'
			id="<?php echo $name; ?>" 
			class='regular-text text-upload <?php echo $class ? $class : ''; ?>'
			name="ro3_options[<?php echo $name; ?>]"
			value="<?php if($value) echo esc_url( $value ); ?>"
		/>		
		<input 
			id="media-button-<?php echo $name; ?>" type='button'
			value='Choose/Upload image'
			class=	'button button-primary open-media-button single'
		/>
		<div id="<?php echo $name; ?>-thumb-preview" class="ro3-thumb-preview">
			<?php if($value){ ?><img src="<?php echo $value; ?>" /><?php } ?>
		</div>
		<?php

	} # end: image_field()

	/**
	 * Validate plugin settings fields when saved
	 *
	 * @param 	array 	$input 		The array of options being saved
	 * @since 	1.0.0
	 */
	public static function options_validate( $input ) { return $input; }


	/**
	 * Register settings and sections for the main plugin settings with the WP Settings API
	 *
	 * @since 	1.0.0
	 */
	public static function register_settings(){

		# register the main option group for the plugin
		register_setting( 'ro3_options', 'ro3_options', array( 'RO3_Options', 'options_validate' ) );

		# register the main plugin options sections
		add_settings_section('ro3_main', '', array( 'RO3_Options', 'main_section_text' ), 'ro3_settings');
		
		# set up a section for each block
		$n = 4;
		for( $i = 1; $i <= $n; $i++ ) {
			add_settings_section('ro3_'.$i, 'Block ' . $i, '', 'ro3_settings');
		}

		/**
		 * Register settings fields
		 */

		# get choices for custom post types
		$pt_args = array(
			'_builtin' => false,
			'public' => true
		);
		$pts = get_post_types( $pt_args, 'objects' );

		# loop through settings and register
		foreach( RO3_Options::$settings as $setting ) {

			# add choices for custom post types
			foreach( $pts as $pt ) {

				# make sure we have published posts for this post type
				if( ! get_posts(
					array(
						'post_type' => $pt->name, 'post_status' => 'publish'
					)
				) ) continue;

				# set the post type choices if this setting calls for it
				if( strpos( $setting['name'], 'post_type' ) === 0 ) {
					$setting['choices'][] = array(
						'label' => $pt->labels->name, 
						'value' => $pt->name,
						'data' => array('section' => $setting['section'])
					);
				}

			} # end foreach: post types

			# register the settings field with the WP Settings API
			add_settings_field($setting['name'], $setting['label'], array( 'RO3_Options', 'do_settings_field' ), 'ro3_settings', 'ro3_' . $setting['section'], $setting);
		
		} # end foreach: plugin settings
	
	} # end: register_settings()

	/**
	 * Render HTML for the main plugin settings page
	 *
	 * @since 	1.0.0
	 */
	public static function settings_page(){
		?><div>
			<h2>Rule of Three Settings</h2>
			<form action="options.php" method="post">
			<?php 
				settings_fields('ro3_options');

				/**
				 * Main Section
				 */
				self::main_section_text();
				?>
				<table class='form-table'>
				<?php

					do_settings_fields('ro3_settings', 'ro3_main');
				?>
				</table>
				<?php

				/**
				 * Block content sections
				 */
				$n = 4;
				foreach( range( 1, $n ) as $i ) {

					?>
					<div class='ro3-settings-block' data-block='<?php echo $i; ?>'>
						<hr />
						<h2>Block <?php echo $i; ?></h2>
						<table class='form-table'>
						<?php

							do_settings_fields('ro3_settings', 'ro3_' . $i );
						?>
						</table>
					</div>
					<?php
				}

				submit_button(); 
			?>
			</form>
		</div><?php

	} # end: settings_page()

	/**
	 * The main plugin settings description
	 *
	 * @since 	1.0.0
	 */
	public static function main_section_text(){
	?>
		<p>Define the blocks here that will show up when you use this shortcode:</p>
		<p><kbd>[rule-of-three]</kbd></p>
	<?php
	}

} # end class: RO3_Options

/**
 * Initialize static variables
 *
 * - set up backend available settings
 * - get saved options and set up defaults
 */

/**
 * Set up backend available settings
 */

# settings that will come in 4's (one for each potential block)
$n = 4;
$options = array(
	array('name' => 'post_type', 'type' => 'radio', 'label' => 'Use Existing Content',
		'class' => 'ro3-post-type-select',
		'choices' => array(
			array('label' => 'Post', 'value' => 'post'),
			array('label' => 'Page', 'value' => 'page'),
		),
	),
	array('name' => 'image', 'type' => 'single-image', 'label' => 'Image'),
	array('name' => 'fa_icon', 'type' => 'text', 'label' => 'Font Awesome Icon',
		'description' => 
			'Enter the name of the icon (Ex: <code style="font-style: normal; font-weight: bold;">coffee</code>, <code style="font-style: normal; font-weight: bold;">bed</code>, etc.)<br />
			See the list of <a target="_blank" href="http://fortawesome.github.io/Font-Awesome/icons/">available options</a>.',
		'class' => 'fa_icon'
	),
	array('name' => 'title', 'type' => 'text', 'label' => 'Title'),
	array('name' => 'description', 'type' => 'textarea', 'label' => 'Description'),
	array('name' => 'link', 'type' => 'text', 'label' => 'Link')
);

# feed the above settings into RO3_Options::$settings in 4's
RO3_Options::$settings = array();
for($i = 1; $i <= $n; $i++){

	foreach($options as $option){
		// set the section (ro3_1, ro3_2, ro3_3) and name (title1, description1, etc )
		$option['section'] = $i;

		# the class shouldn't have an index
		$option['class'] = $option['name'] . (array_key_exists('class', $option) ? ' ' . $option['class'] : '');

		# the option name should have an index
		$option['name'] = $option['name'] . $i;

		# Add a `data-section=$i` attribute to each choice for this option if choices exist
		if(array_key_exists('choices', $option)){
			foreach($option['choices'] as $k => $v){
				$option['choices'][$k]['data'] = array('section' => $i);
			}
		}

		RO3_Options::$settings[] = $option;

	} # end foreach: grouped options

} # end for: $i < $n

/**
 * Load settings for the main settings section (i.e. the settings that don't come in 4's)
 */

# Whether to use rule of three or rule of four
RO3_Options::$settings[] = array(
	'name' => 'num_blocks', 'type' => 'select', 'label' => 'Rule Of ...',
	'section' => 'main',
	'choices' => array(
		array( 'value' => '3', 'label' => 'Three' ),
		array( 'value' => '4', 'label' => 'Four' ),
	)
);

# Which style to use for the rule of three
RO3_Options::$settings[] = array(
	'name'=>'style', 'type'=>'radio', 'label' => 'Style', 'section' => 'main',
	'choices' => array(
		array('value'=>'none', 'label'=>'None'),
		array('value' => 'drop-shadow', 'label' => 'Drop Shadow'),
		array('value' => 'nested', 'label' => 'Nested'),
		array('value' => 'circle', 'label' => 'Circle'),
		array('value' => 'bar', 'label' => 'Bar',),
		array('value' => 'fa-icon', 'label' => 'Font Awesome'),		
	)
);

# Which hover state to use
RO3_Options::$settings[] = array(
	'name' => 'hover_effect',
	'type' => 'select',
	'choices' => array( 
		array( 'value' => '', 'label' => 'None' ), 
		array( 'value' => 'hvr-grow', 'label' => 'Grow' ), 
		array( 'value' => 'hvr-float-shadow', 'label' => 'Float Shadow' ), 
	),
	'label' => 'Hover Effect',
	'section' => 'main',
);

RO3_Options::$settings[] = array(
	'name' => 'main_color', 'type' => 'text', 'class' => 'color-picker', 'label' => 'Main Color', 'section' => 'main'
);
RO3_Options::$settings[] = array(
	'name' => 'read_more', 'type' => 'checkbox', 'label' => 'Show "Read More"', 'section' => 'main',
	'choices' => 'Yes'
);
RO3_Options::$settings[] = array(
	'name' => 'fa_icon_size', 'type' => 'text', 'label' => 'Font Awesome Icon Size', 'section' => 'main',
	'description' => 
		'Please enter a valid CSS value like <code>24px</code> or <code>2em</code>.<br />
		Note that <code>em\'s</code> may generate a different preview size here than on the front end.',
);

/**
 * Get saved options and define defaults
 */

RO3_Options::$options = get_option('ro3_options');

# make sure each setting has a key in the main options (even if empty)
if( ! empty( RO3_Options::$options ) ) foreach( RO3_Options::$settings as $setting ) {
	if( empty( RO3_Options::$options[ $setting['name'] ] ) ) {
		RO3_Options::$options[ $setting['name'] ] = '';
	}
}

# if no options have been saved yet, make sure at least all keys are present in CPTD_Options::$options
if(!RO3_Options::$options){

	# loop through settings
	foreach(RO3_Options::$settings as $setting){

		# add key to options
		RO3_Options::$options[$setting['name']] = '';
	}
}

# set the defaults that we want to implement
if( ! RO3_Options::$options['num_blocks'] ) RO3_Options::$options['num_blocks'] = '3';
if( ! RO3_Options::$options['style'] ) RO3_Options::$options['style'] = 'none';
if( ! RO3_Options::$options['main_color'] ) RO3_Options::$options['main_color'] = '#333';
if( ! RO3_Options::$options['fa_icon_size'] ) RO3_Options::$options['fa_icon_size'] = '2em';
