<?php
declare(strict_types=1);

/**
 * Endpoint público consumido pela vitrine do site principal.
 *
 * Consulta os três posts mais recentes no MySQL, monta URLs relativas ao local
 * de instalação do blog e responde um JSON estável para `site_principal/script.js`.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/** Envia o payload JSON com o status HTTP indicado e encerra a requisição. */
function sendJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    exit;
}

/** Descobre a raiz pública do blog a partir do caminho real deste endpoint. */
function blogBasePath(): string
{
    $scriptName = is_string($_SERVER['SCRIPT_NAME'] ?? null)
        ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME'])
        : '';
    $blogDirectory = dirname(dirname($scriptName));

    if ($blogDirectory === '/' || $blogDirectory === '.' || $blogDirectory === '\\') {
        return '/';
    }

    return '/' . trim(str_replace('\\', '/', $blogDirectory), '/') . '/';
}

/**
 * Resolve a URL pública da thumbnail.
 * Caminhos fora de Images ou com segmentos de navegação recebem a imagem padrão.
 */
function blogImageUrl(string $thumbnail, string $basePath): string
{
    $thumbnail = trim(str_replace('\\', '/', $thumbnail), '/');
    $segments = explode('/', $thumbnail);
    $isSafeImage = count($segments) >= 2
        && $segments[0] === 'Images'
        && !in_array('.', $segments, true)
        && !in_array('..', $segments, true)
        && preg_match('/\.(?:jpe?g|png|webp)\z/i', $thumbnail) === 1;

    if (!$isSafeImage) {
        $segments = ['Images', 'posttopt1.png'];
    }

    return $basePath . implode('/', array_map('rawurlencode', $segments));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    sendJson(['error' => 'Método não permitido.'], 405);
}

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/post-utils.php';

    /*
     * Atualmente todo registro em `posts` é público. Se houver rascunhos no futuro,
     * este é o ponto que deverá filtrar apenas o status publicado.
     * O LIMIT 3 corresponde à quantidade de cards reservada na página institucional.
     */
    $result = $connection->query(
        'SELECT p.id, p.title, p.body, p.thumbnail, p.created_at, '
        . 'c.title AS category_title '
        . 'FROM posts AS p '
        . 'INNER JOIN categories AS c ON c.id = p.category_id '
        . 'ORDER BY p.created_at DESC, p.id DESC '
        . 'LIMIT 3'
    );

    $basePath = blogBasePath();
    $posts = [];

    while ($post = $result->fetch_assoc()) {
        $timestamp = strtotime((string) $post['created_at']);
        $posts[] = [
            'id' => (int) $post['id'],
            'title' => (string) $post['title'],
            'excerpt' => postExcerpt((string) $post['body']),
            'thumbnail_url' => blogImageUrl((string) $post['thumbnail'], $basePath),
            'category' => (string) $post['category_title'],
            'created_at' => $timestamp === false ? (string) $post['created_at'] : date(DATE_ATOM, $timestamp),
            'display_date' => postDisplayDate((string) $post['created_at']),
            'post_url' => $basePath . 'post.php?id=' . (int) $post['id'],
        ];
    }
    $result->free();

    sendJson(['posts' => $posts]);
} catch (Throwable $exception) {
    error_log('NF Blog API: falha ao listar posts recentes. Código: ' . $exception->getCode());
    sendJson(['error' => 'Não foi possível carregar os posts.'], 500);
}
