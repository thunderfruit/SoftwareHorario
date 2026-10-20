<?php
require_once dirname(__DIR__) . '/backend/auth.php';

function e(?string $value, string $empty = '—'): string
{
    return htmlspecialchars($value ?? $empty, ENT_QUOTES, 'UTF-8');
}

// Path of the running page relative to frontend/, e.g. "admin/rooms.php".
function currentPage(): string
{
    return substr(realpath($_SERVER['SCRIPT_FILENAME']), strlen(__DIR__) + 1);
}

// Relative URL to a page under frontend/, so the app works at any mount point (/frontend/, /SoftwareHorario/, ...).
function url(string $path): string
{
    return str_repeat('../', substr_count(currentPage(), '/')) . $path;
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

// Groups rows by a column, keeping the order in which each group first appears.
function groupBy(array $rows, string $key): array
{
    $groups = [];
    foreach ($rows as $row) {
        $groups[$row[$key]][] = $row;
    }
    return $groups;
}

// Buttons over a tree of <details class="node">: expand/collapse all, and print (prints only what is expanded).
function pageTools(bool $tree = true): void
{
    ?>
    <p class="tools no-print">
        <?php if ($tree): ?>
            <button type="button" class="button secondary" aria-pressed="false" onclick="
                const open = this.getAttribute('aria-pressed') !== 'true';
                document.querySelectorAll('details.node').forEach(d => d.open = open);
                this.setAttribute('aria-pressed', open);
                this.textContent = open ? 'Contraer todo' : 'Expandir todo';">Expandir todo</button>
        <?php endif; ?>
        <button type="button" class="button secondary" onclick="window.print()">Imprimir</button>
    </p>
    <?php
}

// $aside prints what goes to the right of the title, usually the filters form.
function pageHeader(string $title, ?callable $aside = null): void
{
    $user = currentUser();
    $links = [['index.php', 'Horario'], ['rooms.php', 'Horario por salón'], ['structure.php', 'Estructura']];
    if (($user['rol'] ?? null) === 'Administrador') {
        array_push($links, ['admin/sections.php', 'Carga horaria'], ['admin/teachers.php', 'Docentes'], ['admin/rooms.php', 'Salones']);
    } elseif (($user['rol'] ?? null) === 'Docente') {
        array_push($links, ['teacher/index.php', 'Mi horario'], ['teacher/preferences.php', 'Horario deseado']);
    }
    $flash = takeFlash();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Horarios UNT</title>
    <link rel="icon" type="image/png" href="<?= e(url('img/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Lato:wght@400;700&display=swap">
    <link rel="stylesheet" href="<?= e(url('styles.css')) ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(url('index.php')) ?>">
        <img src="<?= e(url('img/escudo.png')) ?>" alt="" width="59" height="48">
        <span class="brand-text">
            <span class="brand-name">Universidad Nacional de Trujillo</span>
            <span class="brand-app">Sistema de Horarios</span>
        </span>
    </a>
    <nav>
        <?php foreach ($links as [$href, $label]): ?>
            <a href="<?= e(url($href)) ?>"<?= $href === currentPage() ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
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
    <div class="page-head">
        <h1><?= e($title) ?></h1>
        <?php if ($aside) {
            $aside();
        } ?>
    </div>
    <?php if ($flash): ?>
        <p class="flash <?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['text']) ?></p>
    <?php endif;
}

function pageFooter(): void
{
    ?>
</main>
<footer class="footer">
    <img src="<?= e(url('img/escudo.png')) ?>" alt="" width="40" height="32">
    <span>Universidad Nacional de Trujillo · Sistema de Horarios</span>
</footer>
</body>
</html>
    <?php
}
