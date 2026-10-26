
<?php

require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/models/Project.php';

verificar_autenticacao();

$userId = (int) $_SESSION['usuario_id'];
$model = new Project($pdo);
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {

        $projetos = $model->listar($userId);

        json_response(
            true,
            'Projetos listados com sucesso',
            $projetos
        );

    } elseif ($metodo === 'POST') {

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
            json_response(
                false,
                'O corpo deve ser um objeto JSON',
                null,
                400
            );
        }

        $nome = $dados['nome'] ?? null;
        $descricao = $dados['descricao'] ?? null;
        $erros = [];

        if (!is_string($nome)) {
            $erros[] = 'nome: deve ser texto';
        } else {
            $nome = trim($nome);

            $tamanho = mb_strlen($nome, 'UTF-8');

            if ($tamanho < 1 || $tamanho > 150) {
                $erros[] =
                    'nome: deve ter de 1 a 150 caracteres';
            }
        }

        if ($descricao !== null && !is_string($descricao)) {
            $erros[] = 'descricao: deve ser texto ou null';
        } elseif (is_string($descricao)) {
            $descricao = trim($descricao);

            if ($descricao === '') {
                $descricao = null;
            }
        }

        if ($erros) {
            json_response(
                false,
                'Dados inválidos',
                $erros,
                422
            );
        }

        $projeto = $model->criar(
            $nome,
            $descricao,
            $userId
        );

        json_response(
            true,
            'Projeto criado com sucesso',
            $projeto,
            201
        );

    } else {

        header('Allow: GET, POST');

        json_response(
            false,
            'Método não permitido',
            null,
            405
        );
    }

} catch (PDOException | RuntimeException $e) {

    error_log($e->getMessage());

    json_response(
        false,
        'Erro interno do servidor',
        null,
        500
    );
}
