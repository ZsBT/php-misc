<?php /*


	This class extends PDO and helps to query/insert data with one call.
	Therefore we support often-used select statements.

*/

class zsPDO extends PDO {

  /* returns the first column of the first row of the query */
    function oneValue($sql){
        $st = $this->prepare($sql);
        $st->execute();
        return $st->fetch(PDO::FETCH_NUM)[0];
    }
    
  /* returns the first row of a statement, as stdClass object  */
    function oneRow($sql){
        $st = $this->prepare($sql);
        $st->execute();
        return $st->fetchObject();
    }
    
  /* returns an array of stdClass objects - be sure to use only reasonable number of records. */
    function allRow($sql){
        if(!$st = $this->prepare($sql))
            throw new Exception("Prepare statement error: ".json_encode($this->errorInfo()) );
        $st->execute();
        $fa = $st->fetchAll(PDO::FETCH_CLASS);
        return $fa;
    }
    
  /* insert data to a table. datArr is a mapped array. no BLOB support */
    function insert($table, $datArr){
        $keys = @array_keys($datArr);
        $sql = @sprintf("insert into $table (%s) values (:%s)", implode(",",$keys), implode(",:",$keys) );
        if(!$st = $this->prepare($sql))throw new Exception("Invalid statement: $sql");
            
        // bind parameters
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value);
        
        if(!$st->execute())return false;
        return ($ID=$this->lastInsertId())? $ID:true ;
    }

  /* update data in a table. datArr is a mapped array. $cond is the condition string */
    function update($table, $datArr, $cond){
        $keys = @array_keys($datArr);
        
        // will be the SET values in statement
        $sets = array();
        foreach($keys as $key)
            $sets[] = "{$key}=:{$key}";
            
        $sql = @sprintf("update {$table} set %s where {$cond}", implode(",",$sets) );
        
        if(!$st = $this->prepare($sql))throw new Exception("Invalid statement: $sql");
        
        // bind parameters
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value);
        
        if(!$st->execute())return false;
        return $st->rowCount();
    }
}
