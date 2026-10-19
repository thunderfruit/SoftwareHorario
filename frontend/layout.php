<?php
require_once dirname(__DIR__) . '/backend/auth.php';

function e(?string $value, string $empty = '—'): string
{
    return htmlspecialchars($value ?? $empty, ENT_QUOTES, 'UTF-8');
}

// Relative URL to a page under frontend/, so the app works at any mount point (/frontend/, /SoftwareHorario/, ...).
function url(string $path): string
{
    $script = substr(realpath($_SERVER['SCRIPT_FILENAME']), strlen(__DIR__) + 1);
    return str_repeat('../', substr_count($script, '/')) . $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function requireRole(string $role): array
{
    $user = currentUser();
    if ($user === null) {
        redirect('login.php');
    }
    if ($user['rol'] !== $role) {
        http_response_code(403);
        exit('No tienes permiso para ver esta página.');
    }
    return $user;
}

// Reads an optional integer from GET/POST input; null when missing or not a positive integer.
function intParam(array $source, string $key): ?int
{
    $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? null : $value;
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function selectOptions(array $rows, string $valueKey, string $labelKey, $selected, string $placeholder = ''): string
{
    $html = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($rows as $row) {
        $isSelected = (string) $row[$valueKey] === (string) $selected ? ' selected' : '';
        $html .= '<option value="' . e((string) $row[$valueKey]) . '"' . $isSelected . '>' . e($row[$labelKey]) . '</option>';
    }
    return $html;
}

function pageHeader(string $title): void
{
    $user = currentUser();
    $links = [['index.php', 'Horario por escuela'], ['rooms.php', 'Horario por salón']];
    if (($user['rol'] ?? null) === 'Administrador') {
        array_push($links, ['admin/sections.php', 'Secciones'], ['admin/teachers.php', 'Docentes'], ['admin/rooms.php', 'Salones']);
    } elseif (($user['rol'] ?? null) === 'Docente') {
        $links[] = ['teacher/index.php', 'Mi horario'];
    }
    $flash = takeFlash();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Horarios UNT</title>
    <link rel="stylesheet" href="<?= e(url('styles.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(url('index.php')) ?>">Horarios <span>UNT</span></a>
    <nav>
        <?php foreach ($links as [$href, $label]): ?>
            <a href="<?= e(url($href)) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <?php if ($user): ?>
            <form method="post" action="<?= e(url('logout.php')) ?>" class="inline">
                <?= csrfField() ?>
                <button type="submit" class="link">Salir (<?= e($user['nombre_usuario']) ?>)</button>
            </form>
        <?php else: ?>
            <a href="<?= e(url('login.php')) ?>">Ingresar</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <h1><?= e($title) ?></h1>
    <?php if ($flash): ?>
        <p class="flash <?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['text']) ?></p>
    <?php endif;
}

function pageFooter(): void
{
    ?>
</main>
</body>
</html>
    <?php
}
