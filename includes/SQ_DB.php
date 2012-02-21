<?php
include_once 'config.php';
include_once 'errors.php';
include_once 'SQ_Exception.php';

/**
 * Database Access Abstraction Object
 */
class SQ_DB{
	/**
	 * Count of rows returned by previous query
	 *
	 * @var int
	 */
	private $num_rows = 0;
	
	/**
	 * Count of affected rows by previous query
	 *
	 * @var int
	 */
	private $rows_affected = 0;
	
	/**
	 * The ID generated for an AUTO_INCREMENT column by the previous query (usually INSERT).
	 *
	 * @var int
	 */
	public $insert_id = 0;
	
	/**
	 * Saved result of the last query made
	 *
	 * @var array
	 */
	private $last_query;
	
	/**
	 * Results of the last query made
	 *
	 * @var array|null
	 */
	private $last_result;
	
	/**
	 * Saved info on the table column
	 *
	 * @var array
	 */
	private $col_info;
	
	/**
	 * Saved queries that were executed
	 *
	 * @var array
	 */
	private $queries;
	
	/**
	 * Whether the database queries are ready to start executing.
	 *
	 * @var bool
	 */
	private $ready = false;
	
	/**
	 * @var array
	 */
	public $field_types = array();
	
	/**
	 * Database Username
	 *
	 * @var string
	 */
	private $dbuser;
	
	/**
	 * Database Password
	 * 
	 * @var string
	 */
	private $dbpassword;
	
	/**
	 * Database Name
	 *
	 * @var string
	 */
	private $dbname;
	
	/**
	 * Database Host
	 *
	 * @var string
	 */
	private $dbhost;
	
	/**
	 * A textual description of the last query/get_row/get_var call
	 *
	 * @var string
	 */
	public $func_call;
	
	/**
	 * Connects to the database server and selects a database
	 *
	 * PHP5 style constructor for compatibility with PHP5. Does
	 * the actual setting up of the class properties and connection
	 * to the database.
	 *
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 * @param string $dbhost MySQL database host
	 */
	function __construct($dbuser, $dbpassword, $dbname, $dbhost){
		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;
		
		try{
			$this->db_connect();
		}catch(SQ_Exception $e){
			print_error($e);
		}
	}
	
	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @return bool true
	 */
	function __destruct() {
		return true;
	}
	
	/**
	 * Connect to and select database
	 *
	 * @return null
	 */
	function db_connect(){
		if(SQ_DEBUG){
			$this->dbh = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword, true);
		}else{
			$this->dbh = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword, true);
		}
		
		if (!$this->dbh){
			$error_message = sprintf(SQ_DB_CONN_ERROR_MESSAGE, $this->dbhost, $this->dbuser);
			throw new SQ_Exception($error_message, SQ_DB_CONN_ERROR_CODE);
		}
		$this->ready = true;
		
		try{
			$this->select($this->dbname, $this->dbh);
		}catch(SQ_Exception $e){
			throw $e;
		}
	}
	
	/**
	 * Selects a database using the current database connection.
	 *
	 * The database name will be changed based on the current database
	 * connection. On failure, return false.
	 *
	 * @param string $db MySQL database name
	 * @param resource $dbh Optional link identifier.
	 * @return null
	 */
	function select($db, $dbh = null){
		if (is_null($dbh)){
			$dbh = $this->dbh;
		}
		if (!@mysql_select_db( $db, $dbh )){
			$this->ready = false;
			$error_message = sprintf(SQ_DB_SELECT_TABLE_ERROR_MESSAGE, $db);
			throw new SQ_Exception($message, SQ_DB_SELECT_TABLE_ERROR_CODE);
		}
	}
	
	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query($query){
		if(!$this->ready){
			return false;
		}
		
		$return_val = 0;
		$this->flush();
	
		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";
	
		// Keep track of the last query for debug..
		$this->last_query = $query;
	
		if(defined('SQ_DEBUG') && SQ_DEBUG){
			$this->timer_start();
		}
	
		$this->result = @mysql_query($query, $this->dbh);
		$this->num_queries++;
	
		if(defined('SQ_DEBUG') && SQ_DEBUG){
			$this->queries[] = array($query, $this->timer_stop(), $this->get_caller());
		}
	
		// If there is an error then take note of it..
		if($this->last_error = mysql_error( $this->dbh )){
			throw(new SQ_Exception($this->last_error, SQ_MYSQL_ERROR_CODE));
		}
	
		if(preg_match( '/^\s*(create|alter|truncate|drop) /i', $query)){
			$return_val = $this->result;
		}elseif(preg_match( '/^\s*(insert|delete|update|replace) /i', $query)){
			$this->rows_affected = mysql_affected_rows( $this->dbh );
			// Take note of the insert_id
			if(preg_match('/^\s*(insert|replace) /i', $query)){
				$this->insert_id = mysql_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		}else{
			// Select...
			$i = 0;
			while($i < @mysql_num_fields( $this->result )){
				$this->col_info[$i] = @mysql_fetch_field( $this->result );
				$i++;
			}
			$num_rows = 0;
			while($row = @mysql_fetch_object( $this->result)){
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}
	
			@mysql_free_result($this->result);
	
			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val = $num_rows;
		}

		return $return_val;
	}
	
	/**
	 * Update a row in the table
	 *
	 * <code>
	 * SQ_DB::update( 'table', array( 'column' => 'foo', 'field' => 'bar' ), array( 'ID' => 1 ) )
	 * SQ_DB::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
	 * </code>
	 *
	 * @param string $table table name
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where.  A format is one of '%d', '%f', '%s' (integer, float, string).  If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	function update($table, $data, $where, $format = null, $where_format = null){
		if(!is_array($data) || !is_array($where)){
			return false;
		}
	
		$formats = $format = (array)$format;
		$bits = $wheres = array();
		foreach((array) array_keys($data) as $field){
			if (!empty($format)){
				$form = ($form = array_shift($formats)) ? $form : $format[0];
			}elseif(isset($this->field_types[$field])){
				$form = $this->field_types[$field];
			}else{
				$form = '%s';
			}
			$bits[] = "`$field` = {$form}";
		}
	
		$where_formats = $where_format = (array)$where_format;
		foreach((array)array_keys($where) as $field){
			if (!empty($where_format)){
				$form = ($form = array_shift($where_formats)) ? $form : $where_format[0];
			}elseif(isset($this->field_types[$field])){
				$form = $this->field_types[$field];
			}else{
				$form = '%s';
			}
			$wheres[] = "`$field` = {$form}";
		}
	
		$sql = "UPDATE `$table` SET " . implode( ', ', $bits ) . ' WHERE ' . implode( ' AND ', $wheres );
		return $this->query($this->prepare($sql, array_merge(array_values($data), array_values($where))));
	}
	
	/**
	 * Insert a row into a table.
	 *
	 * <code>
	 * SQ_DB::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * SQ_DB::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	function insert($table, $data, $format = null){
		return $this->_insert_replace_helper($table, $data, $format, 'INSERT');
	}
	
	/**
	 * Replace a row into a table.
	 *
	 * <code>
	 * SQ_DB::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * SQ_DB::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function replace($table, $data, $format = null){
		return $this->_insert_replace_helper($table, $data, $format, 'REPLACE');
	}
	
	/**
	 * Helper function for insert and replace.
	 *
	 * Runs an insert or replace query based on $type argument.
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs).  Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param string $type Optional. What type of operation is this? INSERT or REPLACE. Defaults to INSERT.
	 * @return int|false The number of rows affected, or false on error.
	 */
	private function _insert_replace_helper($table, $data, $format = null, $type = 'INSERT'){
		if (!in_array(strtoupper($type), array('REPLACE', 'INSERT'))){
			return false;
		}
		$formats = $format = (array) $format;
		$fields = array_keys($data);
		$formatted_fields = array();
		foreach($fields as $field){
			if(!empty($format)){
				$form = ($form = array_shift($formats)) ? $form : $format[0];
			}elseif(isset($this->field_types[$field])){
				$form = $this->field_types[$field];
			}else{
				$form = '%s';
			}
			$formatted_fields[] = $form;
		}
		$sql = "{$type} INTO `$table` (`" . implode('`,`', $fields) . "`) VALUES ('" . implode("','", $formatted_fields) . "')";
		return $this->query($this->prepare($sql, $data));
	}
	
	/**
	 * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
	 *
	 * The following directives can be used in the query format string:
	 *   %d (integer)
	 *   %f (float)
	 *   %s (string)
	 *   %% (literal percentage sign - no argument needed)
	 *
	 * All of %d, %f, and %s are to be left unquoted in the query string and they need an argument passed for them.
	 * Literals (%) as parts of the query must be properly written as %%.
	 *
	 * This function only supports a small subset of the sprintf syntax; it only supports %d (integer), %f (float), and %s (string).
	 * Does not support sign, padding, alignment, width or precision specifiers.
	 * Does not support argument numbering/swapping.
	 *
	 * May be called like {@link http://php.net/sprintf sprintf()} or like {@link http://php.net/vsprintf vsprintf()}.
	 *
	 * Both %d and %s should be left unquoted in the query string.
	 *
	 * <code>
	 * SQ_DB::prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d", 'foo', 1337 )
	 * SQ_DB::prepare( "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s", 'foo' );
	 * </code>
	 *
	 * @link http://php.net/sprintf Description of syntax.
	 *
	 * @param string $query Query statement with sprintf()-like placeholders
	 * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like
	 * 	{@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if
	 * 	being called like {@link http://php.net/sprintf sprintf()}.
	 * @param mixed $args,... further variables to substitute into the query's placeholders if being called like
	 * 	{@link http://php.net/sprintf sprintf()}.
	 * @return null|false|string Sanitized query string, null if there is no query, false if there is an error and string
	 * 	if there was something to prepare
	 */
	function prepare($query = null){ // ( $query, *$args )
		if(is_null($query)){
			return;
		}
			
		$args = func_get_args();
		array_shift($args);
		// If args were passed as an array (as in vsprintf), move them up
		if (isset($args[0]) && is_array($args[0])){
			$args = $args[0];
		}
		$query = str_replace("'%s'", '%s', $query); // in case someone mistakenly already singlequoted it
		$query = str_replace('"%s"', '%s', $query); // doublequote unquoting
		$query = preg_replace('|(?<!%)%s|', "'%s'", $query); // quote the strings, avoiding escaped strings like %%s
		array_walk($args, array(&$this, 'escape_by_ref'));
		return @vsprintf($query, $args);
	}
	
	/**
	 * Escapes content by reference for insertion into the database, for security
	 *
	 * @param string $string to escape
	 * @return void
	 */
	function escape_by_ref(&$string){
		$string = $this->escape($string);
	}
	
	/**
	
	/**
	 * Escapes content for insertion into the database using addslashes(), for security.
	 *
	 * Works on arrays.
	 *
	 * @param string|array $data to escape
	 * @return string|array escaped as query safe string
	 */
	function escape($data){
		if(is_array($data)){
			foreach((array) $data as $k => $v){
				if (is_array($v))
					$data[$k] = $this->escape($v);
				else
					$data[$k] = mysql_escape_string($v);
			}
		} else {
			$data = mysql_escape_string($data);
		}

		return $data;
	}
	
	/**
	 * Kill cached query results.
	 *
	 * @return void
	 */
	function flush(){
		$this->last_result = array();
		$this->col_info    = null;
		$this->last_query  = null;
	}
	
	/**
	 * Starts the timer, for debugging purposes.
	 *
	 * @return true
	 */
	function timer_start(){
		$mtime = explode(' ', microtime());
		$this->time_start = $mtime[1] + $mtime[0];
		return true;
	}
	
	/**
	 * Stops the debugging timer.
	 *
	 * @return int Total time spent on the query, in milliseconds
	 */
	function timer_stop(){
		$mtime = explode(' ', microtime());
		$time_end = $mtime[1] + $mtime[0];
		$time_total = $time_end - $this->time_start;
		return $time_total;
	}
	
	/**
	 * Retrieve the name of the function that called SQ_DB.
	 *
	 * Searches up the list of functions until it reaches
	 * the one that would most logically had called this method.
	 *
	 * @return string The name of the calling function
	 */
	function get_caller(){
		$trace  = array_reverse(debug_backtrace());
		$caller = array();
	
		foreach($trace as $call){
			if(isset($call['class']) && __CLASS__ == $call['class']){
				continue; // Filter out wpdb calls.
			}
			$caller[] = isset($call['class']) ? "{$call['class']}->{$call['function']}" : $call['function'];
		}
	
		return join(', ', $caller);
	}
}
?>