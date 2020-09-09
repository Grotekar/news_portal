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
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $pagination = ' LIMIT ';
        $paginationArg = '';
        
        
        // Пагинация
        if (array_key_exists('pagination', $_GET)) {
            // Разобрать аргументы
            $paginationArg = substr($_GET['pagination'], 1, -1);
        }

        $query = "SELECT * FROM users" .
                    $pagination . $paginationArg;
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
        $query = "SELECT * FROM users WHERE user_id=(:user_id)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(":user_id", $id);

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
            array_key_exists('firstname', $params) &&
            array_key_exists('lastname', $params) &&
            array_key_exists('avatar', $params) &&
            array_key_exists('is_admin', $params)
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
        $query = "INSERT INTO users (firstname, lastname, avatar, is_admin) 
                VALUES (:firstname, :lastname, :avatar, :is_admin)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':firstname', $postParams['firstname']);
        $this->statment->bindParam(':lastname', $postParams['lastname']);
        $this->statment->bindParam(':avatar', $postParams['avatar']);
        $this->statment->bindParam(':is_admin', $postParams['is_admin']);

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
        $query = "UPDATE users SET
                firstname = :firstname, lastname = :lastname, avatar = :avatar, is_admin = :is_admin
                WHERE user_id = :user_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':firstname', $putParams['firstname']);
        $this->statment->bindParam(':lastname', $putParams['lastname']);
        $this->statment->bindParam(':avatar', $putParams['avatar']);
        $this->statment->bindParam(':is_admin', $putParams['is_admin']);
        $this->statment->bindParam(':user_id', $id);
        
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
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $this->statment = $this->pdo->prepare($query);
        $this->statment->bindParam(':user_id', $id);

        $status = $this->statment->execute();

        return $status;
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
            isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' &&
            $users->isGetElementComplited($_SERVER['PHP_AUTH_USER']) === true
        ) {
            $this->statment = $users->getStatment();

            if ($this->statment !== null) {
                $users = $this->statment->fetch(\PDO::FETCH_ASSOC);

                if ($users['user_id'] === (string) $this->getParamsRequest()[3]) {
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
