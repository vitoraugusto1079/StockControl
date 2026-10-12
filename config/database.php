<?php
// ==============================================================
// PROJETO: StockControl
// ARQUIVO: config/database.php - Conexão PDO com MySQL
// ==============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'stockcontrol');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Retorna uma instância de conexão PDO com o MySQL
 * @return PDO
 */
function getConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em caso de erro na conexão, interrompe exibindo mensagem clara
            die("<strong>Erro de conexão com o Banco de Dados:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>Verifique se o MySQL está iniciado no XAMPP e se o banco 'stockcontrol' foi importado.");
        }
    }

    return $pdo;
}
