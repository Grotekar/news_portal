<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
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
     * Запрос для получения всех элементов
     *
     * @return object
     */
    protected function getStatmentForAllElements(): object
    {
        $query = "SELECT * FROM news";
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
