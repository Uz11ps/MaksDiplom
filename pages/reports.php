<?php
include 'includes/header.php';

// Получение статистики
$current_month = date('Y-m');
$current_year = date('Y');

// Общая статистика
$total_properties = $db->fetchOne("SELECT COUNT(*) as count FROM properties")['count'];
$total_clients = $db->fetchOne("SELECT COUNT(*) as count FROM clients")['count'];
$total_deals = $db->fetchOne("SELECT COUNT(*) as count FROM deals WHERE status = 'completed'")['count'];
$total_revenue = $db->fetchOne("SELECT COALESCE(SUM(commission_amount), 0) as revenue FROM deals WHERE status = 'completed'")['revenue'];

// Статистика по месяцам
$monthly_stats = $db->fetchAll("
    SELECT 
        DATE_FORMAT(deal_date, '%Y-%m') as month,
        COUNT(*) as deals_count,
        SUM(price) as total_sales,
        SUM(commission_amount) as total_commission
    FROM deals 
    WHERE status = 'completed' AND deal_date IS NOT NULL
    GROUP BY DATE_FORMAT(deal_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

// Статистика по агентам
$agent_stats = $db->fetchAll("
    SELECT 
        u.full_name,
        COUNT(d.id) as deals_count,
        COALESCE(SUM(d.commission_amount), 0) as total_commission,
        COUNT(p.id) as properties_count,
        COUNT(c.id) as clients_count
    FROM users u
    LEFT JOIN deals d ON u.id = d.agent_id AND d.status = 'completed'
    LEFT JOIN properties p ON u.id = p.agent_id
    LEFT JOIN clients c ON u.id = c.agent_id
    WHERE u.role IN ('agent', 'manager')
    GROUP BY u.id, u.full_name
    ORDER BY deals_count DESC
");

// Статистика по типам недвижимости
$property_type_stats = $db->fetchAll("
    SELECT 
        type,
        COUNT(*) as count,
        AVG(price) as avg_price,
        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_count
    FROM properties
    GROUP BY type
    ORDER BY count DESC
");

// Последние сделки
$recent_deals = $db->fetchAll("
    SELECT 
        d.*,
        p.title as property_title,
        b.full_name as buyer_name,
        s.full_name as seller_name,
        u.full_name as agent_name
    FROM deals d
    JOIN properties p ON d.property_id = p.id
    JOIN clients b ON d.buyer_id = b.id
    JOIN clients s ON d.seller_id = s.id
    JOIN users u ON d.agent_id = u.id
    WHERE d.status = 'completed'
    ORDER BY d.deal_date DESC
    LIMIT 10
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Отчеты и аналитика</h1>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-2"></i>Печать
                    </button>
                </div>

                <!-- Общая статистика -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card">
                            <h3><?= $total_properties ?></h3>
                            <p class="mb-0">Всего объектов</p>
                            <i class="fas fa-building fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8, #6f42c1);">
                            <h3><?= $total_clients ?></h3>
                            <p class="mb-0">Всего клиентов</p>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #fd7e14, #e83e8c);">
                            <h3><?= $total_deals ?></h3>
                            <p class="mb-0">Завершенных сделок</p>
                            <i class="fas fa-handshake fa-2x opacity-75"></i>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #28a745, #17a2b8);">
                            <h3><?= formatPrice($total_revenue) ?></h3>
                            <p class="mb-0">Общий доход</p>
                            <i class="fas fa-ruble-sign fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>

                <!-- Статистика по месяцам -->
                <div class="row mb-4">
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Динамика продаж по месяцам</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($monthly_stats)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                                        <p>Нет данных для отображения</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Месяц</th>
                                                    <th>Сделки</th>
                                                    <th>Общие продажи</th>
                                                    <th>Комиссия</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($monthly_stats as $stat): ?>
                                                    <tr>
                                                        <td><?= date('m.Y', strtotime($stat['month'] . '-01')) ?></td>
                                                        <td><?= $stat['deals_count'] ?></td>
                                                        <td><?= formatPrice($stat['total_sales']) ?></td>
                                                        <td class="text-success"><?= formatPrice($stat['total_commission']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>По типам недвижимости</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($property_type_stats)): ?>
                                    <div class="text-center text-muted py-4">
                                        <p>Нет данных</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Тип</th>
                                                    <th>Всего</th>
                                                    <th>Продано</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($property_type_stats as $stat): ?>
                                                    <tr>
                                                        <td><?= getPropertyTypes()[$stat['type']] ?></td>
                                                        <td><?= $stat['count'] ?></td>
                                                        <td>
                                                            <span class="badge bg-success"><?= $stat['sold_count'] ?></span>
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

                <!-- Статистика по агентам -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Производительность агентов</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($agent_stats)): ?>
                                    <div class="text-center text-muted py-4">
                                        <p>Нет данных по агентам</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Агент</th>
                                                    <th>Завершенные сделки</th>
                                                    <th>Комиссия</th>
                                                    <th>Объекты</th>
                                                    <th>Клиенты</th>
                                                    <th>Эффективность</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($agent_stats as $agent): ?>
                                                    <?php 
                                                    $efficiency = $agent['properties_count'] > 0 ? 
                                                        round(($agent['deals_count'] / $agent['properties_count']) * 100, 1) : 0;
                                                    ?>
                                                    <tr>
                                                        <td><strong><?= escape($agent['full_name']) ?></strong></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?= $agent['deals_count'] ?></span>
                                                        </td>
                                                        <td class="text-success"><?= formatPrice($agent['total_commission']) ?></td>
                                                        <td><?= $agent['properties_count'] ?></td>
                                                        <td><?= $agent['clients_count'] ?></td>
                                                        <td>
                                                            <?php if ($efficiency >= 70): ?>
                                                                <span class="badge bg-success"><?= $efficiency ?>%</span>
                                                            <?php elseif ($efficiency >= 40): ?>
                                                                <span class="badge bg-warning"><?= $efficiency ?>%</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger"><?= $efficiency ?>%</span>
                                                            <?php endif; ?>
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

                <!-- Последние сделки -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Последние завершенные сделки</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($recent_deals)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-handshake fa-3x mb-3"></i>
                                        <p>Нет завершенных сделок</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Дата</th>
                                                    <th>Объект</th>
                                                    <th>Покупатель</th>
                                                    <th>Продавец</th>
                                                    <th>Цена</th>
                                                    <th>Комиссия</th>
                                                    <th>Агент</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_deals as $deal): ?>
                                                    <tr>
                                                        <td><?= formatDate($deal['deal_date']) ?></td>
                                                        <td>
                                                            <strong><?= escape($deal['property_title']) ?></strong>
                                                        </td>
                                                        <td><?= escape($deal['buyer_name']) ?></td>
                                                        <td><?= escape($deal['seller_name']) ?></td>
                                                        <td><strong><?= formatPrice($deal['price']) ?></strong></td>
                                                        <td class="text-success">
                                                            <?= formatPrice($deal['commission_amount']) ?>
                                                            <small class="text-muted">(<?= $deal['commission_rate'] ?>%)</small>
                                                        </td>
                                                        <td><?= escape($deal['agent_name']) ?></td>
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

<style>
@media print {
    .btn, .navbar, .sidebar { display: none !important; }
    .main-content { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style> 