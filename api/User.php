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
    protected string $tableName = 'users';
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
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $query = "SELECT * FROM users";
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

        $newPostParamFirstname = iconv('CP1251', 'UTF-8', $postParams['firstname']);
        $newPostParamLastname = iconv('CP1251', 'UTF-8', $postParams['lastname']);

        $this->statment->bindParam(':firstname', $newPostParamFirstname);
        $this->statment->bindParam(':lastname', $newPostParamLastname);
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
        
        $newPutParamFirstname = iconv('CP1251', 'UTF-8', $putParams['firstname']);
        $newPutParamLastname = iconv('CP1251', 'UTF-8', $putParams['lastname']);
        
        $this->statment->bindParam(':firstname', $newPutParamFirstname);
        $this->statment->bindParam(':lastname', $newPutParamLastname);
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
    public function isDeleteteElementCompleted(int $id): bool
    {
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $this->statment = $this->pdo->prepare($query);
        $this->statment->bindParam(':user_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 11);
    }
}
