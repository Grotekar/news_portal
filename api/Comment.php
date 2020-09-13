<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Comment extends AbstractTable
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
     * Подготовка данных перед передачей
     *
     * @return void
     */
    public function processingGetRequest(): void
    {
        // Получение данных
        $this->isGetRequestSuccess();
    }

    /**
     * Запрос для получения всех элементов (все комментарии новости)
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $newsId = $this->getParamsRequest()[3];

        $query = "SELECT c.comment_id,
                         c.news_id,
                         u.firstname,
                         u.lastname,
                         c.created,
                         c.text
                FROM comments c
                    JOIN users u
                        ON c.user_id = u.user_id
                WHERE news_id = :news_id";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':news_id', $newsId);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос для получения элемента (получение комментариев новости)
     *
     * @return bool
     */
    public function isGetElementComplited(int $id): bool
    {
        $newsId = $this->getParamsRequest()[3];

        $query = "SELECT c.comment_id,
                         c.news_id,
                         u.firstname,
                         u.lastname,
                         c.created,
                         c.text
                FROM comments c
                    JOIN users u
                        ON c.user_id = u.user_id
                WHERE news_id = (:news_id) AND comment_id = (:comment_id)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(":news_id", $newsId);
        $this->statment->bindParam(":comment_id", $id);

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
            array_key_exists('text', $params)
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
        $newsId = $this->getParamsRequest()[3];

        $query = "INSERT INTO comments (news_id, user_id, text) 
                VALUES (:news_id, :user_id, :text)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':news_id', $newsId);
        $this->statment->bindParam(':user_id', $_SERVER['PHP_AUTH_USER']);
        $this->statment->bindParam(':text', $postParams['text']);

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
        $query = "UPDATE comments SET
                news_id = :news_id, user_id = :user_id, text = :text
                WHERE comment_id = :comment_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':news_id', $putParams['news_id']);
        $this->statment->bindParam(':user_id', $putParams['user_id']);
        $this->statment->bindParam(':text', $putParams['text']);

        $this->statment->bindParam(':comment_id', $id);
        
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
    public function isDeleteElementCompleted(int $id): bool
    {
        $query = "DELETE FROM comments WHERE comment_id = :comment_id";
        $this->statment = $this->pdo->prepare($query);
        $this->statment->bindParam(':comment_id', $id);

        $status = $this->statment->execute();

        return $status;
    }
}
