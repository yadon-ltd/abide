<?php
/*
  core/db.php — Database connection
  ──────────────────────────────────────────────────────────────
  Provides a single PDO connection to the database configured
  in config.php.

  Call db_connect() anywhere you need a DB handle:
    $pdo = db_connect();

  The connection is created once per request (singleton pattern)
  and reused on every subsequent call. It throws a PDOException
  on connection failure — catch it in your calling code or let
  it bubble up to a 500 page.

  Credentials are defined in config.php, not here.
  Direct HTTP access is blocked via public/.htaccess.
  ──────────────────────────────────────────────────────────────
*/


// ── Block direct browser access ───────────────────────────────
// Belt-and-suspenders alongside the .htaccess FilesMatch rule.
if (basename($_SERVER['PHP_SELF']) === 'db.php') {
    http_response_code(403);
    exit('Forbidden');
}


// ── Connection factory (singleton) ────────────────────────────
// Returns the same PDO instance on every call within a request.
// Reads DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHAR from config.php.
function db_connect(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHAR
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,     // throw on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,           // arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                      // real prepared statements
        ]);
    }

    return $pdo;
}
