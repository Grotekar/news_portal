<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class News extends AbstractTable
{
    protected PDO $pdo;
    protected LoggerInterface $logger;
    
    /**
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->logger = new Logger();
        $this->pdo = $pdo;
    }

    /**
     * Подготовка данных перед выдачей
     *
     * @return void
     */
    public function processingGetRequest(): void
    {
        // Если
        // запрос к новости выполняется успешно и
        // существует дополнение к пути и
        // это дополнение равно comments
        if (
            $this->isGetRequestSuccess() === true &&
            isset($this->getParamsRequest()[3]) === true &&
            $this->getParamsRequest()[3] === 'comments'
        ) {
            $response = json_decode($this->response);

            // Если поле статус не существует (значит, новость есть),
            // то получить комментарии к новости
            if (!isset($response->status)) {
                $comments = new Comment($this->pdo);
                
                // Если нужно получить по id, либо всё
                if (
                    isset($comments->getParamsRequest()[4]) === true &&
                    $comments->getParamsRequest()[4] !== ''
                ) {
                    $comments->isGetElement($comments->getParamsRequest()[4]);
                } else {
                    $comments->isGetAll();
                }

                $this->response = $comments->getResponse();
            }
        // Если это обычный запрос на получение записей и он выполнен успешно
        } elseif ($this->isGetRequestSuccess() === true) {
            // Получение результатов запроса новостей
            $preliminaryDataFromNews = $this->response;

            // Преобразование новостей к нужной форме для обработки
            $listOfNewsArray = json_decode($preliminaryDataFromNews);
            if (is_array($listOfNewsArray) === false) {
                $listOfNewsArray = [$listOfNewsArray];
            }

            // На основании данных новостей заменить id категории на название с учетом вложенности
            $listOfNewsArray = $this->transformCategories($listOfNewsArray);

            // Получить теги новостей
            $listOfNewsArray = $this->getTagsForNews($listOfNewsArray);

            // Получить изображения к новости
            $listOfNewsArray = $this->getImagesForNews($listOfNewsArray);

            // Получение результата
            $this->response = json_encode($listOfNewsArray);
        }
    }

    /**
     * Получение категорий по списку новостей
     *
     * @param array $listOfNews
     *
     * @param array
     */
    private function transformCategories(array $listOfNews)
    {
        // Получение категорий через API
        $categories = new Category($this->pdo);
        $listOfCategories = $categories->isGetAllCompleted()['fetchAll'];

        // Для каждой новости...
        foreach ($listOfNews as $key => $newsElement) {
            // Найти соответствующую категорию в таблице
            $parentCategoryId = null;
            $categoriesOfNewsElement = null;
            $categoryName = '';
            
            // Получить первую категорию с подкатегорией
            for ($i = 0; $i < count($listOfCategories); $i++) {
                if ($listOfCategories[$i]['category_id'] === $newsElement->category_id) {
                    $categoriesOfNewsElement[] = $listOfCategories[$i]['name'];
                    $parentCategoryId = $listOfCategories[$i]['parent_category_id'];
                }
            }

            // Пока есть родительская, добавлять её в начало списка категорий
            while ($parentCategoryId !== null) {
                for ($i = 0; $i < count($listOfCategories); $i++) {
                    if ($listOfCategories[$i]['category_id'] === $parentCategoryId) {
                        // Запомнить имя найденной категории
                        $parentCategoryName = $listOfCategories[$i]['name'];
                        // Поместить в начало массива
                        array_unshift($categoriesOfNewsElement, $parentCategoryName);
                        // Получить следующий id родительской категории
                        $parentCategoryId = $listOfCategories[$i]['parent_category_id'];
                    }
                }
            }
            // Удалить category_id новости, а вместо него поместить массив вложенных категорий
            unset($newsElement->category_id);
            $newsElement->categories = $categoriesOfNewsElement;
        }

        return $listOfNews;
    }

    /**
     * Получение тегов по списку новостей
     *
     * @param array $listOfNews
     *
     * @param array
     */
    private function getTagsForNews(array $listOfNews)
    {
        // Для каждой новости...
        foreach ($listOfNews as $key => $newsElement) {
            // Получить все теги новости
            $query = "SELECT tags.name FROM tags
                        JOIN news_has_tag
                            ON tags.tag_id = news_has_tag.tag_id
                        WHERE news_has_tag.news_id = :news_id";
            $statement = $this->pdo->prepare($query);

            $statement->bindParam(':news_id', $newsElement->news_id);
            
            $statement->execute();
            
            //Вывести все теги в массиве
            $tags = [];
            
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $k => $tag) {
                $tags[] = $tag['name'];
            }

            // Добавить массив тегов
            $newsElement->tags = $tags;
        }

        return $listOfNews;
    }

    
    /**
     * Получение категорий по списку новостей
     *
     * @param array $listOfNews
     *
     * @param array
     */
    private function getImagesForNews(array $listOfNews)
    {
        // Для каждой новости...
        foreach ($listOfNews as $key => $newsElement) {
            // Получить все изображения к новости
            $query = "SELECT images.name FROM images
                        JOIN news_has_image
                            ON images.image_id = news_has_image.image_id
                        WHERE news_has_image.news_id = :news_id";
            $statement = $this->pdo->prepare($query);

            $statement->bindParam(':news_id', $newsElement->news_id);

            $statement->execute();
            
            //Вывести все изображения в массиве
            $images = [];
            
            foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $k => $image) {
                $images[] = $image['name'];
            }

            // Добавить массив изображений
            $newsElement->images = $images;
        }

        return $listOfNews;
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return array
     */
    public function isGetAllCompleted(): array
    {
        $groupBy = ' GROUP BY ';
        $groupByArg = 'n.news_id';
        $filter = ' WHERE ';
        $filterArg = '';
        $additionalTable = '';
        $orderBy = ' ORDER BY ';
        $orderByArg = ' n.news_id';
        $pagination = ' LIMIT ';
        $paginationArg = '';
        $having = ' HAVING ';
        $havingArg = '';

        $isValid = true;

        // Если фильтры, поиск, сортировки или пагинация
        if (
            array_key_exists('created', $_GET) ||
            array_key_exists('created__before', $_GET) ||
            array_key_exists('created__after', $_GET) ||
            array_key_exists('firstname', $_GET) ||
            array_key_exists('category_id', $_GET) ||
            array_key_exists('tag_id', $_GET) ||
            array_key_exists('tag__in', $_GET) ||
            array_key_exists('tag__all', $_GET) ||
            array_key_exists('article', $_GET) ||
            array_key_exists('content', $_GET) ||
            array_key_exists('search', $_GET) ||
            array_key_exists('sort', $_GET) ||
            array_key_exists('pagination', $_GET)
        ) {
            // Фильтры
            if (
                array_key_exists('created', $_GET) ||
                array_key_exists('created__before', $_GET) ||
                array_key_exists('created__after', $_GET) ||
                array_key_exists('firstname', $_GET) ||
                array_key_exists('category_id', $_GET) ||
                array_key_exists('tag_id', $_GET) ||
                array_key_exists('tag__in', $_GET) ||
                array_key_exists('tag__all', $_GET) ||
                array_key_exists('article', $_GET) ||
                array_key_exists('content', $_GET)
            ) {
                // Фильтр по дате
                if (array_key_exists('created', $_GET) === true) {
                    if ($this->isValidFilters('created') === true) {
                        $filterArg = "date(n.created) = '{$_GET['created']}'";
                    } else {
                        $isValid = false;
                    }
                } elseif (array_key_exists('created__before', $_GET) === true) {
                    if ($this->isValidFilters('created__before') === true) {
                        $filterArg = "date(n.created) < '{$_GET['created__before']}'";
                    } else {
                        $isValid = false;
                    }
                } elseif (array_key_exists('created__after', $_GET) === true) {
                    if ($this->isValidFilters('created__after') === true) {
                        $filterArg = "date(n.created) > '{$_GET['created__after']}'";
                    } else {
                        $isValid = false;
                    }
                }

                // Фильтр по имени автора
                if (array_key_exists('firstname', $_GET)) {
                    if ($filterArg !== '') {
                        $filterArg = $filterArg . " AND u.firstname = '{$_GET['firstname']}'";
                    } else {
                        $filterArg = $filterArg . "u.firstname = '{$_GET['firstname']}'";
                    }
                }

                // Фильтр по id категории
                if (array_key_exists('category_id', $_GET)) {
                    if ($this->isValidFilters('category_id') === true) {
                        if ($filterArg !== '') {
                            $filterArg = $filterArg . " AND n.category_id = '{$_GET['category_id']}'";
                        } else {
                            $filterArg = $filterArg . "n.category_id = '{$_GET['category_id']}'";
                        }
                    } else {
                        $isValid = false;
                    }
                }

                // Фильтр по id тега
                if (array_key_exists('tag_id', $_GET)) {
                    if ($this->isValidFilters('tag_id') === true) {
                        $additionalTable = $additionalTable . " JOIN news_has_tag
                                            on n.news_id = news_has_tag.news_id";
                        if ($filterArg !== '') {
                            $filterArg = $filterArg . " AND news_has_tag.tag_id = '{$_GET['tag_id']}'";
                        } else {
                            $filterArg = $filterArg . "news_has_tag.tag_id = '{$_GET['tag_id']}'";
                        }
                    } else {
                        $isValid = false;
                    }
                } elseif (array_key_exists('tag__in', $_GET)) {
                    if ($this->isValidFilters('tag__in') === true) {
                        $additionalTable = $additionalTable . " JOIN news_has_tag
                                            ON n.news_id = news_has_tag.news_id";// Разобрать аргументы
                        $resultArgs = substr($_GET['tag__in'], 1, -1);
                        if ($filterArg !== '') {
                            $filterArg = $filterArg . " AND news_has_tag.tag_id IN ({$resultArgs})";
                        } else {
                            $filterArg = $filterArg . "news_has_tag.tag_id IN ({$resultArgs})";
                        }
                    } else {
                        $isValid = false;
                    }
                } elseif (array_key_exists('tag__all', $_GET)) {
                    if ($this->isValidFilters('tag__all') === true) {
                        $additionalTable = " JOIN news_has_tag
                                            ON n.news_id = news_has_tag.news_id";
                        // Разобрать аргументы
                        $resultArgs = substr($_GET['tag__all'], 1, -1);
                        $countArgs = count(explode(',', $resultArgs));
                        if ($filterArg !== '') {
                            $filterArg = $filterArg . " AND news_has_tag.tag_id IN ({$resultArgs})";
                        } else {
                            $filterArg = $filterArg . "news_has_tag.tag_id IN ({$resultArgs})";
                        }
                        $havingArg = "COUNT(news_has_tag.tag_id) = " . $countArgs;
                        $orderByArg = '';
                    } else {
                        $isValid = false;
                    }
                }

                // Фильтр по вхождению в названии статьи
                if (array_key_exists('article', $_GET)) {
                    if ($filterArg !== '') {
                        $filterArg = $filterArg . " AND n.article LIKE '%{$_GET['article']}%'";
                    } else {
                        $filterArg = $filterArg . "n.article LIKE '%{$_GET['article']}%'";
                    }
                }

                // Фильтр по вхождению в контент
                if (array_key_exists('content', $_GET)) {
                    if ($filterArg !== '') {
                        $filterArg = $filterArg . " AND n.content LIKE '%{$_GET['content']}%'";
                    } else {
                        $filterArg = $filterArg . "n.content LIKE '%{$_GET['content']}%'";
                    }
                }
                // Поиск
            } elseif (array_key_exists('search', $_GET)) {
                // Поиск всех вхождений
                if (array_key_exists('search', $_GET)) {
                    $additionalTable = $additionalTable . " JOIN categories c
                                        ON c.category_id = n.category_id";

                    // Проверить была ли уже добавлена таблица news_has_tag
                    $table = " JOIN news_has_tag
                                ON n.news_id = news_has_tag.news_id";
                    $pos = stripos($additionalTable, $table);
                    if ($pos === false) {
                        $additionalTable = $additionalTable . $table;
                    }

                    $additionalTable = $additionalTable . " JOIN tags t
                                        ON t.tag_id = news_has_tag.tag_id";

                    if ($filterArg !== '') {
                        $filterArg = $filterArg . " AND (
                                                    n.content LIKE '%{$_GET['search']}%' 
                                                    OR u.firstname LIKE '%{$_GET['search']}%' 
                                                    OR c.name LIKE '%{$_GET['search']}%'
                                                    OR t.name LIKE '%{$_GET['search']}%'
                                                )";
                    } else {
                        $filterArg = $filterArg . " (
                        n.content LIKE '%{$_GET['search']}%' 
                        OR u.firstname LIKE '%{$_GET['search']}%' 
                        OR c.name LIKE '%{$_GET['search']}%'
                        OR t.name LIKE '%{$_GET['search']}%'
                    )";
                    }
                }
            }
            // Сортировка
            if (array_key_exists('sort', $_GET)) {
                if (
                    $_GET['sort'] === 'created_asc' ||
                    $_GET['sort'] === 'created_desc' ||
                    $_GET['sort'] === 'firstname' ||
                    $_GET['sort'] === 'category' ||
                    $_GET['sort'] === 'images_asc' ||
                    $_GET['sort'] === 'images_desc'
                ) {
                    if ($_GET['sort'] === 'created_asc') {
                        $orderByArg = "n.created";
                    } elseif ($_GET['sort'] === 'created_desc') {
                        $orderByArg = "n.created DESC";
                    } elseif ($_GET['sort'] === 'firstname') {
                        $orderByArg = 'u.firstname';
                    } elseif ($_GET['sort'] === 'category') {
                        // Проверить была ли уже добавлена таблица categories
                        $table = " JOIN categories c
                                    ON c.category_id = n.category_id";
                        $pos = stripos($additionalTable, $table);
                        if ($pos === false) {
                            $additionalTable = $additionalTable . $table;
                        }
                        $orderByArg = "c.name";
                    } elseif ($_GET['sort'] === 'images_asc') {
                        // Проверить была ли уже добавлена таблица images
                        $table = " JOIN news_has_image
                                    ON news_has_image.news_id = n.news_id";
                        $pos = stripos($additionalTable, $table);
                        if ($pos === false) {
                            $additionalTable = $additionalTable . $table;
                        }
                        $orderByArg = "COUNT(news_has_image.image_id)";
                    } elseif ($_GET['sort'] === 'images_desc') {
                        // Проверить была ли уже добавлена таблица images
                        $table = " JOIN news_has_image
                                    ON news_has_image.news_id = n.news_id";
                        $pos = stripos($additionalTable, $table);
                        if ($pos === false) {
                            $additionalTable = $additionalTable . $table;
                        }
                        $orderByArg = "COUNT(news_has_image.image_id) DESC";
                    }
                } else {
                    $isValid = false;
                }
            }

            // Пагинация
            if (array_key_exists('pagination', $_GET)) {
                // Проверка
                if ($this->isValidPagination($_GET['pagination']) === true) {
                    $paginationArg = substr($_GET['pagination'], 1, -1);
                } else {
                    $isValid = false;
                }
            }
        } elseif (count($_GET) !== 0) {
            $isValid = false;
        }

        if ($isValid === true) {
            if ($orderByArg === '') {
                $orderBy = '';
            }
            if ($filterArg === '') {
                $filter = '';
            }
            if ($paginationArg === '') {
                $pagination = '';
            }
            if ($havingArg === '') {
                $having = '';
            }
            $query = "SELECT
                            n.news_id,
                            n.article,
                            n.created,
                            u.firstname,
                            u.lastname,
                            n.category_id,
                            n.content,
                            n.main_image
                    FROM news n
                        JOIN users u
                            ON n.author_id = u.user_id" .
                $additionalTable .
                $filter . $filterArg .
                $groupBy . $groupByArg .
                $orderBy . $orderByArg .
                $pagination . $paginationArg .
                $having . $havingArg;
            $statement = $this->pdo->prepare($query);
            $result = [
                'status' => $statement->execute(),
                'errorInfo' => $statement->errorInfo()[2],
                'rowCount' => $statement->rowCount(),
                'fetchAll' => $statement->fetchAll(PDO::FETCH_ASSOC)
            ];

            return $result;
        } else {
            $result = [
                'status' => false,
                'errorInfo' => 'Bad Request',
                'rowCount' => 0
            ];

            return $result;
        }
    }

    /**
     * Запрос для получения элемента
     *
     * @param int $id
     *
     * @return array
     */
    public function isGetElementCompleted(int $id): array
    {
        $query = "SELECT * FROM news WHERE news_id=(:news_id)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(":news_id", $id);
        
        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount(),
            'fetch' => $statement->fetch(PDO::FETCH_ASSOC)
        ];

        return $result;
    }

    /**
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    protected function isExistsParamsInArray(array $params): bool
    {
        if (
            array_key_exists('article', $params) &&
            array_key_exists('category_id', $params) &&
            array_key_exists('content', $params) &&
            array_key_exists('main_image', $params)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Обработка POST-запроса
     *
     * @return void
     */
    public function processingPostRequest(): void
    {
        // Если
        // запрос к новости выполняется успешно и
        // существует дополнение к пути и
        // это дополнение равно comments
        if (
            $this->isGetRequestSuccess() === true &&
            isset($this->getParamsRequest()[3]) === true &&
            $this->getParamsRequest()[3] === 'comments'
        ) {
            // Если пользователь есть в таблице пользователей
            
            if ($this->isAccessAllowedToComments() === true) {
                $response = json_decode($this->getResponse());

                // Если поле статус не существует (значит, новость есть),
                // то создать комментарии к новости
                if (!isset($response->status)) {
                    $comments = new Comment($this->pdo);
                    
                    $comments->createElement();

                    $this->response = $comments->getResponse();
                }
            }

        // Если
        // запрос к новости выполняется успешно и
        // существует дополнение к пути и
        // это дополнение равно draft
        } elseif (
            $this->isGetRequestSuccess() === true &&
            isset($this->getParamsRequest()[3]) === true &&
            $this->getParamsRequest()[3] === 'draft'
        ) {
            $response = json_decode($this->response);

            // Если поле статус не существует (значит, новость есть)
            // и если автор новости собирается создать черновик,
            // то добавить черновик в таблицу drafts
            if (
                isset($response->status) === false &&
                $response->author_id === $_SERVER['PHP_AUTH_USER']
            ) {
                // Заполнить массив $_POST новыми значениями
                $_POST['news_id'] = $response->news_id;
                $_POST['article'] = $response->article;
                $_POST['category_id'] = $response->category_id;
                $_POST['content'] = $response->content;
                $_POST['main_image'] = $response->main_image;
                // Создать черновик
                $drafts = new Draft($this->pdo);

                $drafts->createElement();

                $this->response = $drafts->getResponse();
            } else {
                http_response_code(404);
                $this->response = json_encode([
                    'status' => false,
                    'message' => 'Not Found.'
                ]);
            }
        } elseif (isset($this->getParamsRequest()[4]) === true) {
            http_response_code(404);
            $this->response = json_encode([
                'status' => false,
                'message' => 'Not Found.'
            ]);
        } elseif ($this->isAuthor() === true) {
            $this->createElement();
        }
    }

    /**
     * Запрос на создание элемента
     *
     * @param array $postParams
     *
     * @return array
     */
    public function isCreateElementCompleted(array $postParams): array
    {
        $isValid = true;
        $hasTags = false;
        $hasImages = false;

        // Если в запросе есть теги
        if (array_key_exists('tags', $postParams) === true) {
            // Если теги верно заполнены
            if ($this->isValidPostArgs('tags', $postParams['tags']) === true) {
                // Проверить существование каждого тега
                $tagsWithoutBrackets = substr($postParams['tags'], 1, -1);
                $tagsArgs = explode(',', $tagsWithoutBrackets);
                $tagsTable = new Tag($this->pdo);

                foreach ($tagsArgs as $tag) {
                    if ($tagsTable->isGetElementCompleted($tag)['status'] === false) {
                        $isValid = false;
                        break;
                    }
                }
                // Если все найдены
                if ($isValid === true) {
                    $hasTags = true;
                }
            } else {
                $isValid = false;
            }
        }

        // Если в запросе есть изображения
        if (array_key_exists('images', $postParams) === true) {
            // Если изображения верно заполнены
            if ($this->isValidPostArgs('images', $postParams['images']) === true) {
                $hasImages = true;
            } else {
                $isValid = false;
            }
        }

        if ($isValid === true) {
            // Запрос на добавление новости
            $query = "INSERT INTO news (article, author_id, category_id, content, main_image)
                    VALUES (:article, :author_id, :category_id, :content, :main_image)";
            $statement = $this->pdo->prepare($query);

            $statement->bindParam(':article', $postParams['article']);
            $statement->bindParam(':author_id', $_SERVER['PHP_AUTH_USER']);
            $statement->bindParam(':category_id', $postParams['category_id']);
            $statement->bindParam(':content', $postParams['content']);
            $statement->bindParam(':main_image', $postParams['main_image']);

            $result = [
                'status' => $statement->execute(),
                'errorInfo' => $statement->errorInfo()[2],
                'lastInsertId' => $this->pdo->lastInsertId()
            ];

            // Запрос на связывание тегов и новости
            if ($hasTags === true) {
                // Разобрать теги
                $tagsWithoutBrackets = substr($postParams['tags'], 1, -1);
                $tags = explode(',', $tagsWithoutBrackets);

                // Для каждого тега
                foreach ($tags as $tag) {
                    $query = "INSERT INTO news_has_tag (news_id, tag_id)
                            VALUES (:news_id, :tag_id)";
                    $statement = $this->pdo->prepare($query);

                    $statement->bindParam(':news_id', $result['lastInsertId']);
                    $statement->bindParam(':tag_id', $tag);

                    $statement->execute();
                }
            }

            // Запрос на добавление изображения к новости
            if ($hasImages === true) {
                // Разобрать изображения
                $imagesWithoutBrackets = substr($postParams['images'], 1, -1);
                $images = explode(',', $imagesWithoutBrackets);

                $imagesTable = new Image($this->pdo);

                // Для каждого изображения добавить его в таблицу images и связать с новостью
                foreach ($images as $image) {
                    $imageId = $imagesTable->isCreateElementCompleted(['name' => $image])['lastInsertId'];

                    $query = "INSERT INTO news_has_image (news_id, image_id)
                            VALUES (:news_id, :image_id)";
                    $statement = $this->pdo->prepare($query);

                    $statement->bindParam(':news_id', $result['lastInsertId']);
                    $statement->bindParam(':image_id', $imageId);

                    $statement->execute();
                }
            }

            return $result;
        } else {
            $result = [
                'status' => false,
                'errorInfo' => 'Bad Request',
                'rowCount' => 0
            ];

            return $result;
        }
    }

    /**
     * Обработка PUT-запроса
     *
     * @return void
     */
    public function processingPutRequest(): void
    {
        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
    }

    /**
     * Запрос для обновления элемента
     *
     * @param array $putParams - параметры запроса
     * @param int $id
     *
     * @return array
     */
    public function isUpdateElementCompleted(array $putParams, int $id): array
    {
        $query = "UPDATE news SET
            article = :article, author_id = :author_id, category_id = :category_id, 
            content = :content, main_image = :main_image
            WHERE news_id = :news_id";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(':article', $putParams['article']);
        $statement->bindParam(':author_id', $_SERVER['PHP_AUTH_USER']);
        $statement->bindParam(':category_id', $putParams['category_id']);
        $statement->bindParam(':content', $putParams['content']);
        $statement->bindParam(':main_image', $putParams['main_image']);
        $statement->bindParam(':news_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2]
        ];

        return $result;
    }

    /**
     * Обработка DELETE-запроса
     *
     * @return void
     */
    public function processingDeleteRequest(): void
    {
        // Если
        // запрос к новости выполняется успешно и
        // существует дополнение к пути и
        // это дополнение равно comments
        if (
            $this->isGetRequestSuccess() === true &&
            isset($this->getParamsRequest()[3]) === true &&
            $this->getParamsRequest()[3] === 'comments'
        ) {
            // Если пользователь удаляет свой комментарий
            if ($this->isAccessAllowedToComment() === true) {
                $response = json_decode($this->getResponse());

                // Если поле статус не существует (значит, комментарий есть),
                // то удалить комментарий к новости
                if (isset($response->status) === false) {
                    $comments = new Comment($this->pdo);
                    
                    $comments->deleteElement(4);

                    $this->response = $comments->getResponse();
                }
            }
        } elseif (isset($this->getParamsRequest()[3]) === true) {
            http_response_code(404);
            $this->response = json_encode([
                'status' => false,
                'message' => 'Not Found.'
            ]);
        } elseif ($this->isAdmin() === true) {
            $this->deleteElement();
        }
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return array
     */
    public function isDeleteElementCompleted(int $id): array
    {
        $query = "DELETE FROM news WHERE news_id = :news_id";
        $statement = $this->pdo->prepare($query);
        $statement->bindParam(':news_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;
    }

    /**
     * Идентификация пользователя для доступа к таблице comments
     * к своему комментарию
     *
     * @return bool
     */
    public function isAccessAllowedToComment(): bool
    {
        $comments = new Comment($this->pdo);

        // Если есть id пользователя в запросе и он не пуст
        // и есть id комментария
        // и удалось получить комментарий
        if (
            isset($_SERVER['PHP_AUTH_USER']) === true &&
            $_SERVER['PHP_AUTH_USER'] !== '' &&
            isset($this->getParamsRequest()[4]) === true &&
            $comments->isGetElementCompleted($this->getParamsRequest()[4])['status'] === true
        ) {
            $newsId = $this->getParamsRequest()[2];

            $query = "SELECT *
                    FROM comments 
                    WHERE news_id = (:news_id) AND comment_id = (:comment_id)";
            $statement = $this->pdo->prepare($query);

            $statement->bindParam(":news_id", $newsId);
            $statement->bindParam(":comment_id", $this->getParamsRequest()[4]);

            $statement->execute();
            
            if ($statement !== null) {
                $comment = $statement->fetch(\PDO::FETCH_ASSOC);

                if ($comment['user_id'] === (string) $_SERVER['PHP_AUTH_USER']) {
                    $this->logger->debug('Access is allowed.');
                    return true;
                } else {
                    $this->logger->debug('Access denied');
                }
            }
        }

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;
    }

    /**
     * Идентификация пользователя для доступа к таблице comments
     *
     * @return bool
     */
    public function isAccessAllowedToComments(): bool
    {
        $users = new User($this->pdo);

        if (
            isset($_SERVER['PHP_AUTH_USER']) === true &&
            $_SERVER['PHP_AUTH_USER'] !== '' &&
            $users->isGetElementCompleted($_SERVER['PHP_AUTH_USER'])['status'] === true
        ) {
            $receivedUser = $users->isGetElementCompleted($_SERVER['PHP_AUTH_USER']);

            if ($receivedUser['fetch'] !== null) {
                if ($receivedUser['fetch']['user_id'] === $_SERVER['PHP_AUTH_USER']) {
                    $this->logger->debug('Access is allowed.');
                    return true;
                } else {
                    $this->logger->debug('Access denied');
                }
            }
        } else {
            $this->logger->debug('User not found.');
        }

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;
    }

    /**
     * Валидация фильтров
     *
     * @param string $filterName
     *
     * @return bool
     */
    protected function isValidFilters(string $filterName): bool
    {
        $numberOfOccurrences = 0;

        if (
            $filterName === 'created' ||
            $filterName === 'created__before' ||
            $filterName === 'created__after'
        ) {
            if (strlen($_GET[$filterName]) === 10) {
                $pattern = "/^[0-9]{4}-[0-9]{2}-[0-9]{2}/";
                $numberOfOccurrences = preg_match_all($pattern, $_GET[$filterName]);
            }
        }

        if ($filterName === 'category_id' || $filterName === 'tag_id') {
            $pattern = "/^[0-9]+$/";
            $numberOfOccurrences = preg_match_all($pattern, $_GET[$filterName]);
        }

        if ($filterName === 'tag__in' || $filterName === 'tag__all') {
            $pattern = "/^\[[0-9]+(,[0-9]+)*\]/";
            $numberOfOccurrences = preg_match_all($pattern, $_GET[$filterName]);
        }

        if ($numberOfOccurrences === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Валидация POST-аргументов
     *
     * @param string $argName
     * @param string $argValue
     *
     * @return bool
     */
    protected function isValidPostArgs(string $argName, string $argValue): bool
    {
        $numberOfOccurrences = 0;

        if ($argName === 'tags') {
            $pattern = "/^\[[0-9]+(,[0-9]+)*\]/";
            $numberOfOccurrences = preg_match_all($pattern, $argValue);
        } elseif ($argName === 'images') {
            $pattern = "/^\[[a-zA-Z0-9\\\;\.\/\:\-\+]+(,[a-zA-Z0-9\\\;\.\/\:\-\+]+)*\]/";
            $numberOfOccurrences = preg_match_all($pattern, $argValue);
        }

        if ($numberOfOccurrences === 1) {
            return true;
        } else {
            return false;
        }
    }
}
