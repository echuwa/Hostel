<?php
// Include the master configuration file
require_once(__DIR__ . '/../../includes/config.php');

// Enable/disable admin registration (specific to admin module)
if(!defined('ALLOW_ADMIN_REGISTRATION')) {
    define('ALLOW_ADMIN_REGISTRATION', true);
}
?>
