
<?php

require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/models/Project.php';

verificar_autenticacao();

$userId = (int) $_SESSION['usuario_id'];
$model = new Project($pdo);
$metodo = $_SERVER['REQUEST_METHOD'];

$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    json_response(
        false,
        'Informe um ID de projeto válido',
        null,
        400
    );
}

try {
    if ($metodo === 'GET') {
        $projeto = $model->buscarPorId($id, $userId);

        if ($projeto === false) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(true, 'Projeto encontrado', $projeto);

    } elseif ($metodo === 'PUT') {
        try {
            $dados = json_decode(
                file_get_contents('php://input'),
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
        $descricao = $dados['descricao'] ?? null;

        if (
            !is_string($nome) ||
            trim($nome) === '' ||
            mb_strlen(trim($nome), 'UTF-8') > 150
        ) {
            json_response(
                false,
                'O nome deve ter de 1 a 150 caracteres',
                null,
                422
            );
        }

        if ($descricao !== null && !is_string($descricao)) {
            json_response(
                false,
                'A descrição deve ser texto ou null',
                null,
                422
            );
        }

        $descricao = is_string($descricao)
            ? trim($descricao)
            : null;

        if ($descricao === '') {
            $descricao = null;
        }

        $projeto = $model->atualizar(
            $id,
            $userId,
            trim($nome),
            $descricao
        );

        if ($projeto === false) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(true, 'Projeto atualizado com sucesso', $projeto);

    } elseif ($metodo === 'DELETE') {
        $excluido = $model->excluir($id, $userId);

        if (!$excluido) {
            json_response(false, 'Projeto não encontrado', null, 404);
        }

        json_response(
            true,
            'Projeto excluído com sucesso',
            ['id' => $id]
        );

    } else {
        header('Allow: GET, PUT, DELETE');

        json_response(
            false,
            'Método não permitido',
            null,
            405
        );
    }

} catch (PDOException $e) {
    error_log($e->getMessage());

    json_response(
        false,
        'Erro interno do servidor',
        null,
        500
    );
}
