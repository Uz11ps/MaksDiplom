<?php
include 'includes/header.php';

// Обработка действий
if ($action === 'delete' && isset($_GET['id'])) {
    $db->execute("DELETE FROM clients WHERE id = ?", [$_GET['id']]);
    $_SESSION['message'] = "Клиент успешно удален";
    redirect('index.php?page=clients');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $type = $_POST['type'] ?? '';
    $budget_min = $_POST['budget_min'] ?? null;
    $budget_max = $_POST['budget_max'] ?? null;
    $requirements = $_POST['requirements'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if ($full_name && $phone && $type) {
        if ($action === 'add') {
            $db->execute("INSERT INTO clients (full_name, phone, email, type, budget_min, budget_max, requirements, notes, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$full_name, $phone, $email, $type, $budget_min, $budget_max, $requirements, $notes, $_SESSION['user_id']]);
            $_SESSION['message'] = "Клиент успешно добавлен";
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $db->execute("UPDATE clients SET full_name = ?, phone = ?, email = ?, type = ?, budget_min = ?, budget_max = ?, requirements = ?, notes = ? WHERE id = ?",
                [$full_name, $phone, $email, $type, $budget_min, $budget_max, $requirements, $notes, $_GET['id']]);
            $_SESSION['message'] = "Клиент успешно обновлен";
        }
        redirect('index.php?page=clients');
    } else {
        $error = "Заполните обязательные поля";
    }
}

// Получение данных для редактирования
$client = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $client = $db->fetchOne("SELECT * FROM clients WHERE id = ?", [$_GET['id']]);
}

// Фильтры
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Получение клиентов
$clients = $db->fetchAll("SELECT c.*, u.full_name as agent_name 
                         FROM clients c 
                         LEFT JOIN users u ON c.agent_id = u.id 
                         $where_clause 
                         ORDER BY c.created_at DESC", $params);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Управление клиентами</h1>
                    <a href="index.php?page=clients&action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить клиента
                    </a>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= escape($_SESSION['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if ($action === 'add' || $action === 'edit'): ?>
                    <!-- Форма добавления/редактирования -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-<?= $action === 'add' ? 'plus' : 'edit' ?> me-2"></i>
                                <?= $action === 'add' ? 'Добавить клиента' : 'Редактировать клиента' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= escape($error) ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name" class="form-label">ФИО *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?= escape($client['full_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Телефон *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?= escape($client['phone'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= escape($client['email'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="type" class="form-label">Тип клиента *</label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="">Выберите тип</option>
                                            <?php foreach (getClientTypes() as $key => $value): ?>
                                                <option value="<?= $key ?>" <?= ($client['type'] ?? '') === $key ? 'selected' : '' ?>>
                                                    <?= $value ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="budget_min" class="form-label">Бюджет от (руб.)</label>
                                        <input type="number" class="form-control" id="budget_min" name="budget_min" 
                                               value="<?= escape($client['budget_min'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="budget_max" class="form-label">Бюджет до (руб.)</label>
                                        <input type="number" class="form-control" id="budget_max" name="budget_max" 
                                               value="<?= escape($client['budget_max'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="requirements" class="form-label">Требования</label>
                                    <textarea class="form-control" id="requirements" name="requirements" rows="3"><?= escape($client['requirements'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Заметки</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= escape($client['notes'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?= $action === 'add' ? 'Добавить' : 'Сохранить' ?>
                                    </button>
                                    <a href="index.php?page=clients" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Отмена
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Фильтры -->
                    <div class="search-box">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="page" value="clients">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" placeholder="Поиск по имени, телефону, email..." 
                                       value="<?= escape($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" name="type_filter">
                                    <option value="">Все типы</option>
                                    <?php foreach (getClientTypes() as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $type_filter === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Поиск
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Список клиентов -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Клиенты (<?= count($clients) ?>)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($clients)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-users fa-4x mb-3"></i>
                                    <h5>Клиенты не найдены</h5>
                                    <p>Попробуйте изменить параметры поиска или добавьте нового клиента</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Клиент</th>
                                                <th>Контакты</th>
                                                <th>Тип</th>
                                                <th>Бюджет</th>
                                                <th>Агент</th>
                                                <th>Дата</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clients as $cl): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= escape($cl['full_name']) ?></strong>
                                                        <?php if ($cl['requirements']): ?>
                                                            <br><small class="text-muted"><?= escape(substr($cl['requirements'], 0, 50)) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div><i class="fas fa-phone me-1"></i><?= escape($cl['phone']) ?></div>
                                                        <?php if ($cl['email']): ?>
                                                            <div><i class="fas fa-envelope me-1"></i><?= escape($cl['email']) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= getClientTypes()[$cl['type']] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($cl['budget_min'] || $cl['budget_max']): ?>
                                                            <?= $cl['budget_min'] ? formatPrice($cl['budget_min']) : '0' ?> - 
                                                            <?= $cl['budget_max'] ? formatPrice($cl['budget_max']) : '∞' ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Не указан</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= escape($cl['agent_name'] ?? 'Не назначен') ?></td>
                                                    <td><?= formatDate($cl['created_at']) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="index.php?page=clients&action=edit&id=<?= $cl['id'] ?>" 
                                                               class="btn btn-outline-primary" title="Редактировать">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?page=clients&action=delete&id=<?= $cl['id'] ?>" 
                                                               class="btn btn-outline-danger" title="Удалить"
                                                               onclick="return confirm('Удалить клиента?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 