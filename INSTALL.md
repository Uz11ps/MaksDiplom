# 🚀 Инструкция по установке

Подробное руководство по установке системы автоматизации учета недвижимости.

## 📋 Системные требования

### Минимальные требования
- **PHP**: 7.4 или выше
- **MySQL**: 5.7 или выше  
- **Веб-сервер**: Apache 2.4+ или Nginx 1.18+
- **Память**: 512 MB RAM
- **Диск**: 100 MB свободного места

### Рекомендуемые требования
- **PHP**: 8.0+
- **MySQL**: 8.0+
- **Память**: 1 GB RAM
- **Диск**: 500 MB свободного места

### PHP расширения
Убедитесь, что установлены следующие расширения:
- `pdo_mysql` - для работы с MySQL
- `mbstring` - для работы с UTF-8
- `json` - для работы с JSON
- `session` - для сессий
- `filter` - для валидации данных

## 🔧 Способы установки

### Вариант 1: Локальная установка

#### 1. Клонирование репозитория
```bash
git clone https://github.com/Uz11ps/MaksDiplom.git
cd MaksDiplom
```

#### 2. Настройка веб-сервера

**Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Безопасность
<Files "config/database.php">
    Order Allow,Deny
    Deny from all
</Files>
```

**Nginx**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/MaksDiplom;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Защита конфигурационных файлов
    location ~ ^/config/ {
        deny all;
    }
}
```

#### 3. Создание базы данных
```sql
CREATE DATABASE realty_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'realty_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON realty_system.* TO 'realty_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 4. Настройка подключения к БД
```bash
cp config/database.example.php config/database.php
```

Отредактируйте `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'realty_system');
define('DB_USER', 'realty_user');
define('DB_PASS', 'secure_password');
```

#### 5. Установка базы данных
Перейдите в браузере:
```
http://your-domain.com/config/install.php
```

#### 6. Безопасность
После установки удалите установщик:
```bash
rm config/install.php
rm install.php  # если существует
```

### Вариант 2: Docker установка

#### 1. Создайте docker-compose.yml
```yaml
version: '3.8'

services:
  web:
    image: php:8.0-apache
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=realty_system
      - DB_USER=root
      - DB_PASS=rootpassword

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: realty_system
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  phpmyadmin:
    image: phpmyadmin:latest
    ports:
      - "8081:80"
    environment:
      - PMA_HOST=db
      - PMA_USER=root
      - PMA_PASSWORD=rootpassword

volumes:
  mysql_data:
```

#### 2. Запуск контейнеров
```bash
docker-compose up -d
```

#### 3. Установка
Перейдите к `http://localhost:8080/config/install.php`

### Вариант 3: Shared Hosting

#### 1. Загрузка файлов
Загрузите все файлы через FTP в корневую папку сайта

#### 2. Создание БД через панель управления
- Создайте базу данных MySQL
- Создайте пользователя БД
- Запишите данные подключения

#### 3. Настройка
Отредактируйте `config/database.php` с данными хостинга

#### 4. Установка
Перейдите к `http://your-site.com/config/install.php`

## 🔐 Первый вход

После установки используйте:
- **Логин**: `admin`
- **Пароль**: `admin123`

⚠️ **Важно**: Сразу смените пароль администратора!

## ⚙️ Настройка после установки

### 1. Смена пароля администратора
1. Войдите как `admin`
2. Перейдите в "Профиль"
3. Смените пароль на безопасный

### 2. Создание пользователей
1. Перейдите в "Настройки"
2. Добавьте агентов и менеджеров
3. Назначьте соответствующие роли

### 3. Настройка системы
1. Добавьте первых клиентов
2. Внесите объекты недвижимости
3. Настройте комиссии

### 4. Безопасность
```bash
# Установите правильные права доступа
chmod 755 /path/to/project
chmod 644 /path/to/project/config/database.php
chmod 755 /path/to/project/uploads

# Создайте .htaccess для защиты
echo "deny from all" > config/.htaccess
```

## 🔧 Устранение неполадок

### Ошибка подключения к БД
```
SQLSTATE[HY000] [2002] Connection refused
```
**Решение**: Проверьте данные подключения в `config/database.php`

### Ошибка прав доступа
```
Permission denied
```
**Решение**: 
```bash
sudo chown -R www-data:www-data /path/to/project
sudo chmod -R 755 /path/to/project
```

### Белая страница
**Решение**: Включите отображение ошибок PHP:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Проблемы с сессиями
**Решение**: Проверьте настройки сессий в php.ini:
```ini
session.save_path = "/tmp"
session.gc_maxlifetime = 1440
```

## 📞 Поддержка

Если возникли проблемы:

1. **Проверьте системные требования**
2. **Изучите логи ошибок**
3. **Создайте Issue** на GitHub
4. **Опишите проблему** подробно

### Полезные команды для диагностики

```bash
# Проверка версии PHP
php -v

# Проверка расширений PHP
php -m

# Проверка подключения к MySQL
mysql -u username -p -h localhost

# Просмотр логов Apache
tail -f /var/log/apache2/error.log

# Просмотр логов Nginx
tail -f /var/log/nginx/error.log
```

## 🎯 Следующие шаги

После успешной установки:

1. 📖 Изучите [документацию](README.md)
2. 👥 Добавьте пользователей
3. 🏢 Внесите объекты недвижимости
4. 📊 Начните работу с системой

---

**Удачной работы с системой! 🚀**