<?php
include 'includes/header.php';

// Получение статистики
$stats = [
    'total_properties' => $db->fetchOne("SELECT COUNT(*) as count FROM properties")['count'],
    'available_properties' => $db->fetchOne("SELECT COUNT(*) as count FROM properties WHERE status = 'available'")['count'],
    'total_clients' => $db->fetchOne("SELECT COUNT(*) as count FROM clients")['count'],
    'total_deals' => $db->fetchOne("SELECT COUNT(*) as count FROM deals WHERE status = 'completed'")['count'],
    'pending_deals' => $db->fetchOne("SELECT COUNT(*) as count FROM deals WHERE status = 'pending'")['count'],
    'monthly_revenue' => $db->fetchOne("SELECT COALESCE(SUM(commission_amount), 0) as revenue FROM deals WHERE status = 'completed' AND MONTH(deal_date) = MONTH(CURRENT_DATE()) AND YEAR(deal_date) = YEAR(CURRENT_DATE())")['revenue']
];

// Последние объекты
$recent_properties = $db->fetchAll("SELECT p.*, u.full_name as agent_name FROM properties p LEFT JOIN users u ON p.agent_id = u.id ORDER BY p.created_at DESC LIMIT 5");

// Последние клиенты
$recent_clients = $db->fetchAll("SELECT c.*, u.full_name as agent_name FROM clients c LEFT JOIN users u ON c.agent_id = u.id ORDER BY c.created_at DESC LIMIT 5");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Панель управления</h1>
                    <div class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        <?= formatDate(date('Y-m-d')) ?>
                    </div>
                </div>

                <!-- Статистика -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="stats-card">
                            <h3><?= $stats['total_properties'] ?></h3>
                            <p class="mb-0">Всего объектов</p>
                            <i class="fas fa-building fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                            <h3><?= $stats['available_properties'] ?></h3>
                            <p class="mb-0">Доступно</p>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8, #6f42c1);">
                            <h3><?= $stats['total_clients'] ?></h3>
                            <p class="mb-0">Клиентов</p>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #fd7e14, #e83e8c);">
                            <h3><?= $stats['total_deals'] ?></h3>
                            <p class="mb-0">Сделок</p>
                            <i class="fas fa-handshake fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                            <h3><?= $stats['pending_deals'] ?></h3>
                            <p class="mb-0">В процессе</p>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #17a2b8);">
                            <h3><?= formatPrice($stats['monthly_revenue']) ?></h3>
                            <p class="mb-0">Доход за месяц</p>
                            <i class="fas fa-ruble-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Быстрые действия</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php?page=properties&action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Добавить объект
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php?page=clients&action=add" class="btn btn-success w-100">
                                            <i class="fas fa-user-plus me-2"></i>Добавить клиента
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php?page=deals&action=add" class="btn btn-warning w-100">
                                            <i class="fas fa-handshake me-2"></i>Новая сделка
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <a href="index.php?page=reports" class="btn btn-info w-100">
                                            <i class="fas fa-chart-bar me-2"></i>Отчеты
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Последние объекты и клиенты -->
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Последние объекты</h5>
                                <a href="index.php?page=properties" class="btn btn-sm btn-outline-primary">Все объекты</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($recent_properties)): ?>
                                    <div class="p-4 text-center text-muted">
                                        <i class="fas fa-building fa-3x mb-3"></i>
                                        <p>Нет добавленных объектов</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <tbody>
                                                <?php foreach ($recent_properties as $property): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= escape($property['title']) ?></strong><br>
                                                            <small class="text-muted"><?= escape($property['address']) ?></small>
                                                        </td>
                                                        <td class="text-end">
                                                            <div><?= formatPrice($property['price']) ?></div>
                                                            <span class="status-badge status-<?= $property['status'] ?>">
                                                                <?= getStatusTypes()[$property['status']] ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Последние клиенты</h5>
                                <a href="index.php?page=clients" class="btn btn-sm btn-outline-primary">Все клиенты</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($recent_clients)): ?>
                                    <div class="p-4 text-center text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <p>Нет добавленных клиентов</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <tbody>
                                                <?php foreach ($recent_clients as $client): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= escape($client['full_name']) ?></strong><br>
                                                            <small class="text-muted">
                                                                <i class="fas fa-phone me-1"></i><?= escape($client['phone']) ?>
                                                            </small>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="badge bg-secondary">
                                                                <?= getClientTypes()[$client['type']] ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 