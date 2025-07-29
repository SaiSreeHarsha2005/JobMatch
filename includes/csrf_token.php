<?php
/**
 * CSRF Token Management Script
 *
 * This script handles the generation and validation of CSRF (Cross-Site Request Forgery) tokens.
 * It ensures that a unique token is available in the session for form submissions.
 *
 * IMPORTANT: session_start() should ideally be called once at the very beginning of your
 * application's entry point (e.g., index.php or a central bootstrap file) to ensure
 * session handling is initialized before any output is sent.
 */

// Ensure session is started before accessing $_SESSION superglobal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate a new CSRF token if one doesn't exist in the session
// This token will persist throughout the user's session.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 32 bytes = 64 hex characters
}

/**
 * Validates the CSRF token submitted from a form against the one stored in the session.
 * Uses hash_equals() for secure comparison to prevent timing attacks.
 *
 * @param string $token The CSRF token received from the form submission.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token($token) {
    
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/**

 *
 * @return string HTML hidden input tag for the CSRF token.
 */
function csrf_token_tag() {
    
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}
?>