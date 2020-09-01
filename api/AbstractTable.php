<?php

namespace Api;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
 */
abstract class AbstractTable implements TableInterface
{
    protected $response;
    protected $statment;

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
     * @return string
     */
    //abstract public function processingGetRequest(): string;

    /**
     * Обработка GET-запроса.
     *
     * @return bool
     */
    public function isGetRequestSuccess(): bool
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: access");
        header("Access-Control-Allow-Methods: GET");
        header("Access-Control-Allow-Credentials: true");
        header("Content-Type: application/json");

        $status = false;

        // Если нужно получить по id, либо всё
        if ($this->getId() > 0) {
            $status = $this->isGetElement($this->getId());
        } else {
            $status = $this->isGetAll();
        }

        return $status;
    }

    /**
     * @return bool
     */
    public function isGetAll(): bool
    {
        // Результат запроса к таблице
        $status = $this->isGetAllComplited();

        if ($status === false) {
            $this->logger->error(
                "The {tableName} table is empty or bad request.\n / 
                Таблица {tableName} пуста, либо запрос не верен. Сообщение от PDO: {errorInfo}.",
                ['tableName' => $this->tableName, 'errorInfo' => $this->statment->errorInfo()[2]]
            );
            
            http_response_code(400);
            $this->response = json_encode([
                "message_error" => "Bad request. / Запрос не верен.",
                "message_PDO" => $this->statment->errorInfo()[2]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $this->logger->info('The request was completed successfully. / Запрос выполнен успешно.');
            // Фиксация полученных данных
            http_response_code(200);
            $this->response = json_encode($this->statment->fetchAll(\PDO::FETCH_ASSOC));
        }

        return $status;
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    abstract protected function isGetAllComplited(): bool;

    /**
     * @param int $id
     *
     * @return bool
     */
    public function isGetElement(int $id): bool
    {
        // Результат запроса к таблице        
        $status = $this->isGetElementComplited($id);
        
        if ($status === false) {
            $this->logger->info(
                "Not found.\n / Не найдено. Сообщение от PDO: {errorInfo}.",
                ['errorInfo' => $this->statment->errorInfo()[2]]
            );
            
            http_response_code(404);
            $this->response = json_encode([
                "message_error" => "Not found./ Не найдено.",
                "message_PDO" => $this->statment->errorInfo()[2]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $this->logger->info(' The request was completed successfully. / Запрос выполнен успешно.');
            // Фиксация полученных данных
            http_response_code(200);
            $this->response = json_encode($this->statment->fetch(\PDO::FETCH_ASSOC));
        }

        return $status;
    }

     /**
     * Запрос для получения элемента
     *
     * @return bool
     */
    abstract public function isGetElementComplited(int $id): bool;

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

        // Проверка переданных переменных
        if ($this->isExistsParamsInArray($_POST)) {
            // Результат запроса к таблице
            $status = $this->isCreateElementCompleted($_POST);
            
            if ($status === false) {
                $this->logger->error(
                    'Bad request. / 
                    Не удалось выполнить запрос на добавление записи в таблицу {tableName}. 
                    Сообщение от PDO: {errorInfo}.',
                    ['tableName' => $this->tableName, 'errorInfo' => $this->statment->errorInfo()[2]]
                );
                
                http_response_code(400);
                $this->response = json_encode([
                    "message_error" => "Bad request. / Не удалось выполнить запрос на добавление записи в таблицу.",
                    "message_PDO" => $this->statment->errorInfo()[2]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(201);
                $this->response = json_encode([
                    "message_success" => "The request was completed successfully. / 
                    Добавление записи в таблицу выполнено успешно."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            $this->response = json_encode([
                "message_error" => "Bad request or not enough data. / 
                Не удалось выполнить запрос на добавление записи в таблицу. Неверные данные, либо их недостаточно."
            ], JSON_UNESCAPED_UNICODE);
        }

        return $status;
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
     * @param array $postParams
     *
     * @return bool
     */
    abstract public function isCreateElementCompleted(array $postParams): bool;

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

        if ($this->isExistsParamsInArray($putParams)) {
            // Запрос к таблице
            $status = $this->isUpdateElementCompleted($id, $putParams);
                        
            if ($status === false) {
                $this->logger->error(
                    'Bad request. / 
                    Не удалось выполнить запрос на обновление записи в таблице {tableName}. 
                    Сообщение от PDO: {errorInfo}.',
                    ['tableName' => $this->tableName, 'errorInfo' => $this->statment->errorInfo()[2]]
                );
                
                http_response_code(304);
                $this->response = json_encode([
                    "message_error" => "Not modified. / Не удалось выполнить запрос на обновление записи в таблице.",
                    "message_PDO" => $this->statment->errorInfo()[2]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(200);
                $this->response = json_encode([
                    "message_success" => "The request was completed successfully. / 
                    Обновление записи в таблице выполнено успешно."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            $this->response = json_encode([
                "message_error" => "Bad request or not enough data. / 
                Не удалось выполнить запрос на обновление записи в таблице. Неверные данные, либо их недостаточно."
            ], JSON_UNESCAPED_UNICODE);
        }

        return $this->response;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return bool
     */
    abstract public function isUpdateElementCompleted(int $id, array $putParams): bool;

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

        if ($id > 0) {
            // Запрос к таблице
            $status = $this->isDeleteteElementCompleted($id);

            if ($isSuccess === false) {
                $this->logger->error(
                    'Bad request. / 
                    Не удалось выполнить запрос на удаление записи из таблицы {tableName}. 
                    Сообщение от PDO: {errorInfo}.',
                    ['tableName' => $this->tableName, 'errorInfo' => $this->statment->errorInfo()[2]]
                );
                
                http_response_code(204);
                $this->response = json_encode([
                    "message_error" => "No Content. / Не удалось выполнить запрос на удаление записи из таблицы.",
                    "message_PDO" => $this->statment->errorInfo()[2]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(200);
                $this->response = json_encode([
                    "message_success" => "The request was completed successfully. 
                    / Удаление записи из таблицы выполнено успешно."
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            http_response_code(400);
            $this->response = json_encode([
                "message_error" => "Bad request or not enough data. / 
                Не удалось выполнить запрос на обновление записи в таблице. Неверные данные, либо их недостаточно."
            ], JSON_UNESCAPED_UNICODE);
        }

        return $this->response;
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return bool
     */
    abstract public function isDeleteteElementCompleted(int $id): bool;

    /**
     * @return int
     */
    abstract protected function getId(): int;
}
