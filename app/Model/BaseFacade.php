<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

abstract class BaseFacade {

    protected Explorer $db;

    public function __construct(Explorer $db) {
        $this->db = $db;
    }

    abstract protected function getTableName(): string;

    public function get(int $id): ?ActiveRow {
        return $this->db->table($this->getTableName())->get($id);
    }

    public function findBy(array $conditions = []): array {
        return $this->db->table($this->getTableName())->where($conditions)->fetchAll();
    }

    public function edit(int $id, array $values): void {
        $record = $this->get($id);

        if ($record) {
            $record->update($values);
        }
    }

    public function delete(int $id): void {
        $record = $this->get($id);

        if ($record) {
            $record->delete();
        }
    }

    public function create(array $values) {
        return $this->db->table($this->getTableName())->insert($values);
    }

}
