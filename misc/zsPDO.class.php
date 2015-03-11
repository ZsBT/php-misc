<?php /*
	This class extends PDO and helps to query/insert data with one call.
	Therefore we support often-used select statements.

	https://github.com/ZsBT
	
*/
class zsPDO extends PDO {

    public function oneValue($sql){  /* returns the first column of the first row of the query */
        if(!$st = $this->prepare($sql))
            throw new Exception("Prepare statement error: ".json_encode($this->errorInfo()) );
        $st->execute();
        return $st->fetch(PDO::FETCH_NUM)[0];
    }
    
    public function oneCol($sql){  /* returns the first column of all rows of the query */
        if(!$st = $this->prepare($sql))
            throw new Exception("Prepare statement error: ".json_encode($this->errorInfo()) );
        $st->execute();
        return $st->fetchAll(PDO::FETCH_COLUMN,0);
    }
    
    public function oneRow($sql,$mode=PDO::FETCH_OBJ){  /* returns the first row of a statement, as stdClass object  */
        if(!$st = $this->prepare($sql))
            throw new Exception("Prepare statement error: ".json_encode($this->errorInfo()) );
        $st->execute();
        return $st->fetch($mode);
    }
    
    public function allRow($sql,$mode=PDO::FETCH_CLASS){  /* returns an array of stdClass objects - be sure to use only reasonable number of records. */
        if(!$st = $this->prepare($sql))
            throw new Exception("Prepare statement error: ".json_encode($this->errorInfo()) );
        $st->execute();
        $fa = $st->fetchAll($mode);
        return $fa;
    }
    

    public function iterate($sql, $function,$mode=PDO::FETCH_CLASS){	/* pass every record object as parameter to $function  */
        if(!$st = $this->prepare($sql))
            throw new Exception("Prepare statement error: ".json_encode($this->errorInfo()) );
        $st->execute();
        while($fo = $st->fetch($mode))$function($fo);
        return true;
    }
    
    
    public function insert($table, $datArr){  /* insert data to a table. datArr is a mapped array. no BLOB support */
        $keys = @array_keys($datArr);
        $sql = @sprintf("insert into $table (%s) values (:%s)", implode(",",$keys), implode(",:",$keys) );
        if(!$st = $this->prepare($sql))throw new Exception("Invalid statement: $sql");
        
        // bind parameters
        $tmp=null;
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value);
        
        if(!$st->execute())return false;
        return ($ID=$this->lastInsertId())? $ID:true ;
    }

    public function update($table, $datArr, $cond){  /* update data in a table. datArr is a mapped array. $cond is the condition string */
        $keys = @array_keys($datArr);
        
        // will be the SET values in statement
        $sets = array();
        foreach($keys as $key)
            $sets[] = "{$key}=:{$key}";
            
        $sql = @sprintf("update {$table} set %s where {$cond}", implode(",",$sets) );
        
        if(!$st = $this->prepare($sql))throw new Exception("Invalid statement: $sql");
        
        // bind parameters
        $tmp=null;
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value);
        
        if(!$st->execute())return false;
        return $st->rowCount();
    }
}
