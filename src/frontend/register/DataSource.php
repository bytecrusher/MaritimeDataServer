<?php
/**
 * Copyright (C) Phppot
 *
 * Distributed under 'The MIT License'
 * In essence you can modify, distribute, and use for commercial purposes.
 * Though not mandatory, you are requested to
 * attribute Phppot URL https://phppot.com in your code or website.
 */

//require_once(__DIR__ . '/../../configuration.php');

/**
 * A lightweight generic datasource class for handling database operations.
 * Uses MySqli or PDO and PreparedStatements.
 *
 * @version 3.2 - Namespace removed.
 */
class DataSource
{
    //const $config = new configuration();
    const HOST = 'localhost';
    //const HOST = $config::$db_host
    const USERNAME = 'user';
    //const USERNAME = $config::$db_user
    const PASSWORD = 'pw';
    //const PASSWORD = $config::$db_password
    const DATABASENAME = 'dbname';
    //const DATABASENAME = $config::$db_name
    private $connection;

    /**
     * PHP implicitly takes care of cleanup for default database connection types.
     * So no need to close connection explicitly.
     *
     * Singletons not required in PHP as there is no concept of shared memory.
     * Every object lives only for a request.
     *
     * Keeping things simple and that works!
     */
    function __construct()
    {
        $this->connection = $this->getConnection();
    }

    /**
     * Returns a database MySQLi instance.
     *
     * @return \mysqli
     */
    public function getConnection()
    {
        $connection = new \mysqli(self::HOST, self::USERNAME, self::PASSWORD, self::DATABASENAME);

        if (mysqli_connect_errno()) {
            trigger_error("Problem with connecting to database with MySQLi.");
        }

        $connection->set_charset("utf8");
        return $connection;
    }

    /**
     * Returns a database PDO instance.
     *
     * @return \PDO
     */
    public function getPdoConnection()
    {
        $connection = FALSE;
        try {
            $dsn = 'mysql:host=' . self::HOST . ';dbname=' . self::DATABASENAME;
            $connection = new \PDO($dsn, self::USERNAME, self::PASSWORD);
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\Exception $e) {
            exit("PDO database connection error: " . $e->getMessage());
        }
        return $connection;
    }

    /**
     * Bind prameters and values to a sql statement.
     *
     * @param string $statement
     * @param string $paramType:
     *            parameter types as a contigous string
     * @param array $paramArray
     */
    public function bindQueryParams($statement, $paramType, $paramArray = array())
    {
        $paramValueReference = null;
        $paramValueReference[] = & $paramType;
        for ($i = 0; $i < count($paramArray); $i ++) {
            $paramValueReference[] = & $paramArray[$i];
        }
        call_user_func_array(array(
            $statement,
            "bind_param"
        ), $paramValueReference);
    }

    /**
     * Common entry point to execute query for all CRUD operations.
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     * @return \mysqli_stmt
     */
    public function execute($query, $paramType = "", $paramArray = array())
    {
        $statement = $this->connection->prepare($query);
        if (! empty($paramType) && ! empty($paramArray)) {
            $this->bindQueryParams($statement, $paramType, $paramArray);
        }
        $statement->execute();
        return $statement;
    }

    /**
     * To read database.
     *
     * @param string $query
     * @param string $paramType
     * @param array $paramArray
     * @return array
     */
    public function select($query, $paramType = "", $paramArray = array())
    {
        $statement = $this->execute($query, $paramType, $paramArray);
        $result = $statement->get_result();

        if ($result->num_rows > 0) {
            $resultset = null;
            while ($row = $result->fetch_assoc()) {
                $resultset[] = $row;
            }
            return $resultset;
        }
    }

    public function insert($query, $paramType, $paramArray)
    {
        $statement = $this->execute($query, $paramType, $paramArray);
        return $statement->insert_id;
    }

    public function update($query, $paramType, $paramArray)
    {
        $statement = $this->execute($query, $paramType, $paramArray);
        $affectedRows = $statement->affected_rows;
        return $affectedRows;
    }

    public function delete($query, $paramType, $paramArray)
    {
        $statement = $this->execute($query, $paramType, $paramArray);
        $affectedRows = $statement->affected_rows;
        return $affectedRows;
    }

    public function getRecordCount($query, $paramType = "", $paramArray = array())
    {
        $statement = $this->execute($query, $paramType, $paramArray);
        $statement->store_result();
        return $statement->num_rows;
    }
}
