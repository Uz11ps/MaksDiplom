<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Простой роутер
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? '';

// Проверка авторизации для защищенных страниц
$protected_pages = ['dashboard', 'properties', 'clients', 'deals', 'reports', 'profile', 'settings'];
if (in_array($page, $protected_pages) && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Обработка логаута
if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Буферизация содержимого страницы
ob_start();

switch ($page) {
    case 'login':
        include 'pages/login.php';
        break;
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    case 'properties':
        include 'pages/properties.php';
        break;
    case 'clients':
        include 'pages/clients.php';
        break;
    case 'deals':
        include 'pages/deals.php';
        break;
    case 'reports':
        include 'pages/reports.php';
        break;
    case 'profile':
        include 'pages/profile.php';
        break;
    case 'settings':
        include 'pages/settings.php';
        break;
    default:
        include 'pages/404.php';
}

$content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Риэлтерское агентство "Сделай своими руками"</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?= $content ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html> 