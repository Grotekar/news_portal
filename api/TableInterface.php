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
     * @return string
     */
    public function processingGetRequest(): string;

    /**
     * Обработка POST-запроса.
     *
     * @return string
     */
    public function createElement(): string;

    /**
     * Обработка PUT-запроса
     *
     * @param array $putParams
     *
     * @return string
     */
    public function updateElement($putParams): string;

    /**
     * Обработка DELETE-запроса
     *
     * @return string
     */
    public function deleteElement(): string;
}
