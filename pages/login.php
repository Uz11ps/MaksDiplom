<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$username, $username]);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            redirect('index.php?page=dashboard');
        } else {
            $error = "Неверные данные для входа";
        }
    } else {
        $error = "Заполните все поля";
    }
}
?>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-home fa-3x text-primary mb-3"></i>
                        <h2>Вход в систему</h2>
                        <p class="text-muted">Риэлтерское агентство<br>"Сделай своими руками"</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= escape($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Логин или Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Пароль</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Войти
                        </button>
                    </form>
                    
                    <hr>
                    <div class="text-center text-muted">
                        <small>
                            Демо-доступ:<br>
                            Логин: <strong>admin</strong><br>
                            Пароль: <strong>admin123</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 