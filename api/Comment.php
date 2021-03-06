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
     * @return array
     */
    public function isGetAllCompleted(): array
    {
        $pagination = ' LIMIT ';
        $paginationArg = '';
        $isValid = true;
        $result = [];

        // Пагинация
        if (array_key_exists('pagination', $_GET)) {
            // Проверка
            if ($this->isValidPagination($_GET['pagination']) === true) {
                $paginationArg = substr($_GET['pagination'], 1, -1);
            } else {
                $this->logger->debug('Invalid pagination');
                $isValid = false;
            }
        }

        if ($isValid === true) {
            if ($paginationArg === '') {
                $pagination = '';
            }

            $newsId = $this->getParamsRequest()[2];

            $query = "SELECT c.comment_id,
                             c.news_id,
                             u.firstname,
                             u.lastname,
                             c.created,
                             c.text
                    FROM comments c
                        JOIN users u
                            ON c.user_id = u.user_id
                    WHERE news_id = :news_id" .
                    $pagination . $paginationArg;
            $statement = $this->pdo->prepare($query);

            $statement->bindParam(':news_id', $newsId);

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
     * Запрос для получения элемента (получение комментариев новости)
     *
     * @param int $id
     *
     * @return array
     */
    public function isGetElementCompleted(int $id): array
    {
        $newsId = $this->getParamsRequest()[2];

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
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(":news_id", $newsId);
        $statement->bindParam(":comment_id", $id);

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
     * @return array
     */
    public function isCreateElementCompleted(array $postParams): array
    {
        $newsId = $this->getParamsRequest()[2];

        $query = "INSERT INTO comments (news_id, user_id, text) 
                VALUES (:news_id, :user_id, :text)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':news_id', $newsId);
        $statement->bindParam(':user_id', $_SERVER['PHP_AUTH_USER']);
        $statement->bindParam(':text', $postParams['text']);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'lastInsertId' => $this->pdo->lastInsertId()
        ];

        return $result;
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
        $query = "UPDATE comments SET
                news_id = :news_id, user_id = :user_id, text = :text
                WHERE comment_id = :comment_id";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(':news_id', $putParams['news_id']);
        $statement->bindParam(':user_id', $putParams['user_id']);
        $statement->bindParam(':text', $putParams['text']);
        $statement->bindParam(':comment_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2]
        ];

        return $result;
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
        $query = "DELETE FROM comments WHERE comment_id = :comment_id";
        $statement = $this->pdo->prepare($query);
        $statement->bindParam(':comment_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;
    }
}
