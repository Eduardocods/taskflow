<?php

function validar_cadastro(array $dados): array
{
    $erros = [];

    // Validar nome
    if (empty(trim($dados['nome'] ?? ''))) {
        $erros[] = 'O nome é obrigatório.';
    } else {
        $dados['nome'] = trim($dados['nome']);
    }

    // Validar e-mail
    $email = trim($dados['email'] ?? '');

    if (empty($email)) {
        $erros[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O e-mail informado é inválido.';
    }

    // Validar senha
    $senha = $dados['senha'] ?? '';

    if (empty($senha)) {
        $erros[] = 'A senha é obrigatória.';
    } elseif (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter no mínimo 8 caracteres.';
    }

    return $erros;
}

function validar_login(array $dados): array
{
    $erros = [];

    // Validar e-mail
    $email = trim($dados['email'] ?? '');

    if (empty($email)) {
        $erros[] = 'O e-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'O e-mail informado é inválido.';
    }

    // Validar senha
    $senha = $dados['senha'] ?? '';

    if (empty($senha)) {
        $erros[] = 'A senha é obrigatória.';
    }

    return $erros;
}