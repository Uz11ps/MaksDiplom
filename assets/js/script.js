// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическое скрытие alert сообщений
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        });
    }, 5000);

    // Форматирование полей цены
    const priceInputs = document.querySelectorAll('input[name="price"], input[name="price_min"], input[name="price_max"]');
    priceInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            // Убираем все не-цифры
            this.value = this.value.replace(/[^\d]/g, '');
        });
    });

    // Валидация форм
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Подтверждение удаления
    const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить этот элемент?')) {
                e.preventDefault();
            }
        });
    });

    // Поиск в реальном времени (с задержкой)
    const searchInputs = document.querySelectorAll('input[name="search"]');
    searchInputs.forEach(function(input) {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                // Автоматическая отправка формы поиска через 1 секунду после ввода
                const form = input.closest('form');
                if (form && input.value.length > 2) {
                    // form.submit(); // Раскомментировать для автоматического поиска
                }
            }, 1000);
        });
    });

    // Анимация счетчиков на панели управления
    animateCounters();

    // Инициализация tooltips
    initTooltips();
});

// Анимация счетчиков
function animateCounters() {
    const counters = document.querySelectorAll('.stats-card h3');
    counters.forEach(function(counter) {
        const target = parseInt(counter.textContent.replace(/[^\d]/g, ''));
        if (target && target > 0) {
            let count = 0;
            const increment = target / 50;
            const timer = setInterval(function() {
                count += increment;
                if (count >= target) {
                    counter.textContent = formatNumber(target);
                    clearInterval(timer);
                } else {
                    counter.textContent = formatNumber(Math.floor(count));
                }
            }, 20);
        }
    });
}

// Форматирование чисел
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

// Инициализация tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Функции для работы с модальными окнами
function showModal(id) {
    const modal = new bootstrap.Modal(document.getElementById(id));
    modal.show();
}

function hideModal(id) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(id));
    if (modal) {
        modal.hide();
    }
}

// Функция для показа loading состояния
function showLoading(element) {
    const originalText = element.innerHTML;
    element.innerHTML = '<span class="loading"></span> Загрузка...';
    element.disabled = true;
    
    return function() {
        element.innerHTML = originalText;
        element.disabled = false;
    };
}

// AJAX функции для асинхронных запросов
function ajaxRequest(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (callback) callback(data);
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showAlert('danger', 'Произошла ошибка при обработке запроса');
    });
}

// Показ уведомлений
function showAlert(type, message) {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.main-content');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHTML);
        
        // Автоматическое скрытие через 5 секунд
        setTimeout(function() {
            const alert = container.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
}

// Функции для работы с фильтрами
function clearFilters() {
    const form = document.querySelector('.search-box form');
    if (form) {
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            if (input.type === 'hidden') return;
            input.value = '';
        });
        form.submit();
    }
}

// Обработчик для мобильного меню
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Функция для экспорта данных
function exportData(format, page) {
    const url = `export.php?format=${format}&page=${page}`;
    window.open(url, '_blank');
}

// Валидация телефонных номеров
function validatePhone(phone) {
    const phoneRegex = /^[\+]?[0-9\-\(\)\s]+$/;
    return phoneRegex.test(phone);
}

// Валидация email
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Функция для копирования в буфер обмена
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('success', 'Скопировано в буфер обмена');
    }).catch(function() {
        showAlert('warning', 'Не удалось скопировать');
    });
}

// Функция для печати отчета
function printReport() {
    window.print();
}

// Обработка клавиатурных сокращений
document.addEventListener('keydown', function(e) {
    // Ctrl+S для сохранения форм
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.click();
        }
    }
    
    // Escape для закрытия модальных окон
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(function(modal) {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }
}); 