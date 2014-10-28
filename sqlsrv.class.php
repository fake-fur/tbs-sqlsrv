<?php

/**
 * TinyButStrong DataReader plug-in for Microsoft SQL Server.
 * It also can be used as a tiny database connectivity.
 * 
 * Requires the SQLSRV extension for PHP.
 * See http://php.net/manual/en/book.sqlsrv.php
 * @author:  Fakefur
 * @date:    2014-10-27
 * @version: 1.0
 *
 * Example:
 * $tbs = new clsTinybutStrong;
 * $tbs->LoadTemplate('myTemplate.html');
 * $sqlSrv = new tbsDbSqlServer('myServerName\myInstanceName', array('Database'=>"dbName", 'UID'=>"userName", 'PWD'=>"password"));
 * $tbs->MergeBlock('b', $sqlSrv, "SELECT id, name FROM table1 ORDER BY id");
 *
 * Synopsis for database connectivity:
 * ->tbsdb_*                      These functions are called by TBS and must remain as-is
 *
 * These functions are convenience helper functions that you can safely remove should you wish to do so
 * ->close()                      Close a database connection and any open statment handle
 * ->query($sql)                  Execute a simple query and return a statement
 * ->insert($sql)                 Execute an insert query and return the insert ID
 * ->fetch_assoc($stmt)           Return the next row of the statment of false if there are no more rows.
 * ->get_int($sql)                Execute a simple query that returns an integer value
 * ->get_string($sql)		      Execute a simple query that returns a string value
 * ->get_row($sql)			      Execute a simple query that returns a whole row as an associative array
 * ->p_get_row($sql, $params)     Execute a query with parameters and return a whole row as an associative array.
 * 
 */
 
class tbsDbSqlServer
{
	private $dbh;

	/**
	 * @param String $serverName Target server name or host.
	 * @param Array  $conninfo   Associative array containing the db connection required parameters.
	 *                           Expected keys: UID, PWD, Database
	 */
	public function __construct($serverName, $connInfo)
	{
		$this->dbh = sqlsrv_connect($serverName, $connInfo);
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
