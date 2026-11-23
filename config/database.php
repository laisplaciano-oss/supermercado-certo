<?php
declare(strict_types=1);

/*
 * Conexão automática para XAMPP:
 * - conecta ao servidor MySQL sem selecionar um banco;
 * - cria supermercado_app se ainda não existir;
 * - cria as tabelas necessárias;
 * - adiciona categorias iniciais sem duplicar registros.
 *
 * Padrão XAMPP: usuário root e senha vazia.
 */

$host = '127.0.0.1';
$db   = 'supermercado_app';
$user = 'root';
$pass = ''; // Se seu MySQL tiver senha, informe-a aqui.
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Primeiro conecta apenas ao servidor. Assim não falha se o banco não existir.
    $serverDsn = "mysql:host={$host};charset={$charset}";
    $server = new PDO($serverDsn, $user, $pass, $options);

    // Cria o banco automaticamente, caso ainda não exista.
    $server->exec(
        "CREATE DATABASE IF NOT EXISTS `{$db}`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );

    // Agora conecta ao banco criado.
    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Garante que as tabelas existam.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(80) NOT NULL UNIQUE,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS produtos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            descricao VARCHAR(500) NULL,
            preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            quantidade INT UNSIGNED NOT NULL DEFAULT 0,
            categoria_id INT UNSIGNED NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_produtos_categoria
                FOREIGN KEY (categoria_id)
                REFERENCES categorias(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT,
            INDEX idx_produtos_nome (nome),
            INDEX idx_produtos_categoria (categoria_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Categorias de exemplo para o primeiro acesso.
    $insert = $pdo->prepare("INSERT IGNORE INTO categorias (nome) VALUES (?)");
    foreach (['Mercearia', 'Hortifruti', 'Bebidas', 'Limpeza', 'Higiene pessoal'] as $categoria) {
        $insert->execute([$categoria]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    $detalhe = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit(
        '<div style="font-family:Arial,sans-serif;max-width:760px;margin:40px auto;padding:24px;border:1px solid #f0cbd9;border-radius:14px;background:#fff7fa;color:#563747">' .
        '<h2 style="color:#a85f7e">Não foi possível conectar ao MySQL</h2>' .
        '<p>Confira se o MySQL está iniciado no XAMPP. No XAMPP padrão, o usuário é <b>root</b> e a senha fica vazia.</p>' .
        '<p><b>Detalhe:</b> ' . $detalhe . '</p>' .
        '<p>Se você configurou uma senha no MySQL, edite <code>config/database.php</code> e altere a variável <code>$pass</code>.</p>' .
        '</div>'
    );
}
