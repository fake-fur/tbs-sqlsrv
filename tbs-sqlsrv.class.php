<?php

// these would normally be in a seperate file but are included here 
// for completeness of this class
define("DBHOST","your-database-host");
define("DBNAME","your-database-name");
define("DBUSER","your-user-name");
define("DBPASS","your-database-password");




class sqlsrv
{
	private $dbh;

	public function __construct()
	{
		$conninfo = array("UID" => DBUSER, "PWD" => DBPASS, "Database" => DBNAME);
		$this->dbh = sqlsrv_connect(DBHOST,$conninfo);
		if ($this->dbh === false){
			return false;
		}
	}

/*************************************************************************************
	these are the TBS specific functions that must be here and called as they are
**************************************************************************************/
	public function tbsdb_open(&$Source,&$Query)
	{
		$stmt = sqlsrv_query($this->dbh,$Query);
		if ($stmt === false){
			return false;
		}
		return $stmt;
	}

	public function tbsdb_fetch(&$Rs,$RecNum=null)
	{
		$row = sqlsrv_fetch_array($Rs,SQLSRV_FETCH_ASSOC);
		if (is_null($row)){
			$row = false;
		}
		return $row;
	}

	public function tbsdb_close(&$Rs)
	{
		sqlsrv_free_stmt($Rs);
	}
/*************************************************************************************
	end of TBS specific functions
**************************************************************************************/



/*************************************************************************************
	these are general functions i find useful but can be removed if you wish
**************************************************************************************/

	// close a database connection and any open statment handle
	public function close($stmt = null)
	{
		if (!is_null($stmt)){
			sqlsrv_free_stmt($stmt);
		}
		sqlsrv_close($this->dbh);
	}

	// run a simple query and return the stmt resource ready for fetching data if required
	public function query($sql)
	{
		$stmt = sqlsrv_query($this->dbh,$sql);
		return $stmt;
	}

	// seperate insert function so we can return the insert_id
	public function insert($sql)
	{
		$stmt = sqlsrv_query($this->dbh,$sql . ";SELECT SCOPE_IDENTITY();");
		if (!$stmt){
			return false;
		}
		sqlsrv_next_result($stmt);
		sqlsrv_fetch($stmt);
		$id = sqlsrv_get_field($stmt,0);
		sqlsrv_free_stmt($stmt);
		return $id;
	}

	// ready a row from an open stmt resource as an assoc array
	public function fetch_assoc($stmt)
	{
		$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
		if (is_null($row)){
			$row = false;
		}
		return $row;
	}

	// get an integer value from database using a simple query
	public function get_int($sql)
	{
		$stmt = sqlsrv_query($this->dbh,$sql);
		if ($stmt === false){
			return 0;
		}
		$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_NUMERIC);
		return settype($row[0],"integer");
	}

	// get a string value from database using a simple query
	public function get_string($sql)
	{
		$stmt = sqlsrv_query($this->dbh,$sql);
		if ($stmt === false){
			return "";
		}
		$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_NUMERIC);
		return $row[0];
	}

	// get an entire row from database using a simple query
	public function get_row($sql)
	{
		$stmt = sqlsrv_query($this->dbh,$sql);
		if ($stmt === false){
			return false;
		}
		return sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
	}

	// get an entire row from database using prepared statment
	public function p_get_row($sql,$params)
	{
		// have to convert each param into a reference to a param
		$prep_params = array();
		foreach ($params as $param){
			$prep_params[] = &$param;
		}

		$stmt = sqlsrv_prepare($this->dbh,$sql,$prep_params);
		if ($stmt === false){
			return false;
		}
		if (!sqlsrv_execute($stmt)){
			return false;
		}
		return sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
	}

}
?>
