<?php

/**
 * Reads environment variables, falling back to .env file if needed.
 * 
 * @param string $key Environment variable key
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env_or_default($key, $default = null) {
    // First, check environment variables
    $v = getenv($key);
    if ($v !== false && $v !== '') {
        return $v;
    }
    
    // Then, check .env file
    static $ini = null;
    if ($ini === null && file_exists(__DIR__ . '/.env')) {
        $ini = parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW);
    }
    
    if ($ini && array_key_exists($key, $ini)) {
        return $ini[$key];
    }
    
    return $default;
}

