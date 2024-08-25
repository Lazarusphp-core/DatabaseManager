<?php

namespace LazarusPhp\DatabaseManager;

use App\System\App;
use LazarusPhp\DatabaseManager\CredentialsManager;
use App\System\Core;
use PDO;
use PDOException;
use PSpell\Config;

abstract class Database extends CredentialsManager
{
    public $config;
    
    private $sql;
    private  $connection;
    public  $is_connected = false;
    private $stmt;


    // Params
    private  $param = array();
    // Db Credntials

    private  $type;
    private  $hostname;
    private  $username;
    private  $password;
    private  $dbname;


    public function __construct()
    {
        $this->config = self::LoadConfig();
        if (is_file($this->config) && file_exists($this->config)) 
        {
            $this->type = self::GetType();
            $this->hostname = self::GetHostname();
            $this->username = self::GetUsername();
            $this->password = self::GetPassword();
            $this->dbname = self::GetDbName();
        } else {
            trigger_error(E_ERROR,"Config File Does not Exist");
        }

        try {
            // Manage Credentials
            if ($this->is_connected !== true) {
                $this->is_connected = true;
                $this->connection = new PDO($this->Dsn(), $this->username, $this->password, $this->Options());
            }
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }


    }
    

    public function connect()
    {
        return $this->connection;
    }
    public function __destruct()
    {
    }

    public function CloseDb()
    {
        $this->connection = null;
    }

    public function sql($sql,$params=null)
    {
        $this->sql = $sql;
        !is_null($params) ?  $this->param = $params : false;
        return $this;
    }
    
    public function GenerateQuery($sql=null, $array = [])
    {
        !is_null($sql) ? $this->sql = $sql : false;
        // Get the Params
        if (!empty($array)) $this->param = $array;

        // Check there is a connection
        try {
            $this->stmt = $this->connection->prepare($this->sql);
            if (!empty($this->param)) $this->BindParams();
            $this->param = [];
            $this->stmt->execute();
            return $this->stmt;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage() . $e->getCode());
        }
    }

    final public function BindParams()
    {
        if (!empty($this->param)) {
            // Prepare code
            foreach ($this->param as $key => $value) {
                $type = $this->GetParamTpe($value);
                $this->stmt->bindValue(":$key", $value,$type);
            }
        }
    }

    private function GetParamTpe($value)
    {
        switch ($value) {
            case is_bool($value):
                return PDO::PARAM_BOOL;
            case is_null($value):
                return PDO::PARAM_NULL;
            case is_int($value):
                return PDO::PARAM_INT;
            case is_string($value):
                return PDO::PARAM_STR;
            default;
                break;
        }
    }


    public function AddParams($name, $value)
    {
        $this->param[$name] = $value;
        return $this;
    }

/**
 * Undocumented function
 *
 * @param [type] $type
 * @return void
 * Use Sql Function to chainload the following functions 
 * Updated 18/06/2024
 */
    public function One($type = PDO::FETCH_OBJ)
    {
        $stmt = $this->GenerateQuery();
        return $stmt->fetch($type);
    }

    public function All($type = PDO::FETCH_OBJ)
    {
        $stmt = $this->GenerateQuery();
        return $stmt->fetchAll($type);
    }

    public function FetchColumn()
    {
        $stmt = $this->GenerateQuery();
        return $stmt->fetchColumn();
    }

    public function RowCount()
    {
        $stmt = $this->GenerateQuery();
        return $stmt->rowCount();
    }


    public function Options()
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return $options;
    }

    private function Dsn()
    {
        return $this->type . ":host=" . $this->hostname . ";dbname=" . $this->dbname;
    }
}
