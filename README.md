# Новостной портал (news_portal)
Здесь представен REST API для работы с новостным порталом.

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
- Некоторые запросы доступны только с определенными правами, которые можно получить только по id пользователя.
- Поддерживается пагинация (например, `.../news?pagination=[0,5]`, что означает вывод с 0 элемента с шагом 5).
В скобках может быть и одна цифра, которая означает вывод первых ***n*** элементов.
## 5. Тестирование

Каталог [testDump](https://github.com/Grotekar/news_portal/blob/master/testDump)
содержит файлы-миграции для заполнения базы данных тестовыми данными.

Чтобы выполнить эти миграции необходимо запустить скрипт [testDump/makeMigrations.php](https://github.com/Grotekar/news_portal/blob/master/testDump/makeMigrations.php).

Также в каталоге [tests/PHPUnit/Framework](https://github.com/Grotekar/news_portal/blob/master/tests/PHPUnit/Framework)
хранятся тесты запросов к таблицам **users** и **news**.
## 6. Описание файлов

### [api/authors/index.php](https://github.com/Grotekar/news_portal/blob/master/api/authors/index.php)
Точка входа для запросов к таблице **authors**.

Поддерживаются запросы:
* GET: получить все строки таблицы **authors**, получить строку таблицы **authors** по **author_id** 
*(доступно только администраторам)*;
* POST: создать строку в таблицу **authors** *(доступно только администраторам)*;
* PUT: обновить строку таблицы **authors** по **author_id** *(доступно только администраторам)*;
* DELETE: удалить строку таблицы **authors** по **author_id** *(доступно только администраторам)*.

### [api/Author.php](https://github.com/Grotekar/news_portal/blob/master/api/Author.php)
Класс подготавливает SQL-запросы к таблице **authors**.

Идентификаторы в выводе заменяются на интерпретацию.

### [api/categories/index.php](https://github.com/Grotekar/news_portal/blob/master/api/categories/index.php)
Точка входа для запросов к таблице **categories**.

Поддерживаются запросы:
* GET: получить все строки таблицы **categories**, получить строку таблицы **categories** по **category_id** 
*(доступно всем пользователям)*;
* POST: создать строку в таблицу **categories** *(доступно только администраторам)*;
* PUT: обновить строку таблицы **categories** по **category_id** *(доступно только администраторам)*;
* DELETE: удалить строку таблицы **categories** по **category_id** *(доступно только администраторам)*.

### [api/Category.php](https://github.com/Grotekar/news_portal/blob/master/api/Category.php)
Класс подготавливает SQL-запросы к таблице **categories**.

### [api/Comments.php](https://github.com/Grotekar/news_portal/blob/master/api/Comments.php)
Класс подготавливает SQL-запросы к таблице **comments**.

Поддерживаются запросы:
* GET: получить все строки таблицы **comments**, получить строку таблицы **comments** по **comment_id** 
*(доступно всем пользователям)*;
* POST: создать строку в таблицу **comments** *(доступно только авторизованным пользователям)*;
* DELETE: удалить строку таблицы **comments** по **comment_id** *(доступно только владельцу комментария)*.

### [api/drafts/index.php](https://github.com/Grotekar/news_portal/blob/master/api/drafts/index.php)
Точка входа для запросов к таблице **drafts**.

Поддерживаются запросы:
* GET: получить все строки таблицы **drafts** (текущего автора), получить строку таблицы **drafts** по **draft_id** (текущего автора)
*(доступно всем авторам)*;
* POST: опубликовать новость (обновить строку в таблице **news**, статус черновикаа - *опубликован*) *(доступно только авторам)*;
* PUT: обновить строку таблицы **drafts** по **draft_id** *(доступно только авторам)*;
* DELETE: удалить строку таблицы **drafts** по **draft_id** *(доступно только авторам)*.

### [api/Drafts.php](https://github.com/Grotekar/news_portal/blob/master/api/Drafts.php)
Класс подготавливает SQL-запросы к таблице **drafts**.

### [api/news/index.php](https://github.com/Grotekar/news_portal/blob/master/api/news/index.php)
Точка входа для запросов к таблице **news**.

Поддерживаются запросы:
* GET: получить все строки таблицы **news**, получить строку таблицы **news** по **news_id** 
*(доступно всем пользователям)*;
* POST: создать строку в таблицу **drafts** на основе строки в таблице **news** *(доступно только авторам)*;
* PUT: обновить строку таблицы **news** по **news_id** *(доступно только авторам)*;
* DELETE: удалить строку таблицы **news** по **news_id** *(доступно только администраторам)*.

### [api/News.php](https://github.com/Grotekar/news_portal/blob/master/api/News.php)
Класс подготавливает SQL-запросы к таблице **news**.

Также подготавливает ответ для запроса, который получается с подстановкой идентификатора значением, например, вместо
идентификатора категории будет получено название категории с родительской категорией. 

Категория новости - последний элемент массива **categories**, потому что является дочерним.

### [api/tags/index.php](https://github.com/Grotekar/news_portal/blob/master/api/tags/index.php)
Точка входа для запросов к таблице **tags**.

Поддерживаются запросы:
* GET: получить все строки таблицы **tags**, получить строку таблицы **tags** по **tag_id** 
*(доступно всем пользователям)*;
* POST: создать строку в таблицу **tags** *(доступно только администраторам)*;
* PUT: обновить строку таблицы **tags** по **tag_id** *(доступно только администраторам)*;
* DELETE: удалить строку таблицы **tags** по **tag_id** *(доступно только администраторам)*.

### [api/Tag.php](https://github.com/Grotekar/news_portal/blob/master/api/Tag.php)
Класс подготавливает SQL-запросы к таблице **tags**.

### [api/users/index.php](https://github.com/Grotekar/news_portal/blob/master/api/users/index.php)
Точка входа для запросов к таблице **users**.

Поддерживаются запросы:
* GET: получить все строки таблицы **users**, получить строку таблицы **users** по **user_id**
*(доступно всем пользователям)*;
* POST: создать строку в таблицу **users** *(доступно всем пользователям)*;
* PUT: обновить строку таблицы **users** по **user_id** 
*(пользователь может редактировать только свою строку по id)*;
* DELETE: удалить строку таблицы **users** по **user_id** *(доступно только администраторам)*.

### [api/User.php](https://github.com/Grotekar/news_portal/blob/master/api/User.php)
Класс подготавливает SQL-запросы к таблице **users**.

### [api/AbstractTable.php](https://github.com/Grotekar/news_portal/blob/master/api/AbstractTable.php)
Абсрактный класс содержит реализацию основных API-запросов (GET, POST, PUT и DELETE) ко всем таблицам.

### [api/TableInterface.php](https://github.com/Grotekar/news_portal/blob/master/api/TableInterface.php)
Содержит реализуемый интерфейс основных API-запросов (GET, POST, PUT и DELETE).

### [curl_query](https://github.com/Grotekar/news_portal/blob/master/curl_query)
Каталог содержит примеры cURL-запросов к API.

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

### [tests/PHPUnit/Framework](https://github.com/Grotekar/news_portal/blob/master/tests/PHPUnit/Framework)
Каталог хранит тесты запросов к таблицам **users** и **news**.

### [utils/Logger.php](https://github.com/Grotekar/news_portal/blob/master/utils/Logger.php)
Класс, содержащий реализацию логирования.

### [.env.example](https://github.com/Grotekar/news_portal/blob/master/.env.example)
Шаблон для конфигурационного файла.
