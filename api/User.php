<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
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
     * @return object
     */
    protected function getStatmentForAllElements(): object
    {
        $query = "SELECT * FROM users";
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
        $query = "SELECT * FROM users WHERE user_id=(:user_id)";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(":user_id", $id);
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
     * @return object
     */
    protected function getStatmentForCreateElement(): object
    {
        
        $query = "INSERT INTO users (firstname, lastname, avatar, is_admin) 
                VALUES (:firstname, :lastname, :avatar, :is_admin)";
        $statment = $this->pdo->prepare($query);

        $newPostParamFirstname = iconv('CP1251', 'UTF-8', $_POST['firstname']);
        $newPostParamLastname = iconv('CP1251', 'UTF-8', $_POST['lastname']);

        $statment->bindParam(':firstname', $newPutParamFirstname);
        $statment->bindParam(':lastname', $newPutParamLastname);
        $statment->bindParam(':avatar', $_POST['avatar']);
        $statment->bindParam(':is_admin', $_POST['is_admin']);

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
        $query = "UPDATE users SET
                firstname = :firstname, lastname = :lastname, avatar = :avatar, is_admin = :is_admin
                WHERE user_id = :user_id";
        $statment = $this->pdo->prepare($query);
        
        $newPutParamFirstname = iconv('CP1251', 'UTF-8', $putParams['firstname']);
        $newPutParamLastname = iconv('CP1251', 'UTF-8', $putParams['lastname']);
        
        $statment->bindParam(':firstname', $newPutParamFirstname);
        $statment->bindParam(':lastname', $newPutParamLastname);
        $statment->bindParam(':avatar', $putParams['avatar']);
        $statment->bindParam(':is_admin', $putParams['is_admin']);
        $statment->bindParam(':user_id', $id);
        
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
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(':user_id', $id);

        return $statment;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 11);
    }
}
