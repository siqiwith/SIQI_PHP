<?php
/**
 * Parses a string into variables to be stored in an array.
 *
 * Uses {@link http://www.php.net/parse_str parse_str()} and stripslashes if
 * {@link http://www.php.net/magic_quotes magic_quotes_gpc} is on.
 *
 * @param string $string The string to be parsed.
 * @param array $array Variables will be stored in this array.
 */
function sq_parse_str( $string, &$array ) {
	parse_str( $string, $array );
	if ( get_magic_quotes_gpc() )
		$array = stripslashes_deep( $array );
}

/**
 * Merge user defined arguments into defaults array.
 *
 * This function is used throughout the system to allow for both string or array
 * to be merged into another array.
 *
 * @param string|array $args Value to merge with $defaults
 * @param array $defaults Array that serves as the defaults.
 * @return array Merged user defined values with defaults.
 */
function sq_parse_args( $args, $defaults = '' ) {
	if ( is_object( $args ) )
		$r = get_object_vars( $args );
	elseif ( is_array( $args ) )
		$r =& $args;
	else
		sq_parse_str( $args, $r );

	if ( is_array( $defaults ) )
		return array_merge( $defaults, $r );
	return $r;
}

/**
 * Kill system execution and display HTML message with error message.
 *
 * This function complements the die() PHP function. The difference is that
 * HTML will be displayed to the user. It is recommended to use this function
 * only, when the execution should not continue any further. It is not
 * recommended to call this function very often and try to handle as many errors
 * as possible silently.
 *
 * @param string $message Error message.
 * @param string $title Error title.
 * @param string|array $args Optional arguments to control behavior.
 */
function sq_die( $message, $title = '', $args = array() ) {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		die('-1');
	$function = '_default_sq_die_handler';
	call_user_func( $function, $message, $title, $args );
}

/**
 * Kill system execution and display HTML message with error message.
 *
 * This is the default handler for sq_die if you want a custom one for your
 * site then you can overload using the sq_die_handler filter in sq_die
 * 
 * @access private
 *
 * @param string|SQ_Error $message Error message.
 * @param string $title Error title.
 * @param string|array $args Optional arguments to control behavior.
 */
function _default_sq_die_handler( $message, $title = '', $args = array() ) {
	$defaults = array( 'response' => 500 );
	$r = sq_parse_args($args, $defaults);

	$have_gettext = function_exists('__');

	if ( function_exists( 'is_sq_error' ) && is_sq_error( $message ) ) {
		if ( empty( $title ) ) {
			$error_data = $message->get_error_data();
			if ( is_array( $error_data ) && isset( $error_data['title'] ) )
				$title = $error_data['title'];
		}
		$errors = $message->get_error_messages();
		switch ( count( $errors ) ) :
		case 0 :
			$message = '';
		break;
		case 1 :
			$message = "<p>{$errors[0]}</p>";
			break;
		default :
			$message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
		break;
		endswitch;
	} elseif ( is_string( $message ) ) {
		$message = "<p>$message</p>";
	}

	if ( isset( $r['back_link'] ) && $r['back_link'] ) {
		$back_text = $have_gettext? __('&laquo; Back') : '&laquo; Back';
		$message .= "\n<p><a href='javascript:history.back()'>$back_text</a></p>";
	}
	
	//Clean the output buffer. It's quite dirty, not sure how to do it...
	ob_clean();
	?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title ?></title>
<style type="text/css">
</style>
</head>
<body id="error-page">
<?php echo $message; ?>
</body>
</html>
<?php
die();
}
?>