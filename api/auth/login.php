<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/validate.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(
        false,
        'Método não permitido',
        null,
        405
    );
}

$dados = json_decode(file_get_contents('php://input'), true);

try {
    // Validar os dados do login
    $erros = validar_login($dados ?? []);

    if (!empty($erros)) {
        json_response(
            false,
            'Dados inválidos',
            $erros,
            422
        );
    }

    // Buscar o usuário pelo e-mail
    $sql = "SELECT id, nome, senha_hash FROM users WHERE email = :email";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':email' => trim($dados['email'])
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar se o usuário existe
    if (!$usuario) {
        json_response(
            false,
            'Credenciais inválidas',
            null,
            401
        );
    }

    // Verificar a senha
    if (!password_verify($dados['senha'], $usuario['senha_hash'])) {
        json_response(
            false,
            'Credenciais inválidas',
            null,
            401
        );
    }

    // Salvar dados do usuário na sessão
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];

    // Responder sucesso sem senha_hash
    json_response(
        true,
        'Login realizado com sucesso',
        [
            'id' => $usuario['id'],
            'nome' => $usuario['nome']
        ],
        200
    );

} catch (PDOException $e) {
    // Erro interno sem mostrar detalhes do banco
    json_response(
        false,
        'Erro interno do servidor',
        null,
        500
    );
}