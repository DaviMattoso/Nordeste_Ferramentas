<?php
declare(strict_types=1);

/**
 * Ponto futuro para geração e validação de tokens CSRF.
 *
 * Formulários que alterarem estado deverão validar um token criptográfico da
 * sessão exclusiva do Sistema de Aluguel antes de executar qualquer operação.
 */
