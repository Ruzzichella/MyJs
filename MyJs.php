<?php
	
	function JOUT(){ echo( ( $arr = func_get_arg( 0 ) ) ? call_user_func_array('json_encode', array( $arr, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT ) ) : "" ); }
	
	function JERR( $id, $err_str, $err_code = 1 ){ return array( $id => array( "err" => $err_code, "err_str" => $err_str ) ); }
	
	function JARR( $arr_id = "unknown" ){ $arg_list = func_get_args(); array_shift( $arg_list ); return array( $arr_id => $arg_list ); }
	
	$timer = 0;
	function TIMER( $time ){ global $timer; return ( $time ? ( ( microtime( true ) - $timer ) * 1000 ) : ( $timer = microtime( true ) ) ); }

	class DbJs
	{
		private $db_pdo = NULL;
		private $debug	= 0;
		
		function __construct()
		{
			$a = func_get_args();
			$i = func_num_args();
			if( method_exists( $this, $f = '__construct' . $i ) ){ call_user_func_array( array( $this, $f ), $a ); }
		}
		function __construct5( $host, $port, $db_name, $username, $password )
		{
			try
			{
				$this->db_pdo = new PDO( 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $db_name, $username, $password );
			}
			catch( PDOException $e )
			{
				exit( "[ERROR]: PDOException, " . $e->getMessage() . "<br/>" );
			}
		}
		
		function DEBUG( $val ){ $this->debug = $val; }
		
		function Q( $query_id, $query, $statement_array = NULL )
		{
			try
			{
				if( $this->debug ) TIMER();
				$stmt = $this->db_pdo->prepare( $query );
				if( $this->debug ) $timer_pre = TIMER( 1 );
				
				if( ( $par_len = count( $statement_array ) ) )
				{
					foreach( $statement_array as $key => $value )
					{
						$stmt->bindParam( $key, $value );
					}
				}
				if( $this->debug ) $timer_bin = TIMER( 1 );
				
				if( $this->db_pdo->beginTransaction() )
				{
					if( $this->debug ) $timer_beg = TIMER( 1 );
					
					if( $stmt->execute() )
					{
						if( $this->debug ) $timer_exe = TIMER( 1 );
						
						if( $this->db_pdo->commit() )
						{
							if( $this->debug ) $timer_com = TIMER( 1 );
							
							$toRet = array();
							
							if( $this->debug ) $toRet["debug"] =
							[
								"prepare"	=> round( $timer_pre, 3 ),
								"bind"		=> round( $timer_bin, 3 ),
								"begin"		=> round( $timer_beg, 3 ),
								"execute"	=> round( $timer_exe, 3 ),
								"commit"	=> round( $timer_com, 3 )
							];
							
							if( strpos( $query, "SELECT" ) !== FALSE ) if( is_array( $fetched = $stmt->fetchAll( PDO::FETCH_ASSOC ) ) ) $toRet[ $query_id ] = $fetched; else return JERR( $query_id, "Error Fetching Result Set" );
							if( strpos( $query, "INSERT" ) !== FALSE ) $toRet[ $query_id ] = [ "last_insert_ID" => $this->db_pdo->lastInsertId() ];
							if( strpos( $query, "DELETE" ) !== FALSE ) $toRet[ $query_id ] = [ "row_count" => $stmt->rowCount() ];
							
							return $toRet;
						}
						return JERR( $query_id, "Unable to Commit" );
					}
					else return $this->db_pdo->rollBack() ? JERR( $query_id, $stmt->errorInfo()[2] ) : JERR( $query_id, "Unable to RollBack" );
				}
			}
			catch( PDOException $e ){ return JERR( $query_id, "PDOException, " . $e->getMessage() ); }
		}
	}
	
	//Sample Usage
	/*
	$DB = new DbJs( "127.0.0.1", "3306", "schemata", "root", "password" );
	
	$DB->DEBUG( 1 );

	$toRet = $DB->Q( "Admin 1", "SELECT * FROM Users WHERE ID = :id", [ ":id" => 1 ] );
	JOUT( $toRet ); echo("</br>");
	
	$toRet = $DB->Q( "utenti vuoto", "SELECT * FROM Users WHERE 0" );
	echo("</br>"); JOUT( $toRet ); echo("</br>");
	
	$toRet = $DB->Q( "utenti", "SELECT * FROM asdasd WHERE type=1" );
	echo("</br>"); JOUT( $toRet ); echo("</br>");
		
	$toRet2 = $DB->Q( "insert", "INSERT INTO Users VALUES ( 0, \"ALESSIOOOOOOOOO\", \"ALESSIOOOOOOOOO\", 1, \"ALESSIOOOOOOOOO\", 1, \"\", \"\", 0, 1)" );
	echo("</br>"); JOUT( $toRet2 ); echo("</br>");
	
	echo("</br>"); JOUT( JARR( "Array Example", $toRet, $toRet2 ) ); echo("</br>");
	
	$toRet = $DB->Q( "delete", "DELETE FROM Users WHERE username = \"ALESSIO\"" );
	echo("</br>"); JOUT( $toRet ); echo("</br>");
	*/
?>
