<?php
require_once __DIR__ . '/layout.php';

const HOME_BY_ROLE = ['Administrador' => 'admin/sections.php', 'Docente' => 'teacher/index.php'];

if ($user = currentUser()) {
    redirect(HOME_BY_ROLE[$user['rol']]);
}

$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $username = trim($_POST['username'] ?? '');
    if (login($pdo, $username, $_POST['password'] ?? '')) {
        redirect(HOME_BY_ROLE[currentUser()['rol']]);
    }
    flash('error', 'Usuario o contraseña incorrectos.');
}

pageHeader('Ingresar');
?>
<div class="login">
<div class="login-brand">
    <img src="<?= e(url('img/logo-unt.png')) ?>" alt="Universidad Nacional de Trujillo" width="207" height="110">
</div>
<form class="card narrow" method="post">
    <?= csrfField() ?>
    <label>Usuario
        <input type="text" name="username" value="<?= e($username, '') ?>" autocomplete="username" required autofocus>
    </label>
    <label>Contraseña
        <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button type="submit">Ingresar</button>
</form>
</div>
<p class="hint">El horario por escuela y por salón es público; el ingreso es para administradores y docentes.</p>
<?php pageFooter(); ?>
