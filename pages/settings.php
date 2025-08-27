<?php
include 'includes/header.php';

// Проверка прав доступа
$current_user = getCurrentUser();
$is_admin = $current_user['role'] === 'admin';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_user' && $is_admin) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'agent';
        $phone = trim($_POST['phone'] ?? '');
        
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Логин обязателен";
        } elseif (strlen($username) < 3) {
            $errors[] = "Логин должен содержать минимум 3 символа";
        }
        
        if (empty($email)) {
            $errors[] = "Email обязателен";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Некорректный формат email";
        }
        
        if (empty($full_name)) {
            $errors[] = "ФИО обязательно";
        }
        
        if (empty($password)) {
            $errors[] = "Пароль обязателен";
        } elseif (strlen($password) < 6) {
            $errors[] = "Пароль должен содержать минимум 6 символов";
        }
        
        // Проверка уникальности
        if ($username) {
            $existing_user = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existing_user) {
                $errors[] = "Пользователь с таким логином уже существует";
            }
        }
        
        if ($email) {
            $existing_email = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing_email) {
                $errors[] = "Пользователь с таким email уже существует";
            }
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $db->execute("INSERT INTO users (username, email, password, full_name, role, phone) VALUES (?, ?, ?, ?, ?, ?)",
                        [$username, $email, $hashed_password, $full_name, $role, $phone]);
            
            $_SESSION['message'] = "Пользователь успешно создан";
            redirect('index.php?page=settings');
        }
    }
    
    elseif ($action === 'update_user' && $is_admin && isset($_GET['id'])) {
        $user_id = $_GET['id'];
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'agent';
        $phone = trim($_POST['phone'] ?? '');
        
        $errors = [];
        
        if (empty($email)) {
            $errors[] = "Email обязателен";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Некорректный формат email";
        }
        
        if (empty($full_name)) {
            $errors[] = "ФИО обязательно";
        }
        
        // Проверка уникальности email
        $existing_email = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
        if ($existing_email) {
            $errors[] = "Пользователь с таким email уже существует";
        }
        
        if (empty($errors)) {
            $db->execute("UPDATE users SET email = ?, full_name = ?, role = ?, phone = ? WHERE id = ?",
                        [$email, $full_name, $role, $phone, $user_id]);
            
            $_SESSION['message'] = "Пользователь успешно обновлен";
            redirect('index.php?page=settings');
        }
    }
    
    elseif ($action === 'reset_password' && $is_admin && isset($_GET['id'])) {
        $user_id = $_GET['id'];
        $new_password = $_POST['new_password'] ?? '';
        
        if (strlen($new_password) < 6) {
            $errors[] = "Пароль должен содержать минимум 6 символов";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $user_id]);
            
            $_SESSION['message'] = "Пароль пользователя успешно сброшен";
            redirect('index.php?page=settings');
        }
    }
}

// Удаление пользователя
if ($action === 'delete_user' && $is_admin && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Нельзя удалить самого себя
    if ($user_id != $_SESSION['user_id']) {
        $db->execute("DELETE FROM users WHERE id = ?", [$user_id]);
        $_SESSION['message'] = "Пользователь успешно удален";
    } else {
        $_SESSION['error'] = "Нельзя удалить свой собственный аккаунт";
    }
    redirect('index.php?page=settings');
}

// Получение пользователя для редактирования
$edit_user = null;
if ($action === 'edit_user' && $is_admin && isset($_GET['id'])) {
    $edit_user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_GET['id']]);
}

// Получение всех пользователей (только для админа)
$users = [];
if ($is_admin) {
    $users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
}

// Системная статистика
$system_stats = [
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_properties' => $db->fetchOne("SELECT COUNT(*) as count FROM properties")['count'],
    'total_clients' => $db->fetchOne("SELECT COUNT(*) as count FROM clients")['count'],
    'total_deals' => $db->fetchOne("SELECT COUNT(*) as count FROM deals")['count'],
    'completed_deals' => $db->fetchOne("SELECT COUNT(*) as count FROM deals WHERE status = 'completed'")['count'],
    'total_revenue' => $db->fetchOne("SELECT COALESCE(SUM(commission_amount), 0) as revenue FROM deals WHERE status = 'completed'")['revenue']
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Настройки системы</h1>
                    <?php if ($is_admin): ?>
                        <a href="index.php?page=settings&action=create_user" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Добавить пользователя
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= escape($_SESSION['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= escape($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Системная статистика -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Системная статистика
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <div class="stat-card">
                                            <h4 class="text-primary"><?= $system_stats['total_users'] ?></h4>
                                            <small>Пользователи</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <div class="stat-card">
                                            <h4 class="text-info"><?= $system_stats['total_properties'] ?></h4>
                                            <small>Объекты</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <div class="stat-card">
                                            <h4 class="text-warning"><?= $system_stats['total_clients'] ?></h4>
                                            <small>Клиенты</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <div class="stat-card">
                                            <h4 class="text-secondary"><?= $system_stats['total_deals'] ?></h4>
                                            <small>Всего сделок</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <div class="stat-card">
                                            <h4 class="text-success"><?= $system_stats['completed_deals'] ?></h4>
                                            <small>Завершено</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                                        <div class="stat-card">
                                            <h4 class="text-danger"><?= formatPrice($system_stats['total_revenue']) ?></h4>
                                            <small>Доход</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$is_admin): ?>
                    <!-- Для обычных пользователей -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Информация
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Доступ к расширенным настройкам имеет только администратор системы.
                                Для изменения своих данных перейдите в <a href="index.php?page=profile">профиль</a>.
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    
                    <?php if ($action === 'create_user' || $action === 'edit_user'): ?>
                        <!-- Форма создания/редактирования пользователя -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-<?= $action === 'create_user' ? 'user-plus' : 'user-edit' ?> me-2"></i>
                                    <?= $action === 'create_user' ? 'Создать пользователя' : 'Редактировать пользователя' ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <?php if ($action === 'create_user'): ?>
                                            <div class="col-md-6 mb-3">
                                                <label for="username" class="form-label">Логин *</label>
                                                <input type="text" class="form-control" id="username" name="username" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="password" class="form-label">Пароль *</label>
                                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">ФИО *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?= escape($edit_user['full_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= escape($edit_user['email'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Телефон</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?= escape($edit_user['phone'] ?? '') ?>" placeholder="+7(999)123-45-67">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="role" class="form-label">Роль *</label>
                                            <select class="form-control" id="role" name="role" required>
                                                <option value="agent" <?= ($edit_user['role'] ?? 'agent') === 'agent' ? 'selected' : '' ?>>Агент</option>
                                                <option value="manager" <?= ($edit_user['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                                <option value="admin" <?= ($edit_user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i><?= $action === 'create_user' ? 'Создать' : 'Сохранить' ?>
                                        </button>
                                        <a href="index.php?page=settings" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Отмена
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if ($action === 'edit_user' && $edit_user): ?>
                            <!-- Сброс пароля -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-key me-2"></i>Сброс пароля
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="index.php?page=settings&action=reset_password&id=<?= $edit_user['id'] ?>">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="new_password" class="form-label">Новый пароль *</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                                <small class="text-muted">Минимум 6 символов</small>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-key me-2"></i>Сбросить пароль
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Список пользователей -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>Управление пользователями
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Пользователь</th>
                                                <th>Логин</th>
                                                <th>Email</th>
                                                <th>Роль</th>
                                                <th>Дата создания</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr <?= $user['id'] == $_SESSION['user_id'] ? 'class="table-primary"' : '' ?>>
                                                    <td>
                                                        <strong><?= escape($user['full_name']) ?></strong>
                                                        <?php if ($user['phone']): ?>
                                                            <br><small class="text-muted"><?= escape($user['phone']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                            <span class="badge bg-primary ms-2">Это вы</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= escape($user['username']) ?></td>
                                                    <td><?= escape($user['email']) ?></td>
                                                    <td>
                                                        <?php 
                                                        $role_classes = ['admin' => 'danger', 'manager' => 'warning', 'agent' => 'secondary'];
                                                        $role_names = ['admin' => 'Администратор', 'manager' => 'Менеджер', 'agent' => 'Агент'];
                                                        ?>
                                                        <span class="badge bg-<?= $role_classes[$user['role']] ?>">
                                                            <?= $role_names[$user['role']] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= formatDate($user['created_at']) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="index.php?page=settings&action=edit_user&id=<?= $user['id'] ?>" 
                                                               class="btn btn-outline-primary" title="Редактировать">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <a href="index.php?page=settings&action=delete_user&id=<?= $user['id'] ?>" 
                                                                   class="btn btn-outline-danger" title="Удалить"
                                                                   onclick="return confirm('Удалить пользователя <?= escape($user['full_name']) ?>?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 15px;
    border-radius: 8px;
    background: rgba(0,0,0,0.05);
    border-left: 4px solid var(--bs-primary);
}

.stat-card h4 {
    margin: 0;
    font-weight: bold;
}

.stat-card small {
    color: #6c757d;
    font-weight: 500;
}
</style> 