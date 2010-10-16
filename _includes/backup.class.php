<?php

class MDB_Backup
{
  public function __construct($conn)
  {
    $this->conn = $conn;
  }
  
  public function dump($path)
  {
    $out = fopen($path, 'w');
    
    if (!$out)
      throw new MDB_Exception_File("Could not open file $path for reading");
    
    $tables = $this->query("SHOW TABLES")->fetchColumn();
    
    foreach ($tables as $table)
      $this->dumpTable($table, $out);
  }
  
  protected function dumpTable($table, $out)
  {
    fwrite($out, $this->query("SHOW CREATE TABLE `$table`")->fetchField(1));
    
    fwrite($out, ";\n\n");
    
    $rows_stmt = $this->query("SELECT * FROM `$table`");
    
    // If there are no rows in the table, don't bother to create an INSERT stmt
    if (!$row = $rows_stmt->fetchRow())
      return;
    
    $columns = implode(', ', $this->query("SHOW FIELDS FROM `$table`")->fetchColumn(0));
    
    fwrite($out, "INSERT INTO `$table` ($columns) VALUES");
    
    $row_idx = 0;
    
    while ($row)
    {
      fwrite($out, sprintf("%s\n(%s)",
        $row_idx++ ? ',' : '',
        implode(', ', array_map(array($this, 'quote'), $row))));
        
      $row = $rows_stmt->fetchRow();
    }
    
    fwrite($out, ";\n\n");
  }
  
  protected function quote($value)
  {
    if(ctype_digit($value))
      return $value;
    
    return sprintf("'%s'", mysql_real_escape_string($value, $this->conn));
  }
  
  protected function query($query)
  {
    $result = mysql_query($query, $this->conn);
    
    if(!$result)
      throw new MDB_Exception_SQL(mysql_error($this->conn), $query);
    
    return new MDB_Result($result);
  }
}

class MDB_Result
{
  protected $result;
  
  public function __construct($result)
  {
    $this->result = $result;
  }
  
  public function fetchColumn($index = 0)
  {
    $results = array();
    
    // fetch-array, so index can be either an index or a column name.
    while ($field = $this->fetchField($index))
      $results[] = $field;
    
    return $results;
  }
  
  public function fetchField($index = 0)
  {
    $row = mysql_fetch_array($this->result);
    return $row ? $row[$index] : false;
  }
  
  public function fetchRow()
  {
    return mysql_fetch_row($this->result);
  }
}

class MDB_Exception extends Exception
{}

class MDB_Exception_File extends MDB_Exception
{}

class MDB_Exception_SQL extends MDB_Exception
{
  public $query;
  
  public function __construct($error, $query = null)
  {
    parent::__construct($error);
    $this->query = $query;
  }
}