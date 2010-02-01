<?php
/*
Plugin Name: Super Simple Quotes
Plugin URI: http://wordpress.org/extend/plugins/super-simple-quotes/
Description: Display quotes that auto-refresh using javascript. 
Version: 1.0
Author: P&aring;l Brattberg
Author URI: http://www.subtree.se/
*/

// Thanks to <a href="http://planetozh.com/">planetozh</a> for an excellent blog!

add_action('admin_init', 'super_simple_quotes_init' );
add_action('admin_menu', 'super_simple_quotes_add_page');
add_action('wp_head', 'super_simple_quotes_emit_js');

function super_simple_quotes_init_method() {
  wp_enqueue_script('jquery');
}
add_action('init', 'super_simple_quotes_init_method');

// Get our options (with default values)
function super_simple_quotes_get_options() {
	$default_values = array(
    "use_refresh" => 1,
    "refresh_time" => 10,
    "element_reference" => "#text-3 .textwidget",
    "quotes"=>array(
      "If you try and take a cat apart to see how it works, the first thing you have on your hands is a non-working cat",
      "Driving a Porsche in London is like bringing a Ming vase to a football game",
      "A happy man is too satisfied with the present to dwell too much on the future"
    )
  );
  return get_option('super_simple_quotes', $default_values); 
}

// Init plugin options to white-list our options
function super_simple_quotes_init() {
	register_setting( 'super_simple_quotes_options', 'super_simple_quotes', 'super_simple_quotes_validate' );
}

// Add menu page
function super_simple_quotes_add_page() {
  add_menu_page(__('Simple Quotes'), __('Simple Quotes'), 'level_2', 'super_simple_quotes_options', 'super_simple_quotes_do_page');
}

// emit public-page code
function super_simple_quotes_emit_js() {
  //wp_enqueue_script('jquery');
  require_once(ABSPATH."/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
  
  $options = super_simple_quotes_get_options();
  $json_obj = new Moxiecode_JSON();
  $json_quotes = $json_obj->encode($options['quotes']);
  ?>
  <script type="text/javascript"> 
  jQuery.noConflict();
  var quotes = <?php echo $json_quotes ?>;
  var lastIndex = 0;
  function getQuoteIndex(lastIndex) {
    var randValue = Math.floor(Math.random() * quotes.length);
    if ((randValue == lastIndex) && (quotes.length > 1)) {
      return getQuoteIndex(lastIndex); // Another try
    } 
    return randValue;
  }
  function showQuote() {
    lastIndex = getQuoteIndex(lastIndex);
    jQuery("<?php echo $options['element_reference'] ?>:not(:animated)").fadeOut("slow", function () {
      jQuery("<?php echo $options['element_reference'] ?>").text(quotes[lastIndex]);
      jQuery("<?php echo $options['element_reference'] ?>").fadeIn("slow", function () {
        if (<?php echo ($options['use_refresh'] == 1)?'true':'false' ?>) {
          setTimeout("showQuote();", <?php echo ($options['refresh_time'] * 1000) ?>);
        } 
      });
    });
  } 
  jQuery(document).ready(function(){ showQuote(); });
  </script> 
<?php
}

// Draw the menu page itself
function super_simple_quotes_do_page() {
	?>
	<div class="wrap">
		<h2><?php _e('Super Simple Quotes') ?></h2>
		<form method="post" action="options.php">
			<?php settings_fields('super_simple_quotes_options'); ?>
			<?php $options = super_simple_quotes_get_options(); ?>
			<table class="form-table">
				<tr valign="top"><th scope="row"><?php _e('Use JavaScript refresh?') ?></th>
					<td><input name="super_simple_quotes[use_refresh]" type="checkbox" value="1" <?php checked('1', $options['use_refresh']) ?> /></td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e('Time until refresh in seconds') ?></th>
					<td><input name="super_simple_quotes[refresh_time]" type="text" value="<?php echo $options['refresh_time'] ?>" /></td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e('Element to add quotes to') ?></th>
					<td><input name="super_simple_quotes[element_reference]" type="text" value="<?php echo $options['element_reference'] ?>" /></td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e('Quotes (separate by new lines)') ?></th>
					<td><textarea name="super_simple_quotes[quotes]" cols="150" rows="25"><?php echo implode("\n", $options['quotes']) ?></textarea></td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save changes') ?>" />
			</p>
		</form>
	</div>
	<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function super_simple_quotes_validate($input) {
	// Value is either 0 or 1
	$input['use_refresh'] = ( $input['use_refresh'] == 1 ? 1 : 0 );
  
  // Value is numeric (or default value 10)
	$input['refresh_time'] = (int)( is_numeric($input['refresh_time']) ? $input['refresh_time'] : 10 );
  
	// Must be safe text with no HTML tags
	$input['element_reference'] =  $input['element_reference'];
  
	// Must be safe text with no HTML tags, split on new-line
	$input['quotes'] =  explode("\n", wp_filter_nohtml_kses($input['quotes']));
	
	return $input;
}
