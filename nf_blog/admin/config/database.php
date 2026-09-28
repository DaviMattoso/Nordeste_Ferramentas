<?php
/**
 * Ponte de compatibilidade para a conexão usada por arquivos administrativos.
 * Reutiliza `config/database.php`, sem duplicar credenciais ou abrir outra conexão.
 */
require_once __DIR__ . '/../../config/database.php';
