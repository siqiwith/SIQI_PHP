<?php
/**
 * Database Access Abstraction Object
 */
class SQ_DB{
	/**
	 * Whether to show SQL/DB errors
	 *
	 * @access private
	 * @var bool
	 */
	private $show_errors = true;
	
	/**
	 * Whether to suppress errors during the DB bootstrapping.
	 *
	 * @access private
	 * @var bool
	 */
	private $suppress_errors = false;
	
	/**
	 * The last error during query.
	 *
	 * @access private
	 * @var string
	 */
	private $last_error = '';
	
	/**
	 * Amount of queries made
	 *
	 * @access private
	 * @var int
	 */
	private $num_queries = 0;
	
	/**
	 * Count of rows returned by previous query
	 *
	 * @access private
	 * @var int
	 */
	private $num_rows = 0;
	
	/**
	 * Count of affected rows by previous query
	 *
	 * @access private
	 * @var int
	 */
	private $rows_affected = 0;
	
	/**
	 * The ID generated for an AUTO_INCREMENT column by the previous query (usually INSERT).
	 *
	 * @access public
	 * @var int
	 */
	public $insert_id = 0;
	
	/**
	 * Saved result of the last query made
	 *
	 * @access private
	 * @var array
	 */
	private $last_query;
	
	/**
	 * Results of the last query made
	 *
	 * @access private
	 * @var array|null
	 */
	private $last_result;
	
	/**
	 * Saved info on the table column
	 *
	 * @access private
	 * @var array
	 */
	private $col_info;
	
	/**
	 * Saved queries that were executed
	 *
	 * @access private
	 * @var array
	 */
	private $queries;
	
	/**
	 * Whether the database queries are ready to start executing.
	 *
	 * @access private
	 * @var bool
	 */
	private $ready = false;
	
	/**
	 * Format specifiers for DB columns. Columns not listed here default to %s. Initialized during WP load.
	 *
	 * Keys are column names, values are format types: 'ID' => '%d'
	 *
	 * @see SQ_DB:prepare()
	 * @see SQ_DB:insert()
	 * @see SQ_DB:update()
	 * @see wp_set_wpdb_vars()
	 * @access public
	 * @var array
	 */
	public $field_types = array();
	
	/**
	 * Database table columns charset
	 *
	 * @access public
	 * @var string
	 */
	public $charset;
	
	/**
	 * Database table columns collate
	 *
	 * @access public
	 * @var string
	 */
	public $collate;
	
	/**
	 * Whether to use mysql_real_escape_string
	 *
	 * @access public
	 * @var bool
	 */
	public $real_escape = false;
	
	/**
	 * Database Username
	 *
	 * @access private
	 * @var string
	 */
	private $dbuser;
	
	/**
	 * A textual description of the last query/get_row/get_var call
	 *
	 * @access public
	 * @var string
	 */
	public $func_call;
	
	/**
	 * Whether MySQL is used as the database engine.
	 *
	 * Set in SIQI_DB::db_connect() to true, by default. This is used when checking
	 * against the required MySQL version for WordPress. Normally, a replacement
	 * database drop-in (db.php) will skip these checks, but setting this to true
	 * will force the checks to occur.
	 *
	 * @access public
	 * @var bool
	 */
	public $is_mysql = null;
	
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
	function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
		if ( SQ_DEBUG )
			$this->show_errors();

		$this->init_charset();

		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;

		$this->db_connect();
	}
	
	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @see SQ_DB::__construct()
	 * @return bool true
	 */
	function __destruct() {
		return true;
	}
	
	/**
	 * Set $this->charset and $this->collate
	 *
	 */
	function init_charset() {
		if ( defined( 'DB_COLLATE' ) ) {
			$this->collate = DB_COLLATE;
		}
		if ( defined( 'DB_CHARSET' ) )
			$this->charset = DB_CHARSET;
	}
	
	/**
	 * Sets the connection's character set.
	 *
	 * TODO 2012/02/13
	 *
	 * @param resource $dbh     The resource given by mysql_connect
	 * @param string   $charset The character set (optional)
	 * @param string   $collate The collation (optional)
	 */
	function set_charset($dbh, $charset = null, $collate = null) {
	}
	
	/**
	 * Enables showing of database errors.
	 *
	 * This function should be used only to enable showing of errors.
	 * wpdb::hide_errors() should be used instead for hiding of errors. However,
	 * this function can be used to enable and disable showing of database
	 * errors.
	 *
	 * @see wpdb::hide_errors()
	 *
	 * @param bool $show Whether to show or hide errors
	 * @return bool Old value for showing errors.
	 */
	function show_errors( $show = true ) {
		$errors = $this->show_errors;
		$this->show_errors = $show;
		return $errors;
	}
	
	/**
	 * Connect to and select database
	 *
	 */
	function db_connect() {
	
		$this->is_mysql = true;
	
		if ( SQ_DEBUG ) {
			$this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, true );
		} else {
			$this->dbh = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, true );
		}
	
		if ( !$this->dbh ) {
			$this->bail( sprintf( /*SQ_I18N_DB_CONN_ERROR*/"
					<h1>Error establishing a database connection</h1>
					<p>This either means that the username and password information in your <code>config.php</code> file is incorrect or we can't contact the database server at <code>%s</code>. This could mean your host's database server is down.</p>
					<ul>
					<li>Are you sure you have the correct username and password?</li>
					<li>Are you sure that you have typed the correct hostname?</li>
					<li>Are you sure that the database server is running?</li>
					</ul>
					"/*/SQ_I18N_DB_CONN_ERROR*/, $this->dbhost ), 'db_connect_fail' );
	
			return;
		}
	
		$this->set_charset( $this->dbh );
	
		$this->ready = true;
	
		$this->select( $this->dbname, $this->dbh );
	}
	
	/**
	 * Selects a database using the current database connection.
	 *
	 * The database name will be changed based on the current database
	 * connection. On failure, the execution will bail and display an DB error.
	 *
	 * @param string $db MySQL database name
	 * @param resource $dbh Optional link identifier.
	 * @return null Always null.
	 */
	function select( $db, $dbh = null ) {
		if ( is_null($dbh) )
			$dbh = $this->dbh;
	
		if ( !@mysql_select_db( $db, $dbh ) ) {
			$this->ready = false;
			$this->bail( sprintf( /*SQ_I18N_DB_SELECT_DB*/'<h1>Can&#8217;t select database</h1>
					<p>We were able to connect to the database server (which means your username and password is okay) but not able to select the <code>%1$s</code> database.</p>
					<ul>
					<li>Are you sure it exists?</li>
					<li>Does the user <code>%2$s</code> have permission to use the <code>%1$s</code> database?</li>
					<li>On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?</li>
					</ul>
					<p>If you don\'t know how to set up a database you should <strong>contact your host</strong>. If all else fails you may find help at the <a href="http://wordpress.org/support/">WordPress Support Forums</a>.</p>'/*/WP_I18N_DB_SELECT_DB*/, $db, $this->dbuser ), 'db_select_fail' );
			return;
		}
		//print("DB:".this.db." connnected");
	}
	
	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $query ) {
		if ( ! $this->ready )
			return false;
	
		$return_val = 0;
		$this->flush();
	
		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";
	
		// Keep track of the last query for debug..
		$this->last_query = $query;
	
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->timer_start();
	
		$this->result = @mysql_query( $query, $this->dbh );
		$this->num_queries++;
	
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
			$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );
	
		// If there is an error then take note of it..
		if ( $this->last_error = mysql_error( $this->dbh ) ) {
			$this->print_error();
			return false;
		}
	
		if ( preg_match( '/^\s*(create|alter|truncate|drop) /i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace) /i', $query ) ) {
			$this->rows_affected = mysql_affected_rows( $this->dbh );
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace) /i', $query ) ) {
				$this->insert_id = mysql_insert_id($this->dbh);
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			// Select...
			$i = 0;
			while ( $i < @mysql_num_fields( $this->result ) ) {
				$this->col_info[$i] = @mysql_fetch_field( $this->result );
				$i++;
			}
			$num_rows = 0;
			while ( $row = @mysql_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}
	
			@mysql_free_result( $this->result );
	
			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
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
	 * @see SQ_DB::prepare()
	 * @see SQ_DB::$field_types
	 * @see sq_set_wpdb_vars()  this might be optional, just manually set data type for each column
	 *
	 * @param string $table table name
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where.  A format is one of '%d', '%f', '%s' (integer, float, string).  If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) )
			return false;
	
		$formats = $format = (array) $format;
		$bits = $wheres = array();
		foreach ( (array) array_keys( $data ) as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset($this->field_types[$field]) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$bits[] = "`$field` = {$form}";
		}
	
		$where_formats = $where_format = (array) $where_format;
		foreach ( (array) array_keys( $where ) as $field ) {
			if ( !empty( $where_format ) )
				$form = ( $form = array_shift( $where_formats ) ) ? $form : $where_format[0];
			elseif ( isset( $this->field_types[$field] ) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$wheres[] = "`$field` = {$form}";
		}
	
		$sql = "UPDATE `$table` SET " . implode( ', ', $bits ) . ' WHERE ' . implode( ' AND ', $wheres );
		return $this->query( $this->prepare( $sql, array_merge( array_values( $data ), array_values( $where ) ) ) );
	}
	
	/**
	 * Insert a row into a table.
	 *
	 * <code>
	 * SQ_DB::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * SQ_DB::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @see SQ_DB::prepare()
	 * @see SQ_DB::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	function insert( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}
	
	/**
	 * Replace a row into a table.
	 *
	 * <code>
	 * SQ_DB::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * SQ_DB::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @see SQ_DB::prepare()
	 * @see SQ_DB::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}
	
	/**
	 * Helper function for insert and replace.
	 *
	 * Runs an insert or replace query based on $type argument.
	 *
	 * @access private
	 * @see SQ_DB::prepare()
	 * @see SQ_DB::$field_types
	 * @see wp_set_wpdb_vars()
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs).  Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in wpdb::$field_types.
	 * @param string $type Optional. What type of operation is this? INSERT or REPLACE. Defaults to INSERT.
	 * @return int|false The number of rows affected, or false on error.
	 */
	private function _insert_replace_helper( $table, $data, $format = null, $type = 'INSERT' ) {
		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) )
			return false;
		$formats = $format = (array) $format;
		$fields = array_keys( $data );
		$formatted_fields = array();
		foreach ( $fields as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset( $this->field_types[$field] ) )
			$form = $this->field_types[$field];
			else
				$form = '%s';
			$formatted_fields[] = $form;
		}
		$sql = "{$type} INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES ('" . implode( "','", $formatted_fields ) . "')";
		return $this->query( $this->prepare( $sql, $data ) );
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
	function prepare( $query = null ) { // ( $query, *$args )
		if ( is_null( $query ) )
			return;
	
		$args = func_get_args();
		array_shift( $args );
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset( $args[0] ) && is_array($args[0]) )
			$args = $args[0];
		$query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
		$query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
		$query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
		array_walk( $args, array( &$this, 'escape_by_ref' ) );
		return @vsprintf( $query, $args );
	}
	
	/**
	 * Escapes content by reference for insertion into the database, for security
	 *
	 * @uses SQ_DB::_real_escape()
	 * @param string $string to escape
	 * @return void
	 */
	function escape_by_ref( &$string ) {
		$string = $this->_real_escape( $string );
	}
	
	/**
	 * Weak escape, using addslashes()
	 *
	 * @see addslashes()
	 * @access private
	 *
	 * @param string $string
	 * @return string
	 */
	private function _weak_escape( $string ) {
		return addslashes( $string );
	}
	
	/**
	 * Escapes content for insertion into the database using addslashes(), for security.
	 *
	 * Works on arrays.
	 *
	 * @param string|array $data to escape
	 * @return string|array escaped as query safe string
	 */
	function escape( $data ) {
		if ( is_array( $data ) ) {
			foreach ( (array) $data as $k => $v ) {
				if ( is_array( $v ) )
					$data[$k] = $this->escape( $v );
				else
					$data[$k] = $this->_weak_escape( $v );
			}
		} else {
			$data = $this->_weak_escape( $data );
		}
	
		return $data;
	}
	
	/**
	 * Real escape, using mysql_real_escape_string() or addslashes()
	 *
	 * @see mysql_real_escape_string()
	 * @see addslashes()
	 * @access private
	 *
	 * @param  string $string to escape
	 * @return string escaped
	 */
	private function _real_escape( $string ) {
		if ( $this->dbh && $this->real_escape )
			return mysql_real_escape_string( $string, $this->dbh );
		else
			return addslashes( $string );
	}
	
	
	/**
	 * Kill cached query results.
	 *
	 * @return void
	 */
	function flush() {
		$this->last_result = array();
		$this->col_info    = null;
		$this->last_query  = null;
	}
	
	/**
	 * Wraps errors in a nice header and footer and dies.
	 *
	 * Will not die if SQ_DB::$show_errors is true
	 *
	 * @param string $message The Error message
	 * @param string $error_code Optional. A Computer readable string to identify the error.
	 * @return false|void
	 */
	function bail( $message, $error_code = '500' ) {
		if ( !$this->show_errors ) {
			if ( class_exists( 'SQ_Error' ) )
				$this->error = new SQ_Error($error_code, $message);
			else
				$this->error = $message;
			return false;
		}
		sq_die($message);
	}
	
	/**
	 * Starts the timer, for debugging purposes.
	 *
	 * @return true
	 */
	function timer_start() {
		$mtime = explode( ' ', microtime() );
		$this->time_start = $mtime[1] + $mtime[0];
		return true;
	}
	
	/**
	 * Stops the debugging timer.
	 *
	 * @return int Total time spent on the query, in milliseconds
	 */
	function timer_stop() {
		$mtime = explode( ' ', microtime() );
		$time_end   = $mtime[1] + $mtime[0];
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
	function get_caller() {
		$trace  = array_reverse( debug_backtrace() );
		$caller = array();
	
		foreach ( $trace as $call ) {
			if ( isset( $call['class'] ) && __CLASS__ == $call['class'] )
				continue; // Filter out wpdb calls.
			$caller[] = isset( $call['class'] ) ? "{$call['class']}->{$call['function']}" : $call['function'];
		}
	
		return join( ', ', $caller );
	}
	
	/**
	 * Print SQL/DB error.
	 *
	 * @global array $EZSQL_ERROR Stores error information of query and error string
	 *
	 * @param string $str The error to display
	 * @return bool False if the showing of errors is disabled.
	 */
	function print_error( $str = '' ) {
		global $EZSQL_ERROR;
	
		if ( !$str )
			$str = mysql_error( $this->dbh );
		$EZSQL_ERROR[] = array( 'query' => $this->last_query, 'error_str' => $str );
	
		if ( $this->suppress_errors )
			return false;
	
		if ( $caller = $this->get_caller() )
			$error_str = sprintf( /*SQ_I18N_DB_QUERY_ERROR_FULL*/'System database error %1$s for query %2$s made by %3$s'/*/WP_I18N_DB_QUERY_ERROR_FULL*/, $str, $this->last_query, $caller );
		else
			$error_str = sprintf( /*SQ_I18N_DB_QUERY_ERROR*/'System database error %1$s for query %2$s'/*/WP_I18N_DB_QUERY_ERROR*/, $str, $this->last_query );
	
		if ( function_exists( 'error_log' )
				&& ( $log_file = @ini_get( 'error_log' ) )
				&& ( 'syslog' == $log_file || @is_writable( $log_file ) )
		)
			@error_log( $error_str );
	
		// Are we showing errors?
		if ( ! $this->show_errors )
			return false;
	
		$str   = htmlspecialchars( $str, ENT_QUOTES );
		$query = htmlspecialchars( $this->last_query, ENT_QUOTES );

		print "<div id='error'>
		<p class='wpdberror'><strong>WordPress database error:</strong> [$str]<br />
		<code>$query</code></p>
		</div>";
	}
}
?>