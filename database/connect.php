<?php 

// This file contains the database access information. 
// This file also establishes a connection to MySQL, 
// selects the database, and sets the encoding.

class DatabaseConnection {
    private static $instance = null;
    private $dbc; // Database connection

    private function __construct() { 
        $host = "localhost";
        $user = "root";
        $password = "107Goat!";
        $database = "employee_dashboard_db";

        // Establish connection
        $this->dbc = new mysqli($host, $user, $password, $database);

        if ($this->dbc->connect_error) {
            die('Could not connect to MySQL: ' . $this->dbc->connect_error);
        }

        $this->dbc->set_charset('utf8');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance;
    }

    // Method to get the database connection
    public function getConnection() {
        return $this->dbc;
    }
}

$dbc = DatabaseConnection::getInstance()->getConnection();
?>
