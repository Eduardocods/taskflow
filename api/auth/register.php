
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(false, 'Método não permitido', null, 405);
}

$corpo = file_get_contents('php://input');

try {
    $dados = json_decode(
        $corpo,
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException $e) {
    json_response(false, 'JSON inválido', null, 400);
}

if (!is_array($dados)) {
    json_response(false, 'Corpo JSON inválido', null, 400);
}

$nome = $dados['nome'] ?? null;
$email = $dados['email'] ?? null;
$senha = $dados['senha'] ?? null;

$erros = [];

if (!is_string($nome) || trim($nome) === '') {
    $erros[] = 'Nome obrigatório';
}

if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
    $erros[] = 'E-mail inválido';
}

if (!is_string($senha) || strlen($senha) < 8) {
    $erros[] = 'A senha deve ter pelo menos 8 caracteres';
}

if ($erros) {
    json_response(false, 'Dados inválidos', $erros, 422);
}

$nome = trim($nome);
$email = trim($email);

try {
    $consulta = $pdo->prepare(
        'SELECT id FROM users WHERE email = :email'
    );

    $consulta->execute(['email' => $email]);

    if ($consulta->fetch(PDO::FETCH_ASSOC)) {
        json_response(false, 'E-mail já cadastrado', null, 409);
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = 'INSERT INTO users (nome, email, senha_hash)
            VALUES (:nome, :email, :senha_hash)
            RETURNING id, nome, email';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'nome' => $nome,
        'email' => $email,
        'senha_hash' => $senhaHash
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    json_response(
        true,
        'Usuário cadastrado com sucesso',
        $usuario,
        201
    );

} catch (PDOException $e) {
    error_log($e->getMessage());

    json_response(
        false,
        'Erro interno do servidor',
        null,
        500
    );
}
