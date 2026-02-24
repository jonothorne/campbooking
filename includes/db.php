<?php
/**
 * Database Initialization
 * Include this file to get database access
 */

// Require composer autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load constants and configuration
require_once dirname(__DIR__) . '/config/constants.php';

// Load database class
require_once dirname(__DIR__) . '/config/database.php';

// Initialize database connection
$db = Database::getInstance();

/**
 * Global database helper
 * Usage: $db->query($sql, $params);
 */
return $db;
