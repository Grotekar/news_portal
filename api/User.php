<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
 */
class User
{
    private PDO $pdo;
    private string $tableName = 'users';
    private LoggerInterface $logger;
    
    /**
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->logger = new Logger();
        $this->pdo = $pdo;
    }

    /**
     * Обработка GET-запроса.
     *
     * @return string
     */
    public function getRequest(): string
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: access");
        header("Access-Control-Allow-Methods: GET");
        header("Access-Control-Allow-Credentials: true");
        header("Content-Type: application/json");

        $response = [];

        if ($this->getId() > 0) {
            $response = $this->getUser($this->getId());
        } else {
            $response = $this->getUsers();
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getUsers(): string
    {
        $response = [];

        // Запрос к таблице
        $query = "SELECT * FROM users";
        $statment = $this->pdo->prepare($query);
        $isSuccess = $statment->execute();

        if ($isSuccess === false) {
            $this->logger->error(
                "The {tableNmae} table is empty or bad request.\n / 
                Таблица {tableName} пуста, либо запрос не верен. Сообщение от PDO: {errorInfo}.",
                ['tableName' => $this->tableName, 'errorInfo' => $statment->errorInfo()[2]]
            );
            
            http_response_code(400);
            $response = json_encode([
                "message_error" => "Bad request. / Запрос не верен.",
                "message_PDO" => $statment->errorInfo()[2]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $this->logger->info(' The request was completed successfully. / Запрос выполнен успешно.');
            // Фиксация полученных данных
            http_response_code(200);
            $response = json_encode($statment->fetchAll(\PDO::FETCH_ASSOC));
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getUser(int $userId)
    {
        $response = [];
        
        // Запрос к таблице
        $query = "SELECT * FROM users WHERE user_id=(:user_id)";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(":user_id", $userId);
        $isSuccess = $statment->execute();
        
        if ($isSuccess === false) {
            $this->logger->info(
                "Not found.\n / Не найдено. Сообщение от PDO: {errorInfo}.",
                ['errorInfo' => $statment->errorInfo()[2]]
            );
            
            http_response_code(404);
            $response = json_encode([
                "message_error" => "Not found./ Не найдено.",
                "message_PDO" => $statment->errorInfo()[2]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $this->logger->info(' The request was completed successfully. / Запрос выполнен успешно.');
            // Фиксация полученных данных
            http_response_code(200);
            $response = json_encode($statment->fetch(\PDO::FETCH_ASSOC));
        }

        return $response;
    }

    /**
     * Обработка POST-запроса.
     *
     * @return string
     */
    public function createUser()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers:
                Content-Type,
                Access-Control-Allow-Headers,
                Authorization,
                X-Requested-With"
        );
        
        $response = [];

        if (
            array_key_exists('firstname', $_POST) &&
            array_key_exists('lastname', $_POST) &&
            array_key_exists('avatar', $_POST) &&
            array_key_exists('is_admin', $_POST)
        ) {
            // Запрос к таблице
            $query = "INSERT INTO users (firstname, lastname, avatar, is_admin) 
                    VALUES (:firstname, :lastname, :avatar, :is_admin)";
            $statment = $this->pdo->prepare($query);
            $statment->bindParam(':firstname', $_POST['firstname']);
            $statment->bindParam(':lastname', $_POST['lastname']);
            $statment->bindParam(':avatar', $_POST['avatar']);
            $statment->bindParam(':is_admin', $_POST['is_admin']);
            $isSuccess = $statment->execute();
            
            if ($isSuccess === false) {
                $this->logger->error(
                    'Bad request. / 
                    Не удалось выполнить запрос на добавление записи в таблицу {tableName}. 
                    Сообщение от PDO: {errorInfo}.',
                    ['tableName' => $this->tableName, 'errorInfo' => $statment->errorInfo()[2]]
                );
                
                http_response_code(400);
                $response = json_encode([
                    "message_error" => "Bad request. / Не удалось выполнить запрос на добавление записи в таблицу.",
                    "message_PDO" => $statment->errorInfo()[2]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(201);
                $response = json_encode([
                    "message_success" => "The request was completed successfully. / 
                    Добавление записи в таблицу выполнено успешно."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            $response = json_encode([
                "message_error" => "Bad request or not enough data. / 
                Не удалось выполнить запрос на добавление записи в таблицу. Неверные данные, либо их недостаточно."
            ], JSON_UNESCAPED_UNICODE);
        }
        return $response;
    }

    /**
     * Обработка PUT-запроса
     * @param array $putParam
     *
     * @return string
     */
    public function update($putParam): string
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: PUT");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers:
                Content-Type,
                Access-Control-Allow-Headers,
                Authorization,
                X-Requested-With"
        );

        // Получить идентификатор
        $userId = $this->getId();
        $response = [];

        if (
            array_key_exists('firstname', $putParam) &&
            array_key_exists('lastname', $putParam) &&
            array_key_exists('avatar', $putParam) &&
            array_key_exists('is_admin', $putParam)
        ) {
            // Запрос к таблице
            $query = "UPDATE users SET
                firstname = :firstname, lastname = :lastname, avatar = :avatar, is_admin = :is_admin
                WHERE user_id = :user_id";
            $statment = $this->pdo->prepare($query);
            $statment->bindParam(':firstname', $putParam['firstname']);
            $statment->bindParam(':lastname', $putParam['lastname']);
            $statment->bindParam(':avatar', $putParam['avatar']);
            $statment->bindParam(':is_admin', $putParam['is_admin']);
            $statment->bindParam(':user_id', $userId);
            $isSuccess = $statment->execute();
            
            if ($isSuccess === false) {
                $this->logger->error(
                    'Bad request. / 
                    Не удалось выполнить запрос на обновление записи в таблице {tableName}. 
                    Сообщение от PDO: {errorInfo}.',
                    ['tableName' => $this->tableName, 'errorInfo' => $statment->errorInfo()[2]]
                );
                
                http_response_code(304);
                $response = json_encode([
                    "message_error" => "Not modified. / Не удалось выполнить запрос на обновление записи в таблице.",
                    "message_PDO" => $statment->errorInfo()[2]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(200);
                $response = json_encode([
                    "message_success" => "The request was completed successfully. / 
                    Обновление записи в таблице выполнено успешно."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            $response = json_encode([
                "message_error" => "Bad request or not enough data. / 
                Не удалось выполнить запрос на обновление записи в таблице. Неверные данные, либо их недостаточно."
            ], JSON_UNESCAPED_UNICODE);
        }
        return $response;
    }

    /**
     * Обработка DELETE-запроса
     * @param array $deleteParam
     *
     * @return string
     */
    public function delete($deleteParam): string
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: DELETE");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers: 
                Content-Type,
                Access-Control-Allow-Headers,
                Authorization,
                X-Requested-With"
        );

        // Получить идентификатор
        $userId = $this->getId();
        $response = [];

        if ($userId > 0) {
            // Запрос к таблице
            $query = "DELETE FROM users WHERE user_id = :user_id";
            $statment = $this->pdo->prepare($query);
            $statment->bindParam(':user_id', $userId);
            $isSuccess = $statment->execute();

            if ($isSuccess === false) {
                $this->logger->error(
                    'Bad request. / 
                    Не удалось выполнить запрос на удаление записи из таблицы {tableName}. 
                    Сообщение от PDO: {errorInfo}.',
                    ['tableName' => $this->tableName, 'errorInfo' => $statment->errorInfo()[2]]
                );
                
                http_response_code(204);
                $response = json_encode([
                    "message_error" => "No Content. / Не удалось выполнить запрос на удаление записи из таблицы.",
                    "message_PDO" => $statment->errorInfo()[2]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(200);
                $response = json_encode([
                    "message_success" => "The request was completed successfully. 
                    / Удаление записи из таблицы выполнено успешно."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            $response = json_encode([
                "message_error" => "Bad request or not enough data. / 
                Не удалось выполнить запрос на обновление записи в таблице. Неверные данные, либо их недостаточно."
            ], JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }

    /**
     * @return int
     */
    private function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 11);
    }
}
