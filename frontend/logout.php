<?php
require_once __DIR__ . '/layout.php';

// POST only, so a link or image elsewhere can't log the user out.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    logout();
}
redirect('index.php');
