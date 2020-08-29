<?php

namespace Api;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
 */
abstract class AbstractTable implements TableInterface
{
    
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

        // Если нужно получить по id, либо всё
        if ($this->getId() > 0) {
            $response = $this->getElement($this->getId());
        } else {
            $response = $this->getAll();
        }

        return $response;
    }

    /**
     * @return string
     */
    private function getAll(): string
    {
        $response = [];

        // Запрос к таблице
        $statment = $this->getStatmentForAllElements();

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
     * Запрос для получения всех элементов
     *
     * @return object
     */
    abstract protected function getStatmentForAllElements(): object;

    /**
     * @param int $id
     *
     * @return string
     */
    private function getElement(int $id): string
    {
        $response = [];
        
        // Запрос к таблице
        $statment = $this->getStatmentForGetElement($id);
        
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
     * Запрос для получения элемента
     *
     * @return object
     */
    abstract protected function getStatmentForGetElement(int $id): object;

    /**
     * Обработка POST-запроса.
     *
     * @return string
     */
    public function createElement(): string
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers:Content-Type,Access-Control-Allow-Headers,Authorization,X-Requested-With"
        );
        
        $response = [];

        // Проверка переданных переменных
        if ($this->isExistsParamsInArray($_POST)) {
            // Запрос к таблице
            $statment = $this->getStatmentForCreateElement();

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
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    abstract protected function isExistsParamsInArray(array $params): bool;

    /**
     * Запрос на создание элемента
     *
     * @return object
     */
    abstract protected function getStatmentForCreateElement(): object;

    /**
     * Обработка PUT-запроса
     * @param array $putParams
     *
     * @return string
     */
    public function updateElement($putParams): string
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: PUT");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers:Content-Type,Access-Control-Allow-Headers,Authorization,X-Requested-With"
        );

        // Получить идентификатор
        $id = $this->getId();
        $response = [];

        if ($this->isExistsParamsInArray($putParams)) {
            // Запрос к таблице
            $statment = $this->getStatmentForUpdateElement($id, $putParams);

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
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return object
     */
    abstract protected function getStatmentForUpdateElement(int $id, array $putParams): object;

    /**
     * Обработка DELETE-запроса
     *
     * @return string
     */
    public function deleteElement(): string
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: DELETE");
        header("Access-Control-Max-Age: 3600");
        header(
            "Access-Control-Allow-Headers:Content-Type,Access-Control-Allow-Headers,Authorization,X-Requested-With"
        );

        // Получить идентификатор
        $id = $this->getId();
        $response = [];

        if ($id > 0) {
            // Запрос к таблице
            $statment = $this->getStatmentForDeleteElement($id);

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
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return object
     */
    abstract protected function getStatmentForDeleteElement(int $id): object;

    /**
     * @return int
     */
    abstract protected function getId(): int;
}
