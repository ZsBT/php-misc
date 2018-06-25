<?php /*

	This class extends PDO and helps to query/insert data with one call.
	Therefore we support often-used select statements.
	
	https://github.com/ZsBT


CLASS SYNOPSIS

    public function oneValue($sql)  // returns the first column of the first row of the query 
    public function oneCol($sql)  // returns the first column of all rows of the query 
    public function oneRow($sql, $mode=PDO::FETCH_OBJ)  // returns the first row of a statement, as stdClass object  
    public function allRow($sql, $mode=PDO::FETCH_CLASS)  // returns an array of stdClass objects - be sure to use only reasonable number of records. 

    public function iterate($sql, $function, $mode=PDO::FETCH_OBJ)	// pass every record object as parameter to $function  
    public function insert($table, $datArr)  // insert data to a table. datArr is a mapped array. no BLOB support 
    public function insert_multi($table, $datArrArr)  // insert multiple data to a table. datArr is an array of mapped array. no BLOB support
    public function update($table, $datArr, $cond)  // update data in a table. datArr is a mapped array. $cond is the condition string 

    public function lastError()      // return last error message


DEPENDENCIES

	Needs php 5.3

	
CHANGELOG
	
	2016-05		added insert_multi()
	2016-03		moving under namespace
	2015-11		added lastError()
	2015-03		added iterate(), consolidated statement preparations
	
*/

namespace ZsBT\misc;

class PDO extends \PDO {
    
    public function __construct($dsn, $username=NULL, $password=NULL, $options=[] ){
        // use persistent connection if not specified
        if(!isset($options[PDO::ATTR_PERSISTENT]))$options[PDO::ATTR_PERSISTENT] = true;
        $this->dsn = $dsn;
        $this->driver = strstr($dsn, ":", true);
        parent::__construct($dsn, $this->user=$username, $this->pass=$password, $this->opts=$options);
        self::setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
    }
    private $dsn,$driver,$user,$pass,$opts;
    
    

    public function begin(){	/* alias for beginTransaction() */
        return $this->beginTransaction();
    }


    public function oneValue($sql){  /* returns the first column of the first row of the query */
        return $this->prepexec($sql)->fetch(PDO::FETCH_NUM)[0];
    }

    
    public function oneCol($sql){  /* returns the first column of all rows of the query */
        return $this->prepexec($sql)->fetchAll(PDO::FETCH_COLUMN,0);
    }
    

    public function oneRow($sql,$mode=PDO::FETCH_OBJ){  /* returns the first row of a statement, as stdClass object  */
        return $this->prepexec($sql)->fetch($mode);
    }
    

    public function allRow($sql,$mode=PDO::FETCH_CLASS){  /* returns an array of stdClass objects - be sure to use only reasonable number of records. */
        return $this->prepexec($sql)->fetchAll($mode);
    }
    

    public function iterate($sql, $function, $mode=PDO::FETCH_OBJ){	/* pass every record object as parameter to $function  */
        switch($this->driver){
#            case "pgsql": return $this->iterate_cursor($sql, $function, $mode);	# FIXME buggy when using multiple cursors
            case "mysql": $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
        
        $st = $this->prepexec($sql);
        while($fo = $st->fetch($mode))
            if(false===$function($fo,$this))return false;
        return true;
    }
    
    
    public function iterate_cursor($sql, $function, $mode=PDO::FETCH_OBJ){	/* use cursor for huge resultsets */
        $cursor = "cur".uniqid();
        $cursorSql = "declare $cursor cursor for $sql";
        $this->begin();
        
        $curStmt = $this->prepare($cursorSql);
        $curStmt->execute();
        
        $inStmt = $this->prepare("fetch 1 from $cursor");
        
        $i=0;
        while($inStmt->execute() && ($fo=$inStmt->fetch($mode)) && (false!==$function($fo,$this)) )
            $i++;
        
        return $this->commit();
    }
    
    
    
    public function insert($table, $datArr, $returnCol=NULL){  /* insert data to a table. datArr is a mapped array. no BLOB support */
        $keys = @array_keys($datArr);
        $sql = @sprintf("insert into $table (\"%s\") values (:%s)", implode('","',$keys), implode(",:",$keys) );
        if($returnCol)$sql.=" returning $returnCol";

        $st = $this->prep($sql);
        
        // bind parameters
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value );
        
        $retid = $st->execute();
        
        if($returnCol){
            $retval= $st->fetchAll();
            if ( $retval && $retval[0] && $retval[0][0] ) $retid = $retval[0][0];
        }
        
        return $retid;
    }


    public function insert_multi($table, $datArrArr){  /* insert multiple data to a table. datArr is an array of mapped array. no BLOB support */
        $keys = @array_keys($datArrArr[0]);
        $sql = @sprintf("insert into $table (%s) values ", @implode(",",$keys) );
        
        // build values() statement
        foreach($datArrArr as $i=>$datArr)
            $valA[] = ":".implode("$i,:", $keys).$i;
        $sql.= "(".implode("), (",$valA).")";
        
        $st = $this->prep($sql);
        
        // bind parameters
        foreach($datArrArr as $i=>$datArr){
            foreach($datArr as $key => $value)
                $st->bindParam(":{$key}{$i}", $tmp=$value );
        }

        if(!$st->execute())
            return false;
        
        return ($ID=$this->lastInsertId())? $ID:true ;
    }


    public function update($table, $datArr, $cond){  /* update data in a table. datArr is a mapped array. $cond is the condition string */
        $keys = @array_keys($datArr);
        
        // will be the SET values in statement
        $sets = array();
        foreach($keys as $key)
            $sets[] = "{$key}=:{$key}";
            
        $sql = @sprintf("update {$table} set %s where {$cond}", implode(",",$sets) );
        
        $st = $this->prep($sql);
        
        // bind parameters
        $tmp=null;
        foreach($datArr as $key => $value)
            $st->bindParam(":$key", $tmp=$value);
        
        if(!$st->execute())return false;
        return $st->rowCount();
    }


     public function lastError(){      /* return last error message */
         return $this->errorInfo()[2];
     }



    public function insertupdate($table, $datArr, $cond, $returnCol=NULL){  /* update row in a table if exists, else insert */
        if($this->oneValue("select count(1) from $table where $cond"))
            return $this->update($table, $datArr, $cond);
        else
            return $this->insert($table, $datArr, $returnCol);
    }




    private function prep($sql){	/* tests errors in statement. drops error on failure */
        $this->lastSQL = $sql;
        if(!$st = $this->prepare($sql))throw new \Exception(
            "Prepare statement error: ".json_encode($this->errorInfo())." SQL=[$sql]"
        );
        return $st;
    }
    public $lastSQL;
    
    private function prepexec($sql){	/* safely prepares and executes statement */
        $st = $this->prep($sql);
        $st->execute();
        return $st;
    }




}
?>