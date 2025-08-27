<?php
include 'includes/header.php';

// Обработка действий
if ($action === 'delete' && isset($_GET['id'])) {
    $db->execute("DELETE FROM properties WHERE id = ?", [$_GET['id']]);
    $_SESSION['message'] = "Объект успешно удален";
    redirect('index.php?page=properties');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $type = $_POST['type'] ?? '';
    $address = $_POST['address'] ?? '';
    $district = $_POST['district'] ?? '';
    $area = $_POST['area'] ?? '';
    $rooms = $_POST['rooms'] ?? null;
    $floor = $_POST['floor'] ?? null;
    $total_floors = $_POST['total_floors'] ?? null;
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';
    $features = $_POST['features'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $owner_id = $_POST['owner_id'] ?? null;
    
    if ($title && $type && $address && $area && $price) {
        if ($action === 'add') {
            $db->execute("INSERT INTO properties (title, type, address, district, area, rooms, floor, total_floors, price, description, features, status, owner_id, agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$title, $type, $address, $district, $area, $rooms, $floor, $total_floors, $price, $description, $features, $status, $owner_id, $_SESSION['user_id']]);
            $_SESSION['message'] = "Объект успешно добавлен";
        } elseif ($action === 'edit' && isset($_GET['id'])) {
            $db->execute("UPDATE properties SET title = ?, type = ?, address = ?, district = ?, area = ?, rooms = ?, floor = ?, total_floors = ?, price = ?, description = ?, features = ?, status = ?, owner_id = ? WHERE id = ?",
                [$title, $type, $address, $district, $area, $rooms, $floor, $total_floors, $price, $description, $features, $status, $owner_id, $_GET['id']]);
            $_SESSION['message'] = "Объект успешно обновлен";
        }
        redirect('index.php?page=properties');
    } else {
        $error = "Заполните обязательные поля";
    }
}

// Получение данных для редактирования
$property = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $property = $db->fetchOne("SELECT * FROM properties WHERE id = ?", [$_GET['id']]);
}

// Фильтры
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title LIKE ? OR address LIKE ? OR district LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($price_min) {
    $where_conditions[] = "price >= ?";
    $params[] = $price_min;
}

if ($price_max) {
    $where_conditions[] = "price <= ?";
    $params[] = $price_max;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Получение объектов
$properties = $db->fetchAll("SELECT p.*, u.full_name as agent_name, c.full_name as owner_name 
                            FROM properties p 
                            LEFT JOIN users u ON p.agent_id = u.id 
                            LEFT JOIN clients c ON p.owner_id = c.id 
                            $where_clause 
                            ORDER BY p.created_at DESC", $params);

// Получение клиентов для выбора владельца
$clients = $db->fetchAll("SELECT id, full_name FROM clients WHERE type IN ('seller', 'both') ORDER BY full_name");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Управление недвижимостью</h1>
                    <a href="index.php?page=properties&action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить объект
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
                                <?= $action === 'add' ? 'Добавить объект' : 'Редактировать объект' ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= escape($error) ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="title" class="form-label">Название объекта *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?= escape($property['title'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="type" class="form-label">Тип недвижимости *</label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="">Выберите тип</option>
                                            <?php foreach (getPropertyTypes() as $key => $value): ?>
                                                <option value="<?= $key ?>" <?= ($property['type'] ?? '') === $key ? 'selected' : '' ?>>
                                                    <?= $value ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="address" class="form-label">Адрес *</label>
                                        <input type="text" class="form-control" id="address" name="address" 
                                               value="<?= escape($property['address'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="district" class="form-label">Район</label>
                                        <input type="text" class="form-control" id="district" name="district" 
                                               value="<?= escape($property['district'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="area" class="form-label">Площадь (м²) *</label>
                                        <input type="number" step="0.1" class="form-control" id="area" name="area" 
                                               value="<?= escape($property['area'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="rooms" class="form-label">Количество комнат</label>
                                        <input type="number" class="form-control" id="rooms" name="rooms" 
                                               value="<?= escape($property['rooms'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="floor" class="form-label">Этаж</label>
                                        <input type="number" class="form-control" id="floor" name="floor" 
                                               value="<?= escape($property['floor'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="total_floors" class="form-label">Всего этажей</label>
                                        <input type="number" class="form-control" id="total_floors" name="total_floors" 
                                               value="<?= escape($property['total_floors'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="price" class="form-label">Цена (руб.) *</label>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?= escape($property['price'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">Статус</label>
                                        <select class="form-control" id="status" name="status">
                                            <?php foreach (getStatusTypes() as $key => $value): ?>
                                                <option value="<?= $key ?>" <?= ($property['status'] ?? 'available') === $key ? 'selected' : '' ?>>
                                                    <?= $value ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="owner_id" class="form-label">Владелец</label>
                                        <select class="form-control" id="owner_id" name="owner_id">
                                            <option value="">Выберите владельца</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?= $client['id'] ?>" <?= ($property['owner_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                                    <?= escape($client['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?= escape($property['description'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="features" class="form-label">Особенности</label>
                                    <textarea class="form-control" id="features" name="features" rows="2"><?= escape($property['features'] ?? '') ?></textarea>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i><?= $action === 'add' ? 'Добавить' : 'Сохранить' ?>
                                    </button>
                                    <a href="index.php?page=properties" class="btn btn-secondary">
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
                            <input type="hidden" name="page" value="properties">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" placeholder="Поиск по названию, адресу..." 
                                       value="<?= escape($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="type_filter">
                                    <option value="">Все типы</option>
                                    <?php foreach (getPropertyTypes() as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $type_filter === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" name="status_filter">
                                    <option value="">Все статусы</option>
                                    <?php foreach (getStatusTypes() as $key => $value): ?>
                                        <option value="<?= $key ?>" <?= $status_filter === $key ? 'selected' : '' ?>>
                                            <?= $value ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="price_min" placeholder="Цена от" 
                                       value="<?= escape($price_min) ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="number" class="form-control" name="price_max" placeholder="Цена до" 
                                       value="<?= escape($price_max) ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Список объектов -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-building me-2"></i>
                                Объекты недвижимости (<?= count($properties) ?>)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($properties)): ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fas fa-building fa-4x mb-3"></i>
                                    <h5>Объекты не найдены</h5>
                                    <p>Попробуйте изменить параметры поиска или добавьте новый объект</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Объект</th>
                                                <th>Тип</th>
                                                <th>Площадь</th>
                                                <th>Цена</th>
                                                <th>Статус</th>
                                                <th>Агент</th>
                                                <th>Дата</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($properties as $prop): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= escape($prop['title']) ?></strong><br>
                                                        <small class="text-muted"><?= escape($prop['address']) ?></small>
                                                    </td>
                                                    <td><?= getPropertyTypes()[$prop['type']] ?></td>
                                                    <td><?= $prop['area'] ?> м²</td>
                                                    <td><strong><?= formatPrice($prop['price']) ?></strong></td>
                                                    <td>
                                                        <span class="status-badge status-<?= $prop['status'] ?>">
                                                            <?= getStatusTypes()[$prop['status']] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= escape($prop['agent_name'] ?? 'Не назначен') ?></td>
                                                    <td><?= formatDate($prop['created_at']) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="index.php?page=properties&action=edit&id=<?= $prop['id'] ?>" 
                                                               class="btn btn-outline-primary" title="Редактировать">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="index.php?page=properties&action=delete&id=<?= $prop['id'] ?>" 
                                                               class="btn btn-outline-danger" title="Удалить"
                                                               onclick="return confirm('Удалить объект?')">
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