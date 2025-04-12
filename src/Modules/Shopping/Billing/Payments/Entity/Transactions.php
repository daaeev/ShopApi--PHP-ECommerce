<?php

namespace Project\Modules\Shopping\Billing\Payments\Entity;

class Transactions
{
    public function __construct(
        private array $transactions = [],
    ) {}

    public function __clone(): void
    {
        $transactions = [];
        foreach ($this->transactions as $transaction) {
            $transactions[] = clone $transaction;
        }

        $this->transactions = $transactions;
    }

    public function add(Transaction $transaction): void
    {
        if ($this->contains($transaction)) {
            throw new \DomainException('Same transaction already exists');
        }

        $this->transactions[] = $transaction;
    }

    public function contains(Transaction $transaction): bool
    {
        foreach ($this->transactions as $currentTransaction) {
            if ($transaction->equalsTo($currentTransaction)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Transaction[]
     */
    public function all(): array
    {
        return $this->transactions;
    }

    public function last(): ?Transaction
    {
        if (empty($this->transactions)) {
            return null;
        }

        return $this->transactions[array_key_last($this->transactions)];
    }
}