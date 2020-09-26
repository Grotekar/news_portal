<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class User extends AbstractTable
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

            $query = "SELECT * FROM users" .
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
        $query = "SELECT * FROM users WHERE user_id=(:user_id)";
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
            array_key_exists('firstname', $params) &&
            array_key_exists('lastname', $params) &&
            array_key_exists('avatar', $params)
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
        $this->createElement();
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
        $query = "INSERT INTO users (firstname, lastname, avatar) 
                VALUES (:firstname, :lastname, :avatar)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':firstname', $postParams['firstname']);
        $statement->bindParam(':lastname', $postParams['lastname']);
        $statement->bindParam(':avatar', $postParams['avatar']);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'lastInsertId' => $this->pdo->lastInsertId()
        ];

        return $result;
    }

    /**
     * Обработка PUT-запроса
     *
     * @return void
     */
    public function processingPutRequest(): void
    {
        if ($this->isAccessAllowed() === true) {
            $id = $this->getParamsRequest()[2];
            parse_str(file_get_contents('php://input'), $putParams);
            $this->updateElement($putParams, $id);
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
        $query = "UPDATE users SET
                firstname = :firstname, lastname = :lastname, avatar = :avatar
                WHERE user_id = :user_id";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(':firstname', $putParams['firstname']);
        $statement->bindParam(':lastname', $putParams['lastname']);
        $statement->bindParam(':avatar', $putParams['avatar']);
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
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':user_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;
    }
    
    /**
     * Идентификация пользователя (есть ли он в таблице users)
     *
     * @return bool
     */
    public function isAccessAllowed(): bool
    {
        $users = new User($this->pdo);

        if (
            isset($_SERVER['PHP_AUTH_USER']) === true &&
            $_SERVER['PHP_AUTH_USER'] !== '' &&
            $users->isGetElementCompleted($_SERVER['PHP_AUTH_USER'])['status'] === true
        ) {
            $receivedUser = $users->isGetElementCompleted($_SERVER['PHP_AUTH_USER']);

            if ($receivedUser['fetch'] !== null) {
                if ($receivedUser['fetch']['user_id'] === (string) $this->getParamsRequest()[2]) {
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
}
