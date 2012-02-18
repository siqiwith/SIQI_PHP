<?php
define('SQ_I18N_DB_CONN_ERROR', "
		<h1>Error establishing a database connection</h1>
		<p>This either means that the username and password information in your <code>config.php</code> file is incorrect or we can't contact the database server at <code>%s</code>. This could mean your host's database server is down.</p>
		<ul>
			<li>Are you sure you have the correct username and password?</li>
			<li>Are you sure that you have typed the correct hostname?</li>
			<li>Are you sure that the database server is running?</li>
		</ul>"
);

define('SQ_I18N_DB_SELECT_DB', '<h1>Can&#8217;t select database</h1>
		<p>We were able to connect to the database server (which means your username and password is okay) but not able to select the <code>%1$s</code> database.</p>
		<ul>
		<li>Are you sure it exists?</li>
		<li>Does the user <code>%2$s</code> have permission to use the <code>%1$s</code> database?</li>
		<li>On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?</li>
		</ul>
		<p>If you don\'t know how to set up a database you should <strong>contact your host</strong>. If all else fails you may find help at the <a href="http://wordpress.org/support/">WordPress Support Forums</a>.</p>'
);

define('SQ_I18N_DB_QUERY_ERROR_FULL', 'System database error %1$s for query %2$s made by %3$s'
);

define('SQ_I18N_DB_QUERY_ERROR', 'System database error %1$s for query %2$s'
);
?>