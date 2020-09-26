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

## 4. Запуск сервера (пример на встроенном сервере PHP)

     php -S localhost:8000 public/router.php

## 5. Функциональные возможности
- Пользователь может запустить скрипт [public/migration.php](https://github.com/Grotekar/news_portal/blob/master/public/migration.php)
с аргументом **--migrate**, либо **-m**, чтобы применить все миграции к локальной
базе данных и создать всю нужную структуру для неё.
- Пользователь может запустить скрипт [public/migration.php](https://github.com/Grotekar/news_portal/blob/master/public/migration.php)
с аргументом **--help**, либо **-h**, чтобы узнать список возможных аргументов.
- Точкой входа является файл [public/router.php](https://github.com/Grotekar/news_portal/blob/master/public/router.php)
- Руководствуясь примерами запросов в каталоге [curl_query](https://github.com/Grotekar/news_portal/blob/master/curl_query)
можно составлять запросы к таблицам базы данных. sh-скрипты покрывают всевозможные cURL-запросы. Ответ выводится в JSON-формате.
- Некоторые запросы доступны только с определенными правами, которые можно получить только по id пользователя.
- Поддерживается пагинация (например, `.../news?pagination=[0,5]`, что означает вывод с 0 элемента с шагом 5).
В скобках может быть и одна цифра, которая означает вывод первых ***n*** элементов.

## 6. Методы api

Методы аозвращают JSON-массив: 
- В случае успешного GET-запроса возвращаются записи с требуемой информацией;
- В случае успешных POST-, PUT-, DELETE-запросов возвращаются статус запроса и id затрагиваемой записи 
(за редким исключением указанном в определенном методе);
- В случае ошибки запроса возвращаются статус запроса и текст ошибки.
---
## Примеры cURL-запросов

* ### Автор

    * #### Получить список авторов
    Получить список авторов может только пользователь с правами администратора
    
    ```bash
    curl -i -X GET -u '<admin_id>:' http://localhost:8000/authors
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|

    * #### Получить автора
    Получить информацию об авторе может только пользователь с правами администратора.

    ```bash
    curl -i -X GET -u '<admin_id>:' http://localhost:8000/authors/<user_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<user_id>  | int |идентификатор пользователя|
    
    * #### Добавить автора
    Добавить автора может только пользователь с правами администратора.

    ```bash
    curl -i -X POST -u '<admin_id>:' -d "user_id=<user_id>&description=<description>" http://localhost:8000/authors
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<user_id>  | int |идентификатор пользователя|
    | \<description>| text | описание |

    * #### Редактировать автора
    Редактировать запись об авторе может только пользователь с правами администратора.

    ```bash
    curl -i -X PUT -u '<admin_id>:' -d "description=<description>" http://localhost:8000/authors/<user_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<user_id>  | int |идентификатор пользователя|
    | \<description>| text | описание |

    * #### Удалить автора
    Удалить пользователя из списка авторов может только пользователь с правами администратора.

    ```bash
    curl -i -X DELETE -u '<admin_id>:' http://localhost:8000/author/<user_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<user_id>  | int |идентификатор пользователя|
 
* ### Категория

    * #### Получить список категорий
    Получить список категорий может любой пользователь.
    
    ```bash
    curl -i -X GET http://localhost:8000/categories
    ```

    * #### Получить категорию
    Получить информацию о категории может любой пользователь.

    ```bash
    curl -i -X GET http://localhost:8000/categories/<category_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<category_id>  | int |идентификатор категории|
    
    * #### Добавить категорию
    Добавить категорию может только пользователь с правами администратора.

    ```bash
    curl -i -X POST -u '<admin_id>:' -d "name=cat" http://localhost:8000/categories
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|

    * #### Редактировать категоррию
    Редактировать запись о категории может только пользователь с правами администратора.

    ```bash
    curl -i -X PUT -u '<admin_id>:' -d "name=<name>" http://localhost:8000/categories/<category_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<category_id>  | int |идентификатор категории|
    | \<name>| text |название категории |

    * #### Удалить категорию
    Удалить категорию из списка категорий может только пользователь с правами администратора.

    ```bash
    curl -i -X DELETE -u '<admin_id>:' http://localhost:8000/categories/<category_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<category_id>  | int |идентификатор категории|

* ### Комментарий

    * #### Получить список комментариев
    Получить список комментариев новости может любой пользователь.
    
    ```bash
    curl -i -X GET http://localhost:8000/news/<news_id>/comments
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<news_id>  | int |идентификатор новости|

    * #### Получить комментарий
    Получить информацию о комментарии может любой пользователь.

    ```bash
    curl -i -X GET http://localhost:8000/news/<news_id>/comments/<comment_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<news_id>  | int |идентификатор новости|
    | \<comment_id>  | int |идентификатор комментария|
    
    * #### Добавить комментарий
    Добавить комментарий новости может только зарегистрированный пользователь.

    ```bash
    curl -i -X POST -d "text=<text>" -u '<user_id>:' http://localhost:8000/news/<news_id>/comments
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<user_id> | int |идентификатор пользователя|
    | \<news_id> | int |идентификатор новости|
    | \<text> | text |текст комментария|

    * #### Удалить комментарий
    Удалить комментарий из списка комментариев новости может только пользователь-автор комментария.

    ```bash
    curl -i -X DELETE -u '<user_id>:' http://localhost:8000/news/<news_id>/comments/<comment_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<user_id> | int |идентификатор пользователя|
    | \<news_id>  | int |идентификатор новости|
    | \<comment_id>  | int |идентификатор коомментария|

* ### Новость

    * #### Получить список новостей
    Получить список новостей может любой пользователь.
    
    ```bash
    curl -i -X GET http://localhost:8000/news
    ```

    * #### Получить новость
    Получить информацию о новости может любой пользователь.

    ```bash
    curl -i -X GET http://localhost:8000/news/<news_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<news_id>  | int |идентификатор новости|
    
    * #### Добавить новость
    Добавить новость может только один из авторов.

    ```bash
    curl -i -X POST -u '<author_id>:' -d "article=<article>&category_id=<category_id>&content=<content>&main_image=<main_image>" http://localhost:8000/news
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id> | int |идентификатор автора черновика|
    | \<article> | text |заголовок новости|
    | \<category_id>  | int |идентификатор категории|
    | \<content> | text |тело новости|
    | \<main_image>| text |главное изображение|

    * #### Удалить новость
    Удалить новость может только пользователь с правами администратора.

    ```bash
    curl -i -X DELETE -u '<user_id>:' http://localhost:8000/news/<news_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<user_id>  | int |идентификатор пользователя|
    | \<news_id> | int |идентификатор новости|

    * #### Фильтровать новости
    Использовать фильтр новостей может любой пользователь.

    ```bash
    curl -i -X GET http://localhost:8000/news?<option>=<value>
    ```

    |Параметр|Принимаемые значения|Тип|Определение|
    |------------|-|-----------|-|
    | \<option>  |article        |text|фильтрация по указанному значению <value> в заголовке новости
    |            |category_id    |int |фильтрация по указанному id категории|
    |            |content        |text|фильтрация по указанному значению <value> в теле новости|
    |            |created        |text|фильтрация по точно указанной дате в <value> формата 2222-12-30|
    |            |created__after |text|фильтрация по записям ранее указанной даты в <value> формата 2222-12-30|
    |            |created__before|text|фильтрация по записям позднее указанной даты в <value> формата 2222-12-30|
    |            |firstname      |text|фильтрация по указанному значению <value> в имени автора новости|
    |            |tag_id         |int |фильтрация по указанному id тега <value>|
    |            |tag__all       |text|фильтрация по всем указанным id тега <value> формата [1,1] (произвольное количество тегов)|
    |            |tag__in        |text|фильтрация по одному из указанных id тега <value> формата [1,1] (произвольное количество тегов)|
    |            |search         |text|поиск по указанному значению <value> в теле новости, имени автора, названии категории или тега|
    |            |sort           |text|Сортировка по указанному значению <value> по категориям(category), по возрастанию даты(created_asc), по убыванию даты(created_desc), по возрастанию количества изображений (images_asc), пр убыванию количества изображений (images_desc)|

* ### Пользователь

    * #### Получить список пользователей
    Получить список пользователей может любой пользователь.
    
    ```bash
    curl -i -X GET http://localhost:8000/users
    ```

    * #### Получить пользователя
    Получить информацию о пользователе может любой пользователь.

    ```bash
    curl -i -X GET http://localhost:8000/users/<user_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<user_id>  | int |идентификатор пользователя|
    
    * #### Добавить пользователя
    Добавить пользоователя может любой пользователь.

    ```bash
    curl -i -X POST -d "firstname=<firstname>&lastname=<lastname>&avatar=<avatar>" http://localhost:8000/users
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<firstname> | text |имя пользователя|
    | \<lastname> | text |фамилия пользователя|
    | \<avatar> | text |аватар пользователя|


    * #### Редактировать пользователя
    Редактировать пользователя может только сам пользователь-владелец записи.

    ```bash
    curl -i -X PUT -u '<user_id>:' -d "firstname=<firstname>&lastname=<lastname>&avatar=<avatar>" http://localhost:8000/users/<user_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<user_id> | int |идентификатор пользователя|
    | \<firstname> | text |имя пользователя|
    | \<lastname> | text |фамилия пользователя|
    | \<avatar> | text |аватар пользователя|

    * #### Удалить пользователя
    Удалить пользователя может только пользователь с правами администратора.

    ```bash
    curl -i -X DELETE -u '<admin_id>:' http://localhost:8000/users/<user_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<user_id>  | int |идентификатор пользователя|

* ### Тег

    * #### Получить список тегов
    Получить список тегов может любой пользователь.
    
    ```bash
    curl -i -X GET http://localhost:8000/tags
    ```

    * #### Получить тег
    Получить информацию о теге может любой пользователь.

    ```bash
    curl -i -X GET http://localhost:8000/tags/<tag_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<tag_id>  | int |идентификатор тега|
    
    * #### Добавить тег
    Добавить тег новости может только пользователь с правами администратора.

    ```bash
    curl -i -X POST -u '<admin_id>:' -d "name=<name>" http://localhost:8000/tags
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<name> | text |название тега|


    * #### Редактировать тег
    Редактировать тег может только пользователь с правами администратора.

    ```bash
    curl -i -X PUT -u 'admin_id:' -d "name=<name>" http://localhost:8000/tags/<tag_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<name> | text |название тега|
    | \<tag_id>  | int |идентификатор тега|

    * #### Удалить тег
    Удалить тег может только пользователь с правами администратора.

    ```bash
    curl -i -X DELETE -u '<admin_id>:' http://localhost:8000/tags/<tag_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<admin_id> | int |идентификатор администратора|
    | \<tag_id>  | int |идентификатор тега|

* ### Черновик

    * #### Получить список черновиков
    Получить список черновиков может автор черновиков и только своих.
    
    ```bash
    curl -i -X GET -u '<author_id>:' http://localhost:8000/drafts
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id>  | int |идентификатор автора черновика|

    * #### Получить черновик
    Получить информацию о черновике может автор черновика.

    ```bash
    curl -i -X GET -u '<author_id>:' http://localhost:8000/drafts/<draft_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id>  | int |идентификатор автора черновика|
    | \<draft_id>  | int |идентификатор черновика|
    
    * #### Добавить черновик
    Добавить черновик новости может только автор новости.

    ```bash
    curl -i -X POST -u '<author_id>:' http://localhost:8000/news/<news_id>/draft
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id> | int |идентификатор автора новости|
    | \<news_id> | int |идентификатор новости|

    * #### Редактировать черновик
    Редактировать запись о черновике может только автор черновика.

    ```bash
    curl -i -X PUT -u '<author_id>:' -d "article=<article>&category_id=<category_id>&content=<content>&main_image=<main_image>" http://localhost:8000/drafts/<draft_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id> | int |идентификатор автора черновика|
    | \<article> | text |заголовок новости|
    | \<category_id>  | int |идентификатор категории|
    | \<content> | text |тело новости|
    | \<main_image>| text |главное изображение|
    | \<draft_id>  | int |идентификатор черновика|

    * #### Опубликовать черновик
    Опубликовать черновик может только автор черновика.

    ```bash
    curl -i -X POST -u '<author_id>:' http://localhost:8000/drafts/<draft_id>/publish
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id> | int |идентификатор автора черновика|
    | \<draft_id>  | int |идентификатор черновика|
    
    * #### Удалить черновик
    Удалить черновик новости может только автор черновика.

    ```bash
    curl -i -X DELETE -u '<author_id>:' http://localhost:8000/drafts/<draft_id>
    ```

    |Параметр|Тип|Определение|
    |------------|-|-----------|
    | \<author_id> | int |идентификатор автора черновика|
    | \<draft_id>  | int |идентификатор черновика|

## 7. Тестирование

Каталог [testDump](https://github.com/Grotekar/news_portal/blob/master/testDump)
содержит файлы-миграции для заполнения базы данных тестовыми данными.

Чтобы выполнить эти миграции необходимо запустить скрипт [testDump/makeMigrations.php](https://github.com/Grotekar/news_portal/blob/master/testDump/makeMigrations.php).

Также в каталоге [tests/PHPUnit/Framework](https://github.com/Grotekar/news_portal/blob/master/tests/PHPUnit/Framework)
хранятся тесты запросов.

Запуск тестирования PHPUnit в среде Windows (Visual Studio Code):

    <...\news_portal> .\vendor\bin\phpunit .\tests\ 

## 8. Описание файлов

### [curl_query](https://github.com/Grotekar/news_portal/blob/master/curl_query)
Каталог содержит примеры cURL-запросов к API.

### [migrations](https://github.com/Grotekar/news_portal/blob/master/migrations)
Каталог содержит миграции базы данных.

### [testDump](https://github.com/Grotekar/news_portal/blob/master/testDump)
Каталог содержит файлы-миграции для заполнения базы данных тестовыми данными.


### [.env.example](https://github.com/Grotekar/news_portal/blob/master/.env.example)
Шаблон для конфигурационного файла.
