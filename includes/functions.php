<?php
// Вспомогательные функции

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ₽';
}

function formatDate($date) {
    if (!$date) return '';
    return date('d.m.Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '';
    return date('d.m.Y H:i', strtotime($datetime));
}

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    global $db;
    if (!isLoggedIn()) return null;
    
    return $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

function canAccess($required_role = null) {
    if (!isLoggedIn()) return false;
    if (!$required_role) return true;
    
    $user = getCurrentUser();
    $roles = ['agent' => 1, 'manager' => 2, 'admin' => 3];
    
    return $roles[$user['role']] >= $roles[$required_role];
}

function generateAlert($type, $message) {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

function uploadImage($file, $folder = 'uploads/') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return false;
    }
    
    $newname = uniqid() . '.' . $ext;
    $destination = $folder . $newname;
    
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newname;
    }
    
    return false;
}

function getPropertyTypes() {
    return [
        'apartment' => 'Квартира',
        'house' => 'Дом',
        'commercial' => 'Коммерческая',
        'land' => 'Земельный участок'
    ];
}

function getClientTypes() {
    return [
        'buyer' => 'Покупатель',
        'seller' => 'Продавец',
        'both' => 'Покупатель и продавец'
    ];
}

function getStatusTypes() {
    return [
        'available' => 'Доступен',
        'reserved' => 'Зарезервирован',
        'sold' => 'Продан'
    ];
}

function getDealStatuses() {
    return [
        'pending' => 'В процессе',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена'
    ];
}

function checkAccess($required_role = 'agent') {
    $roles_hierarchy = ['agent' => 1, 'manager' => 2, 'admin' => 3];
    $user_role_level = $roles_hierarchy[$_SESSION['user_role']] ?? 0;
    $required_level = $roles_hierarchy[$required_role] ?? 0;
    
    return $user_role_level >= $required_level;
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isManager() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'manager']);
}

function isAgent() {
    return isset($_SESSION['user_id']);
}

function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

function cleanPhone($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
} 