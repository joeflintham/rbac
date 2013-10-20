<?php

class DB_Mysql {

      private static $instance;
      # some general DB functions

      public $tables = array();

      function DB_Mysql($host, $user, $pass, $db) {

      	    // starts a new DB connection and accesses the db tables

      	    $server   = $host;
      	    $database = $db;
      	    $user     = $user;
      	    $pass     = $pass;

      	    $this->connection		= mysql_connect($server, $user, $pass);
      	    $this->accessdatabase	= mysql_select_db($database);
      	    $this->database		= $database;
      	    $this->tables		= $this->DB_Tables();	

      }

      public static function create($db_host, $db_user, $db_pass, $db_name) {

            if (!(isset(self::$instance))){
      	       self::$instance = new DB_Mysql($db_host, $db_user, $db_pass, $db_name);
      	    }
      	    return self::$instance;
      }

      public function closeDB() {

      	    // close the DB connection

      	    mysql_close($this->connection);

      }

      public function DB_Execute($query) {

      	    // provides some feedback data in case of error
	    // or the need for an insert ID

	    $this->resultObject		= "";
	    $this->feedback		= "";
	    $this->errorNo		= "";
	    $this->ID			= "";
	    $this->numrows		= "";
	    $this->foundRows 		= "";
	    $this->affectedRows 	= "";

	    $this->lastQuery 		= $query;		
	    $this->resultObject		= mysql_query($query);
	    $this->feedback		= mysql_error();
	    $this->errorNo		= mysql_errno();
	    $this->ID			= mysql_insert_id();
	    $this->numrows		= @mysql_num_rows($this->resultObject);
	    $this->affectedRows		= mysql_affected_rows($this->connection);
	    $readfoundRows 		= mysql_query("SELECT FOUND_ROWS()");

	    while($data = mysql_fetch_assoc($readfoundRows)){
	    	$this->foundRows = $data["FOUND_ROWS()"];
	    }

	    return $this->resultObject;
      }

      public function DB_ExecuteAndProcess($query) {

      	    // it returns an array of results

	    $resultSet = array();
	    $thisQuery = $this->DB_Execute($query);

	    if ($thisQuery){

	       $counter = 0;
	       while($data = mysql_fetch_assoc($thisQuery)){
	            while(list($key, $value) = each($data)){
	       	         $resultSet[$counter][$key] = $value;
	    	    }
	       	    $counter ++;
      	       }

      	       return $resultSet;
      	    }
      }	    

      public static function cleanse($input, $type = "string") {
          // expects a single value which may need cleansing ($input)
	  // returns a cleansed value according to optional $type (defaults to string, which is escaped)

	  if ($type == "string"){
	     return mysql_real_escape_string($input);
	  } else if ($type == "timestamp"){
	     return (preg_match("/\d{10}/", $input)) ? $input : time() ;
	  } else if ($type = "int"){
	     return (is_numeric($input)) ? $input : 0 ;
	  }
      }

      private function DB_Tables() {

      	 $sql = "SHOW TABLES;";
	 $query = $this->DB_Execute($sql);
	 $result = $this->resultObject;
	 if ($result){
	     while($data = mysql_fetch_assoc($result)){
	         while (list($key, $value) = each($data)){
	             $this->tables[] = $value;
	         }
             }
	 }
	 return $this->tables;
      }

      private function DB_Fields($whichTable) {

          // returns an array listing a tablename
      	  // and the fields in that table 
	  // (i.e. it returns structural info about the db table)
	  // it's useful for building dynamic forms / queries etc

	  $sql = "SHOW COLUMNS FROM $whichTable";
	  $query = $this->DB_Execute($sql);
	  $result = $this->resultObject;
	  while($data = mysql_fetch_assoc($result)){
	      $thisField = "";
	      while (list($key, $value) = each($data)){
	          $thisField[$key] = $value;
	      }
	      $this->fields[$whichTable][] = $thisField;	
	  }
	  return $this->fields;
      }

}

?>