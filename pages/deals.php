<?php
include 'includes/header.php';

// Обработка действий
if ($action === 'delete' && isset($_GET['id'])) {
    $db->execute("DELETE FROM deals WHERE id = ?", [$_GET['id']]);
    $_SESSION['message'] = "Сделка успешно удалена";
    redirect('index.php?page=deals');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = $_POST['property_id'] ?? '';
    $buyer_id = $_POST['buyer_id'] ?? '';
    $seller_id = $_POST['seller_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $commission_rate = $_POST['commission_rate'] ?? 3.00;
    $deal_date = $_POST['deal_date'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    
    if ($property_id && $buyer_id && $seller_id && $price) {
        $commission_amount = ($price * $commission_rate) / 100;
        
        if ($action === 'add') {
            $db->execute("INSERT INTO deals (property_id, buyer_id, seller_id, agent_id, price, commission_rate, commission_amount, status, deal_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$property_id, $buyer_id, $seller_id, $_SESSION['user_id'], $price, $commission_rate, $commission_amount, $status, $deal_date, $notes]);
            $_SESSION['message'] = "Сделка успешно создана";
            
            // Обновляем статус объекта
            if ($status === 'completed') {
                $db->execute("UPDATE properties SET status = 'sold' WHERE id = ?", [$property_id]);
            } elseif ($status === 'pending') {
                $db->execute("UPDATE properties SET status = 'reserved' WHERE id = ?", [$property_id]);
            }
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $db->execute("UPDATE deals SET property_id = ?, buyer_id = ?, seller_id = ?, price = ?, commission_rate = ?, commission_amount = ?, status = ?, deal_date = ?, notes = ? WHERE id = ?",
                [$property_id, $buyer_id, $seller_id, $price, $commission_rate, $commission_amount, $status, $deal_date, $notes, $_GET['id']]);
            $_SESSION['message'] = "Сделка успешно обновлена";
        }
        redirect('index.php?page=deals');
    } else {
        $error = "Заполните обязательные поля";
    }
}

// Получение данных для редактирования
$deal = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $deal = $db->fetchOne("SELECT * FROM deals WHERE id = ?", [$_GET['id']]);
}

// Получение списков для выбора
$properties = $db->fetchAll("SELECT id, title, address FROM properties WHERE status IN ('available', 'reserved') ORDER BY title");
$buyers = $db->fetchAll("SELECT id, full_name FROM clients WHERE type IN ('buyer', 'both') ORDER BY full_name");
$sellers = $db->fetchAll("SELECT id, full_name FROM clients WHERE type IN ('seller', 'both') ORDER BY full_name");

// Фильтры
$status_filter = $_GET['status_filter'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "d.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Получение сделок
$deals = $db->fetchAll("SELECT d.*, 
                              p.title as property_title, p.address,
                              b.full_name as buyer_name,
                              s.full_name as seller_name,
                              u.full_name as agent_name
                       FROM deals d 
                       LEFT JOIN properties p ON d.property_id = p.id
                       LEFT JOIN clients b ON d.buyer_id = b.id
                       LEFT JOIN clients s ON d.seller_id = s.id
                       LEFT JOIN users u ON d.agent_id = u.id
                       $where_clause 
                       ORDER BY d.created_at DESC", $params);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Управление сделками</h1>
                    <a href="index.php?page=deals&action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Новая сделка
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
                                <?= $action === 'add' ? 'Создать сделку' : 'Редактировать сделку' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= escape($error) ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="property_id" class="form-label">Объект недвижимости *</label>
                                        <select class="form-control" id="property_id" name="property_id" required>
                                            <option value="">Выберите объект</option>
                                            <?php foreach ($properties as $property): ?>
                                                <option value="<?= $property['id'] ?>" <?= ($deal['property_id'] ?? '') == $property['id'] ? 'selected' : '' ?>>
                                                    <?= escape($property['title']) ?> - <?= escape($property['address']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">Цена сделки (руб.) *</label>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?= escape($deal['price'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="buyer_id" class="form-label">Покупатель *</label>
                                        <select class="form-control" id="buyer_id" name="buyer_id" required>
                                            <option value="">Выберите покупателя</option>
                                            <?php foreach ($buyers as $buyer): ?>
                                                <option value="<?= $buyer['id'] ?>" <?= ($deal['buyer_id'] ?? '') == $buyer['id'] ? 'selected' : '' ?>>
                                                    <?= escape($buyer['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="seller_id" class="form-label">Продавец *</label>
                                        <select class="form-control" id="seller_id" name="seller_id" required>
                                            <option value="">Выберите продавца</option>
                                            <?php foreach ($sellers as $seller): ?>
                                                <option value="<?= $seller['id'] ?>" <?= ($deal['seller_id'] ?? '') == $seller['id'] ? 'selected' : '' ?>>
                                                    <?= escape($seller['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="commission_rate" class="form-label">Комиссия (%)</label>
                                        <input type="number" step="0.01" class="form-control" id="commission_rate" name="commission_rate" 
                                               value="<?= escape($deal['commission_rate'] ?? '3.00') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Статус</label>
                                        <select class="form-control" id="status" name="status">
                                            <?php foreach (getDealStatuses() as $key => $value): ?>
                                                <option value="<?= $key ?>" <?= ($deal['status'] ?? 'pending') === $key ? 'selected' : '' ?>>
                                                    <?= $value ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="deal_date" class="form-label">Дата сделки</label>
                                        <input type="date" class="form-control" id="deal_date" name="deal_date" 
                                               value="<?= escape($deal['deal_date'] ?? date('Y-m-d')) ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Примечания</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= escape($deal['notes'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?= $action === 'add' ? 'Создать' : 'Сохранить' ?>
                                    </button>
                                    <a href="index.php?page=deals" class="btn btn-secondary">
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
                            <input type="hidden" name="page" value="deals">
                            <div class="col-md-4">
                                <select class="form-control" name="status_filter">
                                    <option value="">Все статусы</option>
                                    <?php foreach (getDealStatuses() as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $status_filter === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Фильтр
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Список сделок -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-handshake me-2"></i>
                                Сделки (<?= count($deals) ?>)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($deals)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-handshake fa-4x mb-3"></i>
                                    <h5>Сделки не найдены</h5>
                                    <p>Создайте новую сделку для начала работы</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Объект</th>
                                                <th>Участники</th>
                                                <th>Цена</th>
                                                <th>Комиссия</th>
                                                <th>Статус</th>
                                                <th>Дата</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($deals as $deal_item): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= escape($deal_item['property_title']) ?></strong><br>
                                                        <small class="text-muted"><?= escape($deal_item['address']) ?></small>
                                                    </td>
                                                    <td>
                                                        <div><strong>Покупатель:</strong> <?= escape($deal_item['buyer_name']) ?></div>
                                                        <div><strong>Продавец:</strong> <?= escape($deal_item['seller_name']) ?></div>
                                                        <small class="text-muted">Агент: <?= escape($deal_item['agent_name']) ?></small>
                                                    </td>
                                                    <td><strong><?= formatPrice($deal_item['price']) ?></strong></td>
                                                    <td>
                                                        <div><?= $deal_item['commission_rate'] ?>%</div>
                                                        <small class="text-success"><?= formatPrice($deal_item['commission_amount']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $status_class = [
                                                            'pending' => 'warning',
                                                            'completed' => 'success', 
                                                            'cancelled' => 'danger'
                                                        ][$deal_item['status']];
                                                        ?>
                                                        <span class="badge bg-<?= $status_class ?>">
                                                            <?= getDealStatuses()[$deal_item['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= $deal_item['deal_date'] ? formatDate($deal_item['deal_date']) : 'Не указана' ?>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="index.php?page=deals&action=edit&id=<?= $deal_item['id'] ?>" 
                                                               class="btn btn-outline-primary" title="Редактировать">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?page=deals&action=delete&id=<?= $deal_item['id'] ?>" 
                                                               class="btn btn-outline-danger" title="Удалить"
                                                               onclick="return confirm('Удалить сделку?')">
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