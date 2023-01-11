<?php
abstract class DB{
    private $host = "localhost"; 
    private $Username = "root"; 
    private $Password = ""; 
    private $DbName = "test"; 
    protected $conn;
    public function __construct(){ 
            try{ 
                $this->conn = new PDO("mysql:host=".$this->host.";dbname=".$this->DbName, $this->Username, $this->Password); 
                $this->conn -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
                //echo 'Connection successful';
            }catch(PDOException $e){ 
                http_response_code(503);
                die("Connection not successful: " . $e->getMessage()); 
            } 
    }
}
class CRUD extends DB{      
    public function __construct(){
        parent::__construct();
    }
    private function setColumnsValues($columns_values){
        if(is_array($columns_values)){
            $columnsValues ='';
            $i = 0; 
            foreach($columns_values as $key => $value){ 
                $pre = ($i > 0)?' AND ':''; 
                $columnsValues .= $pre.$key." = '".addslashes($value)."'"; 
                $i++; 
            }
            return $columnsValues;
        }
        else{
            return '1';
        }
    }
    /*
     fetch rows from table
     params: 
     $table =table name
     $search_conditions = array( 
        'where' => array( 
            column =>value 
        ), 
        'order_by'=>'column DESC or ASC',
        'start'=>int,
        'LIMIT'=>int,
        'return_type' => 'single' or 'count' or 'all'
    );
    */
    public function getRecordsFromTable($search_conditions = array(),$table){ 
        $sql = 'SELECT ' . (array_key_exists("select",$search_conditions)?$search_conditions['select']:'*'). ' FROM '.$table; 
        if(array_key_exists("where",$search_conditions)){ 
            $sql .= ' WHERE '.$this->setColumnsValues($search_conditions['where']) ;
        } 
        if(array_key_exists("order_by",$search_conditions)){ 
            $sql .= ' ORDER BY '.$search_conditions['order_by'];  
        } 
        if(array_key_exists("limit",$search_conditions))
        {
            if(array_key_exists("start",$search_conditions)){
                $sql .= ' LIMIT '.$search_conditions['start'].','.$search_conditions['limit'];  
            }
            else
            {
                $sql .= ' LIMIT '.$search_conditions['limit'];  
            }
        } 
        $query = $this->conn->prepare($sql); 
        $query->execute(); 
        if(array_key_exists("return_type",$search_conditions) && $search_conditions['return_type'] != 'all'){ 
            switch($search_conditions['return_type']){ 
                case 'count': 
                    $data = $query->rowCount(); 
                    break; 
                case 'single': 
                    $data = $query->fetch(PDO::FETCH_ASSOC); 
                    break; 
                default: 
                    $data = ''; 
            } 
        }else{ 
            if($query->rowCount() > 0){ 
                $data = $query->fetchAll(); 
            } 
        } 
        return empty($data)?false:$data;
    } 
     /*
        insert into table
        params:
        $table =table name
        $data=array( 
            'column' => 'value', 
            'column' => 'value', 
            'column' => 'value',
            ... 
            );  
     */
    public function insertRecord($data,$table){ 
        if(!empty($data) && is_array($data)){ 
            $columnString = implode(',', array_keys($data)); 
            $valueString = ":".implode(',:', array_keys($data)); 
            $sql = "INSERT INTO ".$table." (".$columnString.") VALUES (".$valueString.")"; 
            $query = $this->conn->prepare($sql); 
            foreach($data as $key=>$val){ 
                 $query->bindValue(':'.$key, $val); 
            } 
            $insert = $query->execute(); 
            return $insert?$this->conn->lastInsertId():false; 
        }else{ 
            return false; 
        } 
    } 
    /*
        update record
        params:
        $table = table name
        $updateConditions = array('column' => value); 
        $data=array( 
                'column' => 'value', 
                'column' => 'value', 
                'column' => 'value',
                ... 
            );  
    */
    public function updateRecord($data,$updateConditions,$table){ 
        if(!empty($data) && is_array($data)){ 
            $colvalSet='';
            $i=0;
            foreach($data as $key=>$val){ 
                $pre = ($i > 0)?', ':''; 
                $colvalSet .= $pre.$key."='".addslashes($val)."'"; 
                $i++; 
            }
            $whereSql = '';  
            if(!empty($updateConditions)){ 
                $whereSql .= ' WHERE ' .$this->setColumnsValues($updateConditions);  
            } 
            $sql = "UPDATE ".$table." SET ".$colvalSet.$whereSql; 
            echo $sql;
            $query = $this->conn->prepare($sql); 
            $update = $query->execute(); 
            return $update?$query->rowCount():false; 
        }else{ 
            return false; 
        } 
    }      
    /*
    delete record
    params:
    $table = table name
    $deleteConditions = array('column' => value); 
    */
    public function deleteRecord($deleteConditions,$table){ 
        $whereSql = ''; 
        if(!empty($deleteConditions)){ 
            $whereSql .= ' WHERE ' . $this->setColumnsValues($deleteConditions); 
        } 
        $sql = "DELETE FROM ".$table.$whereSql; 
        $delete = $this->conn->exec($sql); 
        return $delete?$delete:false; 
    } 
}
