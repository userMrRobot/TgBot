<?php

namespace app\classes;

use TelegramBot\Api\BotApi;


class Bot
{

    private $telegram;
    private $user;

    public function __construct($token)
    {
        $this->telegram = new BotApi($token);
        $this->user = new Users();

    }

    public function request()
    {
        $offset = null; // Будем начинать с самого первого обновления
        $processedUpdates = []; // Массив для хранения обработанных update_id

        while (true) {
            try {
                // Получаем новые обновления начиная с offset
//                $updates = $this->telegram->getUpdates($offset);
                $updates = $this->telegram->getUpdates(['offset' => $offset, 'timeout' => 10]);

                foreach ($updates as $update) {
                    if (in_array($update->getUpdateId(), $processedUpdates)) {
                        continue; // Если уже обработан, пропускаем
                    }
                    $processedUpdates[] = $update->getUpdateId(); // Добавляем текущий update_id в обработанные

                    $offset = $update->getUpdateId() + 1; // Обновляем offset для следующего запроса

                    $chatId = $update->getMessage()->getChat()->getId();
                    $message = $update->getMessage()->getText();

                    // Если команда /start
                    if ($message === '/start') {
                        $this->sendWelcomeMessage($chatId);
                    }

                    // Обрабатываем числовые сообщения для пополнения или списания

                        $this->processTransaction($chatId, $message);

                }

                sleep(1); // Задержка между запросами
            } catch (\Exception $e) {
                // Логируем ошибку, чтобы знать, что пошло не так
                echo($e->getMessage());
                break; // Прерываем выполнение при возникновении ошибки
            }
        }
    }



    private function processTransaction($chatId, $message)
    {

        // Заменяем запятую на точку
        $message = str_replace(',', '.', $message);

        if ($this->isNumeric($message)) {
            $amount = (float)$message;

            // Генерируем уникальный ID транзакции
            $transactionId = time();

            // Проверяем, есть ли уже эта транзакция
            if ($this->user->transactionExists($transactionId)) {
                $this->telegram->sendMessage($chatId, "Эта транзакция уже обрабатывается!");
                return;
            }

            // Записываем транзакцию в базу со статусом "pending"
            $this->user->saveTransaction($transactionId, $chatId, $amount, 'pending');

            $user = $this->user->getUserByTelegramId($chatId);
            if ($user) {
                $newBalance = $user['balance'] + $amount;

                if ($newBalance < 0) {
                    $this->telegram->sendMessage($chatId, "Ошибка! Недостаточно средств.");
                    $this->user->updateTransactionStatus($transactionId, 'failed');
                } else {
                    $this->user->updateUserBalance($chatId, $newBalance);
                    $this->telegram->sendMessage($chatId, "Ваш новый баланс: $newBalance $");
                    $this->user->updateTransactionStatus($transactionId, 'completed');
                }
            } else {
                $this->telegram->sendMessage($chatId, "Ошибка! Пользователь не найден.");
                $this->user->updateTransactionStatus($transactionId, 'failed');
            }
        } else {
            $this->telegram->sendMessage($chatId, "Ошибка! Введите число.");
        }
    }



    public function sendWelcomeMessage($chatId)
    {
        // Проверяем, есть ли пользователь в базе данных
        $user = $this->user->getUserByTelegramId($chatId);
        if (!$user) {
            // Если пользователя нет, создаем нового с балансом $0.00
            $this->user->createUser($chatId);
            $this->telegram->sendMessage($chatId, "Добро пожаловать! Ваш счёт: $0.00");
        } else {
            $balance = $user['balance'];
            $this->telegram->sendMessage($chatId, "Привет! Ваш текущий баланс: $balance");
        }
    }

    private function isNumeric($message)
    {
        // Проверяем, является ли сообщение числом
        return preg_match('/^[-+]?\d+(\.\d+)?$/', $message);
    }



}