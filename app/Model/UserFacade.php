<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Security\Authenticator;
use Nette\Security\SimpleIdentity;
use Nette\Security\AuthenticationException;
use App\Model\Exception\DuplicateNameException;

final class UserFacade extends BaseFacade implements Authenticator {

    private Passwords $passwords;

    public function __construct(Nette\Database\Explorer $db, Passwords $passwords) {
        parent::__construct($db);
        $this->passwords = $passwords;
    }

    protected function getTableName(): string {
        return 'user';
    }

    public function authenticate(string $email, string $password): SimpleIdentity {
        $row = $this->db->table('user')
            ->where('email', $email)
            ->fetch();

        if (!$row) {
            throw new AuthenticationException('Uživatel nenalezen.');
        }

        if (!$this->passwords->verify($password, $row->password)) {
            throw new AuthenticationException('Neplatné heslo.');
        }

        return new SimpleIdentity(
            $row->id,
            $row->role,
            [
                'email' => $row->email,
                'name' => $row->name,
                'surname' => $row->surname,
            ]
        );
    }

    public function addUser(string $email, string $password, string $name, string $surname): void {
        try {
            $this->db->table($this->getTableName())->insert([
                'email' => $email,
                'name' => $name,
                'surname' => $surname,
                'password' => $this->passwords->hash($password),
                'role' => 'editor',
            ]);
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateNameException('Uživatel s tímto e-mailem již existuje.');
        }
    }

    public function editUser(int $id, array $values): void {
        if (empty($values['password'])) {
            unset($values['password']);
        } else {
            $values['password'] = $this->passwords->hash($values['password']);
        }

        try {
            $this->edit($id, $values);
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            throw new DuplicateNameException('Uživatel s tímto e-mailem již existuje.');
        }
    }
}
