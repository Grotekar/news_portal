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
            isset($this->getParamsRequest()[4]) === true &&
            $this->getParamsRequest()[4] === 'comments'
        ) {
            $response = json_decode($this->response);

            // Если поле статус не существует (значит, новость есть),
            // то получить комментарии к новости
            if (!isset($response->status)) {
                $comments = new Comment($this->pdo);
                
                // Если нужно получить по id, либо всё
                if (
                    isset($comments->getParamsRequest()[5]) === true &&
                    $comments->getParamsRequest()[5] !== ''
                ) {
                    $comments->isGetElement($comments->getParamsRequest()[5]);
                } else {
                    $comments->isGetAll();
                }

                $this->response = $comments->getResponse();
            }
        } elseif ($this->isGetRequestSuccess() === true) {
            if ($this->statment->rowCount() !== 0) {
                // Получение данных новостей
                $preliminaryDataFromNews = $this->response;
                
                // Преобразование новостей к нужной форме для обработки
                $listOfNewsArray = json_decode($preliminaryDataFromNews);
                if (is_array($listOfNewsArray) === false) {
                    $listOfNewsArray = [$listOfNewsArray];
                }
                
                // На основании данных новостей заменить id категории на название с учетом вложенности
                $listOfNewsArray = $this->getCategories($listOfNewsArray);

                // Получить теги новостей
                $listOfNewsArray = $this->getTagsForNews($listOfNewsArray);

                // Получить изображения к новости
                $listOfNewsArray = $this->getImagesForNews($listOfNewsArray);

                // Получение результата
                $this->response = json_encode($listOfNewsArray);
            }
        }
    }

    /**
     * Получение категорий по списку новостей
     *
     * @param array $listOfNews
     *
     * @param array
     */
    private function getCategories(array $listOfNews)
    {
        // Получение категорий через API
        $categories = new Category($this->pdo);
        $categories->isGetAllComplited();
        $statmentOfCategories = $categories->getStatment();

        $listOfCategories = $statmentOfCategories->fetchAll(\PDO::FETCH_ASSOC);

        // Для каждой новости...
        foreach ($listOfNews as $key => $newsElement) {
            // Найти соответствующую категорию в таблице
            $parentCategoryId = null;
            $categoriesOfNewsElement = null;
            $categoryName = '';
            
            // Получить первую категорию с подкатегорией
            for ($i = 0; $i < count($listOfCategories); $i++) {
                if ($listOfCategories[$i]['category_id'] === $newsElement->category_id) {
                    $categoryName = $listOfCategories[$i]['name'];
                    $categoriesOfNewsElement[] = $categoryName;
                    $parentCategoryId = $listOfCategories[$i]['parent_category_id'];
                }
            }

            // Пока есть родительская, создавать вложенные категории
            while ($parentCategoryId !== null) {
                for ($i = 0; $i < count($listOfCategories); $i++) {
                    if ($listOfCategories[$i]['category_id'] === $parentCategoryId) {
                        // Запомнить имя найденной категории
                        $parentCategoryName = $listOfCategories[$i]['name'];
                        // Поместить в начало массива
                        array_unshift($categoriesOfNewsElement, $parentCategoryName);
                        // Получить следующий id родительской новости
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
            $statment = $this->pdo->prepare($query);

            $statment->bindParam(':news_id', $newsElement->news_id);
            
            $statment->execute();
            
            //Вывести все теги в массиве
            $tags = [];
            
            foreach ($statment->fetchAll(\PDO::FETCH_ASSOC) as $key => $tag) {
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
            $query = "SELECT image_id FROM images
                        WHERE news_id = :news_id";
            $statment = $this->pdo->prepare($query);

            $statment->bindParam(':news_id', $newsElement->news_id);

            $statment->execute();
            
            //Вывести все изображения в массиве
            $images = [];
            
            foreach ($statment->fetchAll(\PDO::FETCH_ASSOC) as $key => $image) {
                $images[] = $image['image_id'];
            }

            // Добавить массив изображений
            $newsElement->images = $images;
        }

        return $listOfNews;
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        // Если есть фильтр, то добавить его к запросу

        $groupBy = ' GROUP BY ';
        $groupByArg = 'n.news_id';
        $filter = ' WHERE ';
        $filterArg = '';
        $additionalTable = '';
        $orderBy = ' ORDER BY ';
        $orderByArg = '';
        $pagination = ' LIMIT ';
        $paginationArg = '';
        $having = ' HAVING ';
        $havingArg = '';

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
            if (array_key_exists('created', $_GET)) {
                $filterArg = "date(n.created) = '{$_GET['created']}'";
            } elseif (array_key_exists('created__before', $_GET)) {
                $filterArg = "date(n.created) < '{$_GET['created_before']}'";
            } elseif (array_key_exists('created__after', $_GET)) {
                $filterArg = "date(n.created) > '{$_GET['created_after']}'";
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
                if ($filterArg !== '') {
                    $filterArg = $filterArg . " AND n.category_id = '{$_GET['category_id']}'";
                } else {
                    $filterArg = $filterArg . "n.category_id = '{$_GET['category_id']}'";
                }
            }

            // Фильтр по id тега
            if (array_key_exists('tag_id', $_GET)) {
                $additionalTable = $additionalTable . " JOIN news_has_tag
                                        on n.news_id = news_has_tag.news_id";

                if ($filterArg !== '') {
                    $filterArg = $filterArg . " AND news_has_tag.tag_id = '{$_GET['tag_id']}'";
                } else {
                    $filterArg = $filterArg . "news_has_tag.tag_id = '{$_GET['tag_id']}'";
                }
            } elseif (array_key_exists('tag__in', $_GET)) {
                $additionalTable = $additionalTable . " JOIN news_has_tag
                                        ON n.news_id = news_has_tag.news_id";
                
                // Разобрать аргументы
                $resultArgs = substr($_GET['tag__in'], 1, -1);

                if ($filterArg !== '') {
                    $filterArg = $filterArg . " AND news_has_tag.tag_id IN ({$resultArgs})";
                } else {
                    $filterArg = $filterArg . "news_has_tag.tag_id IN ({$resultArgs})";
                }
            } elseif (array_key_exists('tag__all', $_GET)) {
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
            if ($_GET['sort'] === 'created_asc') {
                $orderByArg = "n.created";
            } elseif ($_GET['sort'] === 'created_desc') {
                $orderByArg = "n.created DESC";
            }

            if ($_GET['sort'] === 'firstname') {
                if ($orderByArg !== '') {
                    $orderByArg = $orderByArg . ', u.firstname';
                } else {
                    $orderByArg = "u.firstname";
                }
            }

            if ($_GET['sort'] === 'category') {
                // Проверить была ли уже добавлена таблица categories
                $table = " JOIN categories c
                                ON c.category_id = n.category_id";
                $pos = stripos($additionalTable, $table);
                if ($pos === false) {
                    $additionalTable = $additionalTable . $table;
                }
                
                if ($orderByArg !== '') {
                    $orderByArg = $orderByArg . ', c.name';
                } else {
                    $orderByArg = "c.name";
                }
            }

            if ($_GET['sort'] === 'images_asc') {
                // Проверить была ли уже добавлена таблица images
                $table = " JOIN images
                                ON images.news_id = n.news_id";
                $pos = stripos($additionalTable, $table);
                if ($pos === false) {
                    $additionalTable = $additionalTable . $table;
                }

                if ($orderByArg !== '') {
                    $orderByArg = $orderByArg . ', COUNT(images.image_id)';
                } else {
                    $orderByArg = "COUNT(images.image_id)";
                }
            } elseif ($_GET['sort'] === 'images_desc') {
                // Проверить была ли уже добавлена таблица images
                $table = " JOIN images
                                ON images.news_id = n.news_id";
                $pos = stripos($additionalTable, $table);
                if ($pos === false) {
                    $additionalTable = $additionalTable . $table;
                }

                if ($orderByArg !== '') {
                    $orderByArg = $orderByArg . ', COUNT(images.image_id) DESC';
                } else {
                    $orderByArg = "COUNT(images.image_id) DESC";
                }
            }
        }

        // Пагинация
        if (array_key_exists('pagination', $_GET)) {
             // Разобрать аргументы
            $paginationArg = substr($_GET['pagination'], 1, -1);
        }
        
        if ($filterArg === '') {
            $filter = '';
        }

        if ($orderByArg === '') {
            $orderBy = '';
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
        $this->statment = $this->pdo->prepare($query);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос для получения элемента
     *
     * @return bool
     */
    public function isGetElementComplited(int $id): bool
    {
        $query = "SELECT * FROM news WHERE news_id=(:news_id)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(":news_id", $id);
        
        $status = $this->statment->execute();

        return $status;
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
            isset($this->getParamsRequest()[4]) === true &&
            $this->getParamsRequest()[4] === 'comments'
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
            isset($this->getParamsRequest()[4]) === true &&
            $this->getParamsRequest()[4] === 'draft'
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
                $this->getNotFound();
            }
        } elseif (isset($this->getParamsRequest()[4]) === true) {
            $this->getNotFound();
        } elseif ($this->isAuthor() === true) {
            $this->createElement();
        }
    }

    /**
     * Запрос на создание элемента
     *
     * @param array $postParams
     *
     * @return bool
     */
    public function isCreateElementCompleted(array $postParams): bool
    {
        $query = "INSERT INTO news (article, author_id, category_id, content, main_image)
                VALUES (:article, :author_id, :category_id, :content, :main_image)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':article', $postParams['article']);
        $this->statment->bindParam(':author_id', $_SERVER['PHP_AUTH_USER']);
        $this->statment->bindParam(':category_id', $postParams['category_id']);
        $this->statment->bindParam(':content', $postParams['content']);
        $this->statment->bindParam(':main_image', $postParams['main_image']);

        $status = $this->statment->execute();
        
        return $status;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param array $putParams - параметры запроса
     * @param int $id
     *
     * @return bool
     */
    public function isUpdateElementCompleted(array $putParams, int $id): bool
    {
        $query = "UPDATE news SET
            article = :article, author_id = :author_id, category_id = :category_id, 
            content = :content, main_image = :main_image
            WHERE news_id = :news_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':article', $putParams['article']);
        $this->statment->bindParam(':author_id', $_SERVER['PHP_AUTH_USER']);
        $this->statment->bindParam(':category_id', $putParams['category_id']);
        $this->statment->bindParam(':content', $putParams['content']);
        $this->statment->bindParam(':main_image', $putParams['main_image']);
        $this->statment->bindParam(':news_id', $id);

        $status = $this->statment->execute();

        return $status;
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
            isset($this->getParamsRequest()[4]) === true &&
            $this->getParamsRequest()[4] === 'comments'
        ) {
            // Если пользователь удаляет свой комментарий
            if ($this->isAccessAllowedToComment() === true) {
                $response = json_decode($this->getResponse());

                // Если поле статус не существует (значит, комментарий есть),
                // то удалить комментарий к новости
                if (isset($response->status) === false) {
                    $comments = new Comment($this->pdo);
                    
                    $comments->deleteElement(5);

                    $this->response = $comments->getResponse();
                }
            }
        } elseif (isset($this->getParamsRequest()[4]) === true) {
            $this->getNotFound();
        } elseif ($news->isAdmin() === true) {
            $this->deleteElement();
        }
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return bool
     */
    public function isDeleteElementCompleted(int $id): bool
    {
        $query = "DELETE FROM news WHERE news_id = :news_id";
        $this->statment = $this->pdo->prepare($query);
        $this->statment->bindParam(':news_id', $id);

        $status = $this->statment->execute();

        return $status;
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
            isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' &&
            isset($this->getParamsRequest()[5]) &&
            $comments->isGetElementComplited($this->getParamsRequest()[5]) === true
        ) {
            $newsId = $this->getParamsRequest()[3];

            $query = "SELECT *
                    FROM comments 
                    WHERE news_id = (:news_id) AND comment_id = (:comment_id)";
            $this->statment = $this->pdo->prepare($query);

            $this->statment->bindParam(":news_id", $this->getParamsRequest()[3]);
            $this->statment->bindParam(":comment_id", $this->getParamsRequest()[5]);

            $this->statment->execute();
            
            if ($this->statment !== null) {
                $comment = $this->statment->fetch(\PDO::FETCH_ASSOC);

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
            isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' &&
            $users->isGetElementComplited($_SERVER['PHP_AUTH_USER']) === true
        ) {
            $this->statment = $users->getStatment();

            if ($this->statment !== null) {
                $users = $this->statment->fetch(\PDO::FETCH_ASSOC);

                if ($users['user_id'] === $_SERVER['PHP_AUTH_USER']) {
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
}
