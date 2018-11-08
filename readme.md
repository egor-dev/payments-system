# Требования
- PHP 7.1 и выше
- Composer
- Docker, не Toolbox! (при желании запуститься в нём)

Внимание! Инструкция проверена только на Mac OS.

# Запуск локально с базой данных SQLite

Установить зависимости
```
composer install
```

Скопировать настройки окружения
```
cp .env.local.example .env
```

Создать файл для базы данных SQLite
```
touch database/database.sqlite
```

В .env указать абсолютный путь до database.sqlite, например
```
DB_DATABASE=/absolute/path/to/database.sqlite
```

Выполнить миграции
```
php artisan migrate
```

Запустить локальный веб-сервер
```
php artisan serve --host=127.0.0.1 --port=8080
```

Чтобы наполнить проект данными для демонстрации, запустить
```
php artisan db:seed --class=DemoSeeder
```

После выполнения сидера, можно открывать страницу отчёта [http://127.0.0.1:8080](http://127.0.0.1:8080)

# Запуск в Docker

Установить зависимости
```
composer install
```

Скопировать настройки окружения
```
cp .env.docker.example .env
```

Подтянуть подмодуль с Docker
```
git submodule init && git submodule update 
```

Перейти в него
```
cd docker
```

Скопировать настройки
```
cp env-example .env 
```

Запустить
```
docker-compose up -d workspace nginx postgres 
```

Войти в контейнер
```
docker-compose exec workspace bash 
```

Выполнить миграции внутри контейнера
```
php artisan migrate
```

Запустить сидеры для вставки валют и типов транзакций
```
php artisan db:seed
```

После выполнения сидера, можно открывать страницу отчёта [http://127.0.0.1:8080](http://127.0.0.1:8080)

Если захочется поиграться с запросами на API, то вот тут есть [Postman документация по роутам](https://documenter.getpostman.com/view/3030666/RzZ9FeTo). 

# Тесты

Создать тестовую БД
```            
touch database/testing.sqlite
```

Запуск тестов
```            
vendor/bin/phpunit
```
