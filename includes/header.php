<?php $current_user = getCurrentUser(); ?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php?page=dashboard">
            <i class="fas fa-home me-2"></i>
            Риэлтерское агентство
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                        <i class="fas fa-chart-line me-1"></i>Панель управления
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'properties' ? 'active' : '' ?>" href="index.php?page=properties">
                        <i class="fas fa-building me-1"></i>Недвижимость
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'clients' ? 'active' : '' ?>" href="index.php?page=clients">
                        <i class="fas fa-users me-1"></i>Клиенты
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'deals' ? 'active' : '' ?>" href="index.php?page=deals">
                        <i class="fas fa-handshake me-1"></i>Сделки
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page === 'reports' ? 'active' : '' ?>" href="index.php?page=reports">
                        <i class="fas fa-chart-bar me-1"></i>Отчеты
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?= escape($current_user['full_name']) ?>
                        <span class="badge bg-light text-dark ms-1"><?= escape($current_user['role']) ?></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="index.php?page=profile">
                            <i class="fas fa-user me-2"></i>Профиль
                        </a></li>
                        <li><a class="dropdown-item" href="index.php?page=settings"><i class="fas fa-cog me-2"></i>Настройки</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?page=logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Выход
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav> 