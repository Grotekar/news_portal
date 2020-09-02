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
    private PDO $pdo;
    protected string $tableName = 'news';
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
     * @return string
     */
    public function processingGetRequest(): string
    {
        if ($this->isGetRequestSuccess() === true) {
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
            $result = json_encode($listOfNewsArray);
        } else {
            $result = $this->response;
        }

        return $result;
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
        $listOfCategoriesJson = $categories->processingGetRequest();
        $listOfCategoriesArray = json_decode($listOfCategoriesJson);

        // Для каждой новости...
        foreach ($listOfNews as $key => $newsElement) {
            // Найти соответствующую категорию в таблице
            $parentCategoryId = null;
            $categoriesOfNewsElement = null;
            $categoryName = '';
            
            // Получить первую категорию с подкатегорией
            for ($i = 0; $i < count($listOfCategoriesArray); $i++) {
                if ($listOfCategoriesArray[$i]->category_id === $newsElement->category_id) {
                    $categoryName = $listOfCategoriesArray[$i]->name;
                    $categoriesOfNewsElement[] = $categoryName;
                    $parentCategoryId = $listOfCategoriesArray[$i]->parent_category;
                }
            }

            // Пока есть родительская, создавать вложенные категории
            while ($parentCategoryId !== null) {
                for ($i = 0; $i < count($listOfCategoriesArray); $i++) {
                    if ($listOfCategoriesArray[$i]->category_id === $parentCategoryId) {
                        // Запомнить имя найденной категории
                        $parentCategoryName = $listOfCategoriesArray[$i]->name;
                        // Поместить в начало массива
                        array_unshift($categoriesOfNewsElement, $parentCategoryName);
                        // Получить следующий id родительской новости
                        $parentCategoryId = $listOfCategoriesArray[$i]->parent_category;
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
     * Получение категорий по списку новостей
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

        // Фильтры
        if (
            array_key_exists('created', $_GET) ||
            array_key_exists('created__before', $_GET) ||
            array_key_exists('created__after', $_GET) ||
            array_key_exists('firstname', $_GET) ||
            array_key_exists('category_id', $_GET) ||
            array_key_exists('tag_id', $_GET) ||
            array_key_exists('tag__in', $_GET) ||
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

                if ($filterArg !== '') {
                    $filterArg = $filterArg . " AND news_has_tag.tag_id IN {$_GET['tag__in']}";
                } else {
                    $filterArg = $filterArg . "news_has_tag.tag_id IN {$_GET['tag__in']}";
                }
            } /* elseif (array_key_exists('tag__all', $_GET)) {
                $additionalTable = " JOIN news_has_tag
                                        ON n.news_id = news_has_tag.news_id";

                // Разобрать аргументы
                $argsInString = substr($_GET['tag__all'], 1, -1);
                $resultArgs = str_replace(',', ' AND ');

                /* print_r($tempArgsInString);
                exit; */
            /*
                if ($filterArg !== '') {
                    $filterArg = $filterArg . " AND news_has_tag.tag_id IN {$_GET['tag__in']}";
                } else {
                    $filterArg = $filterArg . "news_has_tag.tag_id IN {$_GET['tag__in']}";
                }
            } */

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

            /* if ($_GET['sort'] === 'image') {
                // Проверить была ли уже добавлена таблица images
                $table = " JOIN images c
                                ON images.news_id = n.news_id";
                $pos = stripos($additionalTable, $table);
                if ($pos === false) {
                    $additionalTable = $additionalTable . $table;
                }

                if ($orderByArg !== '') {
                    $orderByArg = $orderByArg . ', c.name';
                } else {
                    $orderByArg = "c.name";
                }
            } */
        }
        
        if ($filterArg === '') {
            $filter = '';
        }

        if ($orderByArg === '') {
            $orderBy = '';
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
                        ON n.author_id = u.user_id"
                . $additionalTable
                . $filter . $filterArg
                . $groupBy . $groupByArg
                . $orderBy . $orderByArg;
        $this->statment = $this->pdo->prepare($query);

        // print_r($this->statment);

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
            array_key_exists('author_id', $params) &&
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

        $newPostParamArticle = iconv('CP1251', 'UTF-8', $postParams['article']);
        $newPostParamContent = iconv('CP1251', 'UTF-8', $postParams['content']);

        $this->statment->bindParam(':article', $newPostParamArticle);
        $this->statment->bindParam(':author_id', $postParams['author_id']);
        $this->statment->bindParam(':category_id', $postParams['category_id']);
        $this->statment->bindParam(':content', $newPostParamContent);
        $this->statment->bindParam(':main_image', $postParams['main_image']);

        $status = $this->statment->execute();
        
        return $status;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return bool
     */
    public function isUpdateElementCompleted(int $id, array $putParams): bool
    {
        $query = "UPDATE news SET
            article = :article, author_id = :author_id, category_id = :category_id, 
            content = :content, main_image = :main_image
            WHERE news_id = :news_id";
        $this->statment = $this->pdo->prepare($query);

        $newPutParamArticle = iconv('CP1251', 'UTF-8', $putParams['article']);
        $newPutParamContent = iconv('CP1251', 'UTF-8', $putParams['content']);
        
        $this->statment->bindParam(':article', $newPutParamArticle);
        $this->statment->bindParam(':author_id', $putParams['author_id']);
        $this->statment->bindParam(':category_id', $putParams['category_id']);
        $this->statment->bindParam(':content', $newPutParamContent);
        $this->statment->bindParam(':main_image', $putParams['main_image']);
        $this->statment->bindParam(':news_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return bool
     */
    public function isDeleteteElementCompleted(int $id): bool
    {
        $query = "DELETE FROM news WHERE news_id = :news_id";
        $this->statment = $this->pdo->prepare($query);
        $this->statment->bindParam(':news_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 10);
    }
}
