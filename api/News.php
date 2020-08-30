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
    private string $tableName = 'news';
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
    public function difficultGetRequest()
    {
        // Получение данных новостей
        $preliminaryDataFromNews = $this->getRequest();
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
        $listOfCategoriesJson = $categories->getRequest();
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
     * @return object
     */
    protected function getStatmentForAllElements(): object
    {
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
                        ON n.author_id = u.user_id";
        $statment = $this->pdo->prepare($query);
        return $statment;
    }

    /**
     * Запрос для получения элемента
     *
     * @return object
     */
    protected function getStatmentForGetElement(int $id): object
    {
        $query = "SELECT * FROM news WHERE news_id=(:news_id)";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(":news_id", $id);
        return $statment;
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
     * @return object
     */
    protected function getStatmentForCreateElement(): object
    {
        
        $query = "INSERT INTO news (article, author_id, category_id, content, main_image) 
                VALUES (:article, :author_id, :category_id, :content, :main_image)";
        $statment = $this->pdo->prepare($query);

        $newPostParamArticle = iconv('CP1251', 'UTF-8', $_POST['article']);
        $newPostParamContent = iconv('CP1251', 'UTF-8', $_POST['content']);

        $statment->bindParam(':article', $newPostParamArticle);
        $statment->bindParam(':author_id', $_POST['author_id']);
        $statment->bindParam(':category_id', $_POST['category_id']);
        $statment->bindParam(':content', $newPostParamContent);
        $statment->bindParam(':main_image', $_POST['main_image']);

        return $statment;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return object
     */
    protected function getStatmentForUpdateElement(int $id, array $putParams): object
    {
        $query = "UPDATE news SET
            article = :article, author_id = :author_id, category_id = :category_id, 
            content = :content, main_image = :main_image
            WHERE news_id = :news_id";
        $statment = $this->pdo->prepare($query);

        $newPutParamArticle = iconv('CP1251', 'UTF-8', $putParams['article']);
        $newPutParamContent = iconv('CP1251', 'UTF-8', $putParams['content']);
        
        $statment->bindParam(':article', $newPutParamArticle);
        $statment->bindParam(':author_id', $putParams['author_id']);
        $statment->bindParam(':category_id', $putParams['category_id']);
        $statment->bindParam(':content', $newPutParamContent);
        $statment->bindParam(':main_image', $putParams['main_image']);
        $statment->bindParam(':news_id', $id);

        return $statment;
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return object
     */
    protected function getStatmentForDeleteElement(int $id): object
    {
        $query = "DELETE FROM news WHERE news_id = :news_id";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(':news_id', $id);

        return $statment;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 10);
    }
}
