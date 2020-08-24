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

## 5. Тестирование

## 6. Описание файлов

### [models/Database.php](https://github.com/Grotekar/news_portal/blob/master/models/Database.php)
Класс, описывающий метод подключения к базе данных.

### [models/Migration.php](https://github.com/Grotekar/news_portal/blob/master/models/Migration.php)
Класс, описывающий методы осуществляющие миграции.

### [public/index.php](https://github.com/Grotekar/news_portal/blob/master/public/index.php)
Точка входа в управление порталом.

### [migrations](https://github.com/Grotekar/news_portal/blob/master/migrations)
Каталог, содержащий миграции базы данных.

### [utils/Logger.php](https://github.com/Grotekar/news_portal/blob/master/utils/Logger.php)
Класс, содержащий реализацию логирования.

### [.env.example](https://github.com/Grotekar/news_portal/blob/master/.env.example)
Шаблон для конфигурационного файла.
