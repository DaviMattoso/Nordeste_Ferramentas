<?php

// Teste temporário de desenvolvimento: retorna texto simples e tenta abrir a conexão
// compartilhada. Se database.php não lançar erro, a configuração básica está funcional.
header('Content-Type: text/plain; charset=UTF-8');
require_once __DIR__ . '/config/database.php';

echo 'Conexão com o banco de dados realizada com sucesso.';
