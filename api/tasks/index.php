
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/helpers/response.php';
require_once __DIR__ . '/../../src/helpers/auth.php';
require_once __DIR__ . '/../../src/helpers/validate.php';
require_once __DIR__ . '/../../src/models/Task.php';

verificar_autenticacao();

$userId = (int) $_SESSION['user_id'];
$model = new Task($pdo);
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    if ($metodo === 'GET') {
        // Vamos implementar a listagem.
    } elseif ($metodo === 'POST') {
        // Vamos implementar o cadastro.
    } else {
        header('Allow: GET, POST');
        json_response(false, 'Método não permitido', null, 405);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_response(false, 'Erro interno do servidor', null, 500);
}

if ($metodo === 'POST') {
    $dados = ler_json_corpo();
    if ($dados === null) {
        json_response(false, 'Corpo JSON inválido', null, 400);
    }
 
    $projectId = filter_var(
        $dados['project_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $titulo = $dados['titulo'] ?? null;
    $descricao = $dados['descricao'] ?? null;
    $prioridade = $dados['prioridade'] ?? 'media';
    $dataInicio = $dados['data_inicio'] ?? null;
    $dataPrazo = $dados['data_prazo'] ?? null;
 
   
$erros = [];

if ($projectId === false) {
    $erros[] = 'project_id deve ser um número inteiro positivo.';
}

if (!is_string($titulo)) {
    $erros[] = 'O título deve ser um texto.';
} else {
    $titulo = trim($titulo);

    if (mb_strlen($titulo) < 1 || mb_strlen($titulo) > 200) {
        $erros[] = 'O título deve ter entre 1 e 200 caracteres.';
    }
}

if ($descricao !== null && !is_string($descricao)) {
    $erros[] = 'A descrição deve ser um texto ou null.';
}

if (!in_array($prioridade, Task::PRIORIDADES, true)) {
    $erros[] = 'Prioridade inválida.';
}

if ($dataInicio !== null &&
    (!is_string($dataInicio) || !validar_data_iso($dataInicio))) {
    $erros[] = 'Data de início inválida.';
}

if ($dataPrazo !== null &&
    (!is_string($dataPrazo) || !validar_data_iso($dataPrazo))) {
    $erros[] = 'Data de prazo inválida.';
}

if (
    is_string($dataInicio) &&
    is_string($dataPrazo) &&
    validar_data_iso($dataInicio) &&
    validar_data_iso($dataPrazo) &&
    $dataPrazo < $dataInicio
) {
    $erros[] = 'O prazo não pode ser anterior ao início.';
}

if ($erros !== []) {
    json_response(false, 'Dados inválidos', $erros, 400);
}

if ($descricao !== null) {
    $descricao = trim($descricao);
}


$tarefa = $model->criar(
    $projectId,
    $titulo,
    $descricao,
    $prioridade,
    $dataInicio,
    $dataPrazo,
    $userId
);

if ($tarefa === null) {
    json_response(false, 'Projeto não encontrado', null, 404);
}


json_response(true, 'Tarefa criada', $tarefa, 201);
}