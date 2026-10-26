
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificar_autenticacao(): void
{
    if (!isset($_SESSION['usuario_id'])) {
        require_once __DIR__ . '/response.php';

        json_response(
            false,
            'Não autenticado',
            null,
            401
        );
    }
}
