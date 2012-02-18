<?php
/**
* Error API.
*
* Contains the SQ_Error class and the is_sq_error() function.
*
*/

/**
* SQ_Error class.
*
* Container for checking for errors and error messages. Return
* SQ_Error and use {@link is_sq_error()} to check if this class is returned.
* Many core functions pass this class in the event of an error and
* if not handled properly will result in code errors.
*
*/
class SQ_Error {
	/**
	 * Stores the list of errors.
	 *
	 * @var array
	 * @access private
	 */
	private $errors = array();

	/**
	 * Stores the list of data for error codes.
	 *
	 * @var array
	 * @access private
	 */
	private $error_data = array();

	/**
	 * Constructor - Sets up error message.
	 *
	 * If code parameter is empty then nothing will be done. It is possible to
	 * add multiple messages to the same code, but with other methods in the
	 * class.
	 *
	 * All parameters are optional, but if the code parameter is set, then the
	 * data parameter is optional.
	 *
	 * @param string|int $code Error code
	 * @param string $message Error message
	 * @param mixed $data Optional. Error data.
	 * @return SQ_Error
	 */
	function __construct($code = '', $message = '', $data = '') {
		if ( empty($code) )
			return;

		$this->errors[$code][] = $message;

		if ( ! empty($data) )
			$this->error_data[$code] = $data;
	}

	/**
	 * Retrieve all error codes.
	 *
	 * @access public
	 *
	 * @return array List of error codes, if available.
	 */
	function get_error_codes() {
		if ( empty($this->errors) )
			return array();

		return array_keys($this->errors);
	}

	/**
	 * Retrieve first error code available.
	 *
	 * @access public
	 *
	 * @return string|int Empty string, if no error codes.
	 */
	function get_error_code() {
		$codes = $this->get_error_codes();

		if ( empty($codes) )
			return '';

		return $codes[0];
	}

	/**
	 * Retrieve all error messages or error messages matching code.
	 *
	 * @param string|int $code Optional. Retrieve messages matching code, if exists.
	 * @return array Error strings on success, or empty array on failure (if using code parameter).
	 */
	function get_error_messages($code = '') {
		// Return all messages if no code specified.
		if ( empty($code) ) {
			$all_messages = array();
			foreach ( (array) $this->errors as $code => $messages )
				$all_messages = array_merge($all_messages, $messages);

			return $all_messages;
		}

		if ( isset($this->errors[$code]) )
			return $this->errors[$code];
		else
			return array();
	}

	/**
	 * Get single error message.
	 *
	 * This will get the first message available for the code. If no code is
	 * given then the first code available will be used.
	 *
	 * @param string|int $code Optional. Error code to retrieve message.
	 * @return string
	 */
	function get_error_message($code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();
		$messages = $this->get_error_messages($code);
		if ( empty($messages) )
			return '';
		return $messages[0];
	}

	/**
	 * Retrieve error data for error code.
	 *
	 * @param string|int $code Optional. Error code.
	 * @return mixed Null, if no errors.
	 */
	function get_error_data($code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		if ( isset($this->error_data[$code]) )
			return $this->error_data[$code];
		return null;
	}

	/**
	 * Append more error messages to list of error messages.
	 *
	 * @access public
	 *
	 * @param string|int $code Error code.
	 * @param string $message Error message.
	 * @param mixed $data Optional. Error data.
	 */
	function add($code, $message, $data = '') {
		$this->errors[$code][] = $message;
		if ( ! empty($data) )
			$this->error_data[$code] = $data;
	}

	/**
	 * Add data for error code.
	 *
	 * The error code can only contain one error data.
	 *
	 * @param mixed $data Error data.
	 * @param string|int $code Error code.
	 */
	function add_data($data, $code = '') {
		if ( empty($code) )
			$code = $this->get_error_code();

		$this->error_data[$code] = $data;
	}
}

/**
 * Check whether variable is a SQ_Error.
 *
 * Looks at the object and if a SQ_Error class. Does not check to see if the
 * parent is also SQ_Error, so can't inherit SQ_Error and still use this
 * function.
 *
 * @param mixed $thing Check if unknown variable is SQ_Error object.
 * @return bool True, if SQ_Error. False, if not SQ_Error.
 */
function is_sq_error($thing) {
	if ( is_object($thing) && is_a($thing, 'SQ_Error') )
		return true;
	return false;
}

?>