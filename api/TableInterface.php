<?php

namespace Api;

/**
 * Интерфейс для базового взаимодействия с таблицами базы данных
 */
interface TableInterface
{
    /**
     * Обработка GET-запроса.
     *
     * @return void
     */
    public function processingGetRequest(): void;

    /**
     * Обработка POST-запроса.
     *
     * @return bool
     */
    public function createElement(): bool;

    /**
     * Обработка PUT-запроса
     *
     * @param array $putParams
     *
     * @return void
     */
    public function updateElement($putParams): void;

    /**
     * Обработка DELETE-запроса
     *
     * @return void
     */
    public function deleteElement(): void;
}
