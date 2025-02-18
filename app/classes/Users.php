<?php

namespace app\classes;

class Users
{
    private $db;

    public function __construct()
    {

        $this->db = Database::getInstance();
    }

    public function createUser($tgId)
    {
        $stmt = $this->db->prepare("INSERT INTO users (telegram_id,username, balance) 
                                            VALUES (:telegram_id,:username ,:balance)");
        $stmt->execute(['telegram_id' => $tgId, 'username' => $tgId, 'balance' => $balance = 0.00]);

    }

    public function getUserByTelegramId($tgId)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE telegram_id = :telegram_id");
        $stmt->execute(['telegram_id' => $tgId]);
        return $stmt->fetch();
    }
    public function setUserProcessingStatus($chatId, $status)
    {
        $stmt = $this->db->prepare ("UPDATE users SET processing = :status WHERE telegram_id = :chatId");
        $stmt->execute(['status' => $status ? 1 : 0, 'chatId' => $chatId]);

    }

    public function transactionExists($transactionId)
    {
        $stmt = $this->db->prepare("SELECT id FROM transactions WHERE transaction_id = :transaction_id LIMIT 1");
        $stmt->execute(['transaction_id' => $transactionId]);
        return $stmt->fetch() !== false;
    }

    public function saveTransaction( $transactionId, $chatId, $amount, $status)
    {
        $stmt = $this->db->prepare("
        INSERT INTO transactions ( transaction_id, telegram_id, amount, status) 
        VALUES (:transaction_id, :telegram_id, :amount, :status)
    ");
        $stmt->execute([

            'transaction_id' => $transactionId,
            'telegram_id'    => $chatId,
            'amount'         => $amount,
            'status'         => $status
        ]);
    }

// Обновляет статус транзакции
    public function updateTransactionStatus($transactionId, $status)
    {
        $stmt = $this->db->prepare("UPDATE transactions SET status = :status WHERE transaction_id = :transaction_id");
        $stmt->execute(['status' => $status, 'transaction_id' => $transactionId]);
    }


    public function updateUserBalance($tgId, $newBalance)
    {
        //старт
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE users SET balance = :balance WHERE telegram_id = :telegram_id");
            $stmt->execute(['telegram_id' => $tgId, 'balance' => $newBalance]);

            // Фикс
            $this->db->commit();
        } catch (\Exception $e) {
            // откат если ошибка
            $this->db->rollBack();
            throw $e;
        }
    }


}