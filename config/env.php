<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file
 * 
 * Note: Only sets variables that are not already defined.
 * Variables set to empty string ('') or '0' in the environment
 * will NOT be overwritten by .env file values.
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("loadEnv: Failed to read .env file at $path");
        return false;
    }
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // Set environment variable if not already set
            if (getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    return true;
}

// Load .env file from project root
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);
