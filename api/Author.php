<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Author extends AbstractTable
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
        if ($this->isAdmin() === true) {
            $this->isGetRequestSuccess();
        }
    }

    /**
     * Запрос для получения всех элементов
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

            $query = "SELECT u.user_id,
                            u.firstname,
                            u.lastname,
                            u.avatar,
                            u.created,
                            u.is_admin,
                            a.description
                    FROM users u
                    JOIN authors a
                        ON u.user_id = a.user_id" .
                    $pagination . $paginationArg;
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
        $query = "SELECT u.user_id,
                        u.firstname,
                        u.lastname,
                        u.avatar,
                        u.created,
                        u.is_admin,
                        a.description
                FROM users u
                JOIN authors a
                    ON u.user_id = a.user_id
                WHERE a.user_id = (:user_id)";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(":user_id", $id);
        
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
            array_key_exists('description', $params)
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
        if ($this->isAdmin() === true) {
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
        // Если user_id не указан, вернуть ошибку
        if (array_key_exists('user_id', $postParams) === false) {
            $result = [
                'status' => false,
                "errorInfo" => "Bad request or not enough data"
            ];

            return $result;
        } else {
            $query = "INSERT INTO authors (user_id, description) 
                    VALUES (:user_id, :description)";
            $statement = $this->pdo->prepare($query);
            $statement->bindParam(':user_id', $postParams['user_id']);
            $statement->bindParam(':description', $postParams['description']);
            $result = [
                'status' => $statement->execute(),
                'errorInfo' => $statement->errorInfo()[2],
                'lastInsertId' => $this->pdo->lastInsertId()
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
        if ($this->isAdmin() === true) {
            if (isset($this->getParamsRequest()[2]) === true) {
                $id = $this->getParamsRequest()[2];
                parse_str(file_get_contents('php://input'), $putParams);
                $this->updateElement($putParams, $id);
            } else {
                http_response_code(404);
                $this->response = json_encode([
                    'status' => false,
                    'message' => 'Not Found.'
                ]);
            }
        }
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
        $query = "UPDATE authors SET
                description = :description WHERE user_id = :user_id";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(':description', $putParams['description']);
        $statement->bindParam(':user_id', $id);

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
        if ($this->isAdmin() === true) {
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
        $query = "DELETE FROM authors WHERE user_id = :user_id";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':user_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;
    }
}
