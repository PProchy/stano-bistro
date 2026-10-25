<?php

declare(strict_types=1);

namespace App\Model;

final class MenuFacade extends BaseFacade {

    protected function getTableName(): string {
        return 'menu';
    }

    public function getOrderedVisibleMenu(): array {
        return $this->db->table($this->getTableName())->where(['visible' => true])->order('order ASC')->fetchAll();
    }
}
