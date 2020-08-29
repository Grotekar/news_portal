# Новостной портал (news_portal)
Здесь представен API для работы с новостным порталом, включая пример работы.

## 1. Требования
* Версия PHP не ниже 7.4;
* База данных MySQL (рекомендуемая версия 8.0 или выше).

## 2. Подготовка к использованию
Убедитесь, что в вашей версии PHP включен PDO-драйвер MySQL.

В MySQL должна быть уже создана база данных, которую вы указали в `.env`.

## 3. Установка
Клонируйте этот репозиторий на вашу локальную машину:

    git clone https://github.com/Grotekar/news_portal.git

Установить пакетную зависимость при помощи composer:

    composer install

На основе файла `.env.example` cоздать файл `.env`.

Создать базу данных и внести соответствующее нзвание в файл `.env`.

## 4. Функциональные возможности
- Пользователь может запустить скрипт [public/index.php](https://github.com/Grotekar/news_portal/blob/master/public/index.php)
с аргументом **--migrate**, либо **-m**, чтобы применить все миграции к локальной
базе данных и создать всю нужную структуру для неё.
- Пользователь может запустить скрипт [public/index.php](https://github.com/Grotekar/news_portal/blob/master/public/index.php)
с аргументом **--help**, либо **-h**, чтобы узнать список возможных аргументов.
- Руководствуясь примерами запросов в каталоге [curl_query](https://github.com/Grotekar/news_portal/blob/master/curl_query)
можно составлять запросы к таблицам базы данных. sh-скрипты покрывают всевозможные cURL-запросы. Ответ выводится в JSON-формате.

## 5. Тестирование

Папка [testDump](https://github.com/Grotekar/news_portal/blob/master/testDump)
содержит файлы-миграции для заполнения базы данных тестовыми данными.

Чтобы выполнить эти миграции необходимо запустить скрипт [testDump/makeMigrations.php](https://github.com/Grotekar/news_portal/blob/master/testDump/makeMigrations.php).

## 6. Описание файлов

### [api/users/index.php](https://github.com/Grotekar/news_portal/blob/master/api/users/index.php)
Точка входа для запросов к таблице users.

Поддерживаются запросы:
* GET: получить все строки таблицы **users**, получить строку таблицы **users** по **user_id**;
* POST: создать строку в таблицу **users**;
* PUT: обновить строку таблицы **users** по **user_id**;
* DELETE: удалить строку таблицы **users** по **user_id**.

### [api/User.php](https://github.com/Grotekar/news_portal/blob/master/api/User.php)
Класс содержит реализацию SQL-запросов к таблице **users**.

### [api/news/index.php](https://github.com/Grotekar/news_portal/blob/master/api/news/index.php)
Точка входа для запросов к таблице users.

Поддерживаются запросы:
* GET: получить все строки таблицы **news**, получить строку таблицы **news** по **user_id**;
* POST: создать строку в таблицу **news**;
* PUT: обновить строку таблицы **news** по **news_id**;
* DELETE: удалить строку таблицы **news** по **news_id**.

### [api/News.php](https://github.com/Grotekar/news_portal/blob/master/api/News.php)
Класс содержит реализацию SQL-запросов к таблице **news**.

### [api/AbstractTable.php](https://github.com/Grotekar/news_portal/blob/master/api/AbstractTable.php)
Абсрактный класс содержит реализацию основных API-запросов (GET, POST, PUT и DELETE) ко всем таблицам.

### [api/TableInterface.php](https://github.com/Grotekar/news_portal/blob/master/api/TableInterface.php)
Содержит реализуемый интерфейс основных API-запросов (GET, POST, PUT и DELETE).

### [curl_query](https://github.com/Grotekar/news_portal/blob/master/curl_query)
Папка содержит примеры cURL-запросов к API.

### [models/Database.php](https://github.com/Grotekar/news_portal/blob/master/models/Database.php)
Класс, описывающий метод подключения к базе данных.

### [models/Migration.php](https://github.com/Grotekar/news_portal/blob/master/models/Migration.php)
Класс, описывающий методы осуществляющие миграции.

### [public/index.php](https://github.com/Grotekar/news_portal/blob/master/public/index.php)
Точка входа для осуществления миграций базы данных.

### [migrations](https://github.com/Grotekar/news_portal/blob/master/migrations)
Каталог содержит миграции базы данных.

### [testDump](https://github.com/Grotekar/news_portal/blob/master/testDump)
Каталог содержит файлы-миграции для заполнения базы данных тестовыми данными.

### [utils/Logger.php](https://github.com/Grotekar/news_portal/blob/master/utils/Logger.php)
Класс, содержащий реализацию логирования.

### [.env.example](https://github.com/Grotekar/news_portal/blob/master/.env.example)
Шаблон для конфигурационного файла.
