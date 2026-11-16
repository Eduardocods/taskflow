
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

    // LISTAR TAREFAS - GET
    if ($metodo === 'GET') {

        $filtros = [];

        // Filtrar por projeto
        if (isset($_GET['project_id'])) {
            $projectId = filter_var(
                $_GET['project_id'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($projectId === false) {
                json_response(false, 'project_id inválido', null, 400);
            }

            $filtros['project_id'] = $projectId;
        }

        // Filtrar por status
        if (isset($_GET['status'])) {
            $status = $_GET['status'];

            if (
                !is_string($status) ||
                !in_array($status, Task::STATUS, true)
            ) {
                json_response(false, 'status inválido', null, 400);
            }

            $filtros['status'] = $status;
        }

        // Filtrar por prioridade
        if (isset($_GET['prioridade'])) {
            $prioridade = $_GET['prioridade'];

            if (
                !is_string($prioridade) ||
                !in_array($prioridade, Task::PRIORIDADES, true)
            ) {
                json_response(false, 'prioridade inválida', null, 400);
            }

            $filtros['prioridade'] = $prioridade;
        }

        // Filtrar por limite
        if (isset($_GET['limite'])) {
            $limite = filter_var(
                $_GET['limite'],
                FILTER_VALIDATE_INT,
                ['options' => [
                    'min_range' => 1,
                    'max_range' => 100
                ]]
            );

            if ($limite === false) {
                json_response(false, 'limite inválido', null, 400);
            }

            $filtros['limite'] = $limite;
        }

        // Buscar tarefas
        $tarefas = $model->listar($userId, $filtros);

        json_response(true, 'Tarefas listadas', $tarefas, 200);

    // CADASTRAR TAREFAS - POST
    } elseif ($metodo === 'POST') {

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

        // Validar project_id
        if ($projectId === false) {
            $erros[] = 'project_id deve ser um número inteiro positivo.';
        }

        // Validar título
        if (!is_string($titulo)) {
            $erros[] = 'O título deve ser um texto.';
        } else {
            $titulo = trim($titulo);

            if (mb_strlen($titulo) < 1 || mb_strlen($titulo) > 200) {
                $erros[] = 'O título deve ter entre 1 e 200 caracteres.';
            }
        }

        // Validar descrição
        if ($descricao !== null && !is_string($descricao)) {
            $erros[] = 'A descrição deve ser um texto ou null.';
        }

        // Validar prioridade
        if (!in_array($prioridade, Task::PRIORIDADES, true)) {
            $erros[] = 'Prioridade inválida.';
        }

        // Validar data de início
        if (
            $dataInicio !== null &&
            (!is_string($dataInicio) || !validar_data_iso($dataInicio))
        ) {
            $erros[] = 'Data de início inválida.';
        }

        // Validar data de prazo
        if (
            $dataPrazo !== null &&
            (!is_string($dataPrazo) || !validar_data_iso($dataPrazo))
        ) {
            $erros[] = 'Data de prazo inválida.';
        }

        // Comparar datas
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

        // Aplicar trim na descrição
        if ($descricao !== null) {
            $descricao = trim($descricao);
        }

        // Criar tarefa
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

    } else {

        header('Allow: GET, POST');
        json_response(false, 'Método não permitido', null, 405);
    }

} catch (PDOException $e) {

    error_log($e->getMessage());
    json_response(false, 'Erro interno do servidor', null, 500);
}
