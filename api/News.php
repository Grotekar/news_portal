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
            // Получить все тэги новости
            $query = "SELECT tags.name FROM tags
                        JOIN news_has_tag
                            ON tags.tag_id = news_has_tag.tag_id
                        WHERE news_has_tag.news_id = :news_id";
            $statment = $this->pdo->prepare($query);
            $statment->bindParam(':news_id', $newsElement->news_id);
            $statment->execute();
            
            //Вывести все тэги в массиве
            $tags = [];
            
            foreach ($statment->fetchAll(\PDO::FETCH_ASSOC) as $key => $tag) {
                $tags[] = $tag['name'];
            }

            // Добавить массив тэгов
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

        $filter = " WHERE ";
        $filterArg = '';
        if (array_key_exists('created', $_GET)) {
            $filterArg = "date(n.created) = '{$_GET['created']}'";
        } elseif (array_key_exists('created_before', $_GET)) {
            $filterArg = "date(n.created) < '{$_GET['created_before']}'";
        } elseif (array_key_exists('created_after', $_GET)) {
            $filterArg = "date(n.created) > '{$_GET['created_after']}'";
        } else {
            $filter = '';
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
                        ON n.author_id = u.user_id" . $filter . $filterArg;
        $this->statment = $this->pdo->prepare($query);
        $status = $this->statment->execute();

        //print_r($this->statment);
        //print_r($this->statment->fetchAll(PDO::FETCH_ASSOC));

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
