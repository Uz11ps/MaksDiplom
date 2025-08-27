<?php
include 'includes/header.php';

// Получение данных текущего пользователя
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

if (!$user) {
    $_SESSION['error'] = "Пользователь не найден";
    redirect('index.php?page=login');
}

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = "ФИО обязательно для заполнения";
        }
        
        if (empty($email)) {
            $errors[] = "Email обязателен для заполнения";
        } elseif (!isValidEmail($email)) {
            $errors[] = "Некорректный формат email";
        }
        
        // Проверка уникальности email
        if ($email !== $user['email']) {
            $existing_email = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $_SESSION['user_id']]);
            if ($existing_email) {
                $errors[] = "Пользователь с таким email уже существует";
            }
        }
        
        if ($phone && !isValidPhone($phone)) {
            $errors[] = "Некорректный формат телефона";
        }
        
        if (empty($errors)) {
            $db->execute("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?", 
                        [$full_name, $email, $phone, $_SESSION['user_id']]);
            
            $_SESSION['user_name'] = $full_name;
            $_SESSION['message'] = "Профиль успешно обновлен";
            redirect('index.php?page=profile');
        }
    }
    
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Введите текущий пароль";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Неверный текущий пароль";
        }
        
        if (empty($new_password)) {
            $errors[] = "Введите новый пароль";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Пароль должен содержать минимум 6 символов";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Пароли не совпадают";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $db->execute("UPDATE users SET password = ? WHERE id = ?", 
                        [$hashed_password, $_SESSION['user_id']]);
            
            $_SESSION['message'] = "Пароль успешно изменен";
            redirect('index.php?page=profile');
        }
    }
}

// Обновление данных пользователя после возможных изменений
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

// Статистика пользователя
$user_stats = [];
if ($user['role'] === 'agent' || $user['role'] === 'manager') {
    $user_stats = [
        'properties' => $db->fetchOne("SELECT COUNT(*) as count FROM properties WHERE agent_id = ?", [$user['id']])['count'],
        'clients' => $db->fetchOne("SELECT COUNT(*) as count FROM clients WHERE agent_id = ?", [$user['id']])['count'],
        'deals' => $db->fetchOne("SELECT COUNT(*) as count FROM deals WHERE agent_id = ? AND status = 'completed'", [$user['id']])['count'],
        'commission' => $db->fetchOne("SELECT COALESCE(SUM(commission_amount), 0) as total FROM deals WHERE agent_id = ? AND status = 'completed'", [$user['id']])['total']
    ];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Профиль пользователя</h1>
                    <span class="badge bg-primary fs-6"><?= escape($user['role']) ?></span>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= escape($_SESSION['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
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

                <div class="row">
                    <!-- Информация о пользователе -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>Информация о пользователе
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="avatar-placeholder mb-3">
                                    <i class="fas fa-user-circle fa-5x text-muted"></i>
                                </div>
                                <h4><?= escape($user['full_name']) ?></h4>
                                <p class="text-muted mb-2"><?= escape($user['email']) ?></p>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-user-tag me-2"></i>
                                    <?php
                                    $roles = ['admin' => 'Администратор', 'manager' => 'Менеджер', 'agent' => 'Агент'];
                                    echo $roles[$user['role']];
                                    ?>
                                </p>
                                
                                <?php if ($user['phone']): ?>
                                    <p class="text-muted">
                                        <i class="fas fa-phone me-2"></i><?= escape($user['phone']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <small class="text-muted">
                                    Регистрация: <?= formatDate($user['created_at']) ?>
                                </small>
                            </div>
                        </div>

                        <!-- Статистика пользователя -->
                        <?php if (!empty($user_stats)): ?>
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Ваша статистика
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="stat-item">
                                                <h4 class="text-primary"><?= $user_stats['properties'] ?></h4>
                                                <small>Объекты</small>
                                            </div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="stat-item">
                                                <h4 class="text-info"><?= $user_stats['clients'] ?></h4>
                                                <small>Клиенты</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item">
                                                <h4 class="text-success"><?= $user_stats['deals'] ?></h4>
                                                <small>Сделки</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-item">
                                                <h4 class="text-warning"><?= formatPrice($user_stats['commission']) ?></h4>
                                                <small>Комиссия</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-8">
                        <!-- Редактирование профиля -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>Редактировать профиль
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">ФИО *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?= escape($user['full_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">Логин</label>
                                            <input type="text" class="form-control" value="<?= escape($user['username']) ?>" disabled>
                                            <small class="text-muted">Логин нельзя изменить</small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= escape($user['email']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Телефон</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?= escape($user['phone'] ?? '') ?>" 
                                                   placeholder="+7(999)123-45-67">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="role" class="form-label">Роль</label>
                                            <input type="text" class="form-control" value="<?= $roles[$user['role']] ?>" disabled>
                                            <small class="text-muted">Роль может изменить только администратор</small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Сохранить изменения
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Смена пароля -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>Изменить пароль
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Текущий пароль *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">Новый пароль *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" 
                                                       name="new_password" required minlength="6">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="togglePassword('new_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Минимум 6 символов</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Подтвердите пароль *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" 
                                                       name="confirm_password" required minlength="6">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="togglePassword('confirm_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key me-2"></i>Изменить пароль
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Проверка совпадения паролей
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Пароли не совпадают');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<style>
.avatar-placeholder {
    display: flex;
    justify-content: center;
    align-items: center;
}

.stat-item {
    padding: 10px;
    border-radius: 8px;
    background: rgba(0,0,0,0.05);
}

.stat-item h4 {
    margin: 0;
    font-weight: bold;
}

.stat-item small {
    color: #6c757d;
    font-weight: 500;
}
</style> 