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

            $query = "SELECT * FROM categories" .
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
        $query = "SELECT * FROM categories WHERE category_id=(:category_id)";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(":category_id", $id);
        
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
            array_key_exists('name', $params)
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
        //Проверка parent_category_id
        if (array_key_exists('parent_category_id', $postParams)) {
            // Если такого id не существует, то ошибка
            if ($this->isValidParentCategoryId($postParams['parent_category_id']) === false) {
                $result = [
                    'status' => false,
                    'errorInfo' => 'Invalid parent_category_id'
                ];

                return $result;
            }
        }

        $query = "INSERT INTO categories (name, parent_category_id) 
                VALUES (:name, :parent_category_id)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':name', $postParams['name']);
        $statement->bindParam(':parent_category_id', $postParams['parent_category_id']);
        
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
        //Проверка parent_category_id
        if (array_key_exists('parent_category_id', $putParams)) {
            // Если такого id не существует, то ошибка
            if ($this->isValidParentCategoryId($putParams['parent_category_id']) === false) {
                $result = [
                    'status' => false,
                    'errorInfo' => 'Invalid parent_category_id'
                ];

                return $result;
            }
        }

        $query = "UPDATE categories SET
            name = :name, parent_category_id = :parent_category_id
            WHERE category_id = :category_id";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':name', $putParams['name']);
        $statement->bindParam(':parent_category_id', $putParams['parent_category_id']);
        $statement->bindParam(':category_id', $id);
        
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
        $query = "DELETE FROM categories WHERE category_id = :category_id";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(':category_id', $id);
        
        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    private function isValidParentCategoryId(int $id): bool
    {
        $category = $this->isGetElementCompleted($id);

        if ($category['rowCount'] === 0) {
            return false;
        } else {
            return true;
        }
    }
}
