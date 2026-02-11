<?php
/**
 * Hostel Management System Helper Functions
 * 
 * Contains common utility functions used throughout the application
 */

/**
 * Sanitizes input data to prevent XSS and other injections
 * 
 * @param mixed $data The input data to sanitize
 * @param string $type The type of sanitization (html|sql|email|url)
 * @return mixed The sanitized data
 */
function cleanInput($data, $type = 'html') {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    
    $data = trim($data);
    
    switch ($type) {
        case 'sql':
            // For SQL, we should use prepared statements instead
            // This is just a basic example
            $data = str_replace("'", "''", $data);
            $data = str_replace("\\", "\\\\", $data);
            break;
            
        case 'email':
            $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            break;
            
        case 'url':
            $data = filter_var($data, FILTER_SANITIZE_URL);
            break;
            
        case 'html':
        default:
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            break;
    }
    
    return $data;
}

/**
 * Builds a query string while preserving existing parameters
 * 
 * @param array $newParams New parameters to add/overwrite
 * @param array $removeParams Parameters to remove
 * @return string The generated query string
 */
function buildQueryString($newParams = [], $removeParams = []) {
    // Start with current GET parameters
    $params = $_GET;
    
    // Add/overwrite new parameters
    foreach ($newParams as $key => $value) {
        $params[$key] = $value;
    }
    
    // Remove specified parameters
    foreach ($removeParams as $param) {
        if (isset($params[$param])) {
            unset($params[$param]);
        }
    }
    
    return http_build_query($params);
}

/**
 * Redirects to a new page with optional status code
 * 
 * @param string $url The URL to redirect to
 * @param int $statusCode HTTP status code (default: 302)
 */
function redirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit();
}

/**
 * Checks if a string starts with a given substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool True if $haystack starts with $needle
 */
function startsWith($haystack, $needle) {
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

/**
 * Checks if a string ends with a given substring
 * 
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for
 * @return bool True if $haystack ends with $needle
 */
function endsWith($haystack, $needle) {
    return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

/**
 * Generates a random string of specified length
 * 
 * @param int $length Length of the random string
 * @return string The generated random string
 */
function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

/**
 * Validates an email address
 * 
 * @param string $email The email address to validate
 * @return bool True if email is valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Formats a date for display
 * 
 * @param string $date The date string to format
 * @param string $format The format to use (default: 'Y-m-d H:i:s')
 * @return string The formatted date
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Checks if a request is AJAX
 * 
 * @return bool True if the request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Gets the current URL
 * 
 * @param bool $withQueryString Include query string (default: true)
 * @return string The current URL
 */
function getCurrentUrl($withQueryString = true) {
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    if (!$withQueryString) {
        $url = strtok($url, '?');
    }
    
    return $url;
}

/**
 * Converts special characters to HTML entities recursively for arrays
 * 
 * @param mixed $data The data to convert
 * @return mixed The converted data
 */
function htmlspecialcharsRecursive($data) {
    if (is_array($data)) {
        return array_map('htmlspecialcharsRecursive', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks if a string contains HTML
 * 
 * @param string $string The string to check
 * @return bool True if the string contains HTML tags
 */
function containsHtml($string) {
    return $string !== strip_tags($string);
}

/**
 * Truncates a string to a specified length
 * 
 * @param string $string The string to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append if truncated
 * @return string The truncated string
 */
function truncateString($string, $length = 100, $suffix = '...') {
    if (mb_strlen($string) <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length) . $suffix;
}

/**
 * Gets the client IP address
 * 
 * @return string The IP address
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}