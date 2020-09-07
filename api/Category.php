<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Category extends AbstractTable
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
     * Подготовка данных перед выдачей
     *
     * @return void
     */
    public function processingGetRequest(): void
    {
        $this->isGetRequestSuccess();
    }
    
    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $query = "SELECT * FROM categories";
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
        $query = "SELECT * FROM categories WHERE category_id=(:category_id)";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(":category_id", $id);
        
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
            array_key_exists('name', $params)
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
        $query = "INSERT INTO categories (name, parent_category) 
                VALUES (:name, :parent_category)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':name', $postParams['name']);
        $this->statment->bindParam(':parent_category', $postParams['parent_category']);
        
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
        $query = "UPDATE categories SET
            name = :name, parent_category = :parent_category
            WHERE category_id = :category_id";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':name', $putParams['name']);
        $this->statment->bindParam(':parent_category', $putParams['parent_category']);
        $this->statment->bindParam(':category_id', $id);
        
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
        $query = "DELETE FROM categories WHERE category_id = :category_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':category_id', $id);
        
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 16);
    }
}
