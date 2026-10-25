<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Model\Exception\DuplicateNameException;
use Contributte\Datagrid\Column\Action\Confirmation\StringConfirmation;
use Contributte\Datagrid\Datagrid;
use Nette\Application\UI\Form;
use Tracy\ILogger;

final class UserPresenter extends AdminPresenter {

    public function actionDefault(): void {
        $this->requireAdmin();
    }

    public function handleDelete(int $id): void {
        $this->requireAdmin();

        if ($this->isSelf($id)) {
            $this->flashMessage('Nemůžete smazat sám sebe.', 'alert-danger');
            $this->redirect('this');
        }

        $email = $this->userFacade->get($id)?->email;
        $this->userFacade->delete($id);
        $this->logger->log($this->getUserEmail() . ' deleted user ' . $email, ILogger::INFO);
        $this->flashMessage('Uživatel byl odebrán.', 'alert-success');
        $this->redirect('this');
    }

    public function updateRole($id, $role): void {
        $this->requireAdmin();

        $id = (int)$id;

        if ($this->isSelf($id)) {
            $this->flashMessage('Nemůžete změnit roli sám sobě.', 'alert-danger');
            $this->redirect('this');
        }

        if (!in_array($role, ['admin', 'editor'], true)) {
            $this->error('Neplatná role.');
        }

        $email = $this->userFacade->get($id)?->email;
        $this->userFacade->edit($id, ['role' => $role]);
        $this->logger->log($this->getUserEmail() . ' changed role of ' . $email . ' to ' . $role, ILogger::INFO);
        $this['userGrid']->reload();
    }

    public function createComponentUserGrid(): Datagrid {
        $grid = new Datagrid();

        $grid->setDataSource($this->userFacade->findBy());
        $grid->setItemsPerPageList([10, 20, 50], true);
        $grid->setDefaultPerPage(20);

        $grid->addColumnText('id', 'ID');

        $grid->addColumnText('name', 'Jméno');

        $grid->addColumnText('surname', 'Příjmení');

        $grid->addColumnText('email', 'Email');

        $gridRole = $grid->addColumnStatus('role', 'Role')
            ->addCellAttributes(['class' => 'text-center']);
        $gridRole
            ->addOption('admin', 'Admin')
            ->setClass('btn-success')
            ->endOption()
            ->addOption('editor', 'Editor')
            ->setClass('btn-primary')
            ->endOption();

        $gridRole
            ->setRenderCondition(fn($row) => !$this->isSelf($row->id))
            ->onChange[] = [$this, 'updateRole'];

        $grid->addAction('delete!', '')
            ->setIcon('trash')
            ->setClass('btn-sm btn-danger')
            ->setRenderCondition(fn($row) => !$this->isSelf($row->id))
            ->setConfirmation(
                new StringConfirmation('Opravdu chcete odebrat uživatele %s?', 'email')
            );

        return $grid;
    }

    public function createComponentUserForm(): Form {
        $form = new Form();

        $form->addHidden('id');

        $form->addText('name', 'Jméno:')
            ->setRequired('Zadejte jméno.');

        $form->addText('surname', 'Příjmení:')
            ->setRequired('Zadejte příjmení.');

        $form->addText('email', 'Email:')
            ->setRequired('Zadejte email.')
            ->addRule(Form::Email, 'Zadejte platný email.');

        $password = $form->addPassword('password', 'Heslo:');
        $password
            ->setRequired(!$this->getParameter('id') ? 'Zadejte heslo.' : false)
            ->addCondition(Form::Filled)
            ->addRule(Form::MinLength, 'Heslo musí mít alespoň %d znaků.', 8);

        $form->addPassword('password_confirm', 'Heslo znovu:')
            ->addConditionOn($password, Form::Filled)
            ->setRequired('Zopakujte heslo.')
            ->addRule(Form::Equal, 'Hesla se neshodují.', $password);

        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = [$this, 'processUserForm'];

        return $form;
    }

    public function processUserForm(Form $form, array $values): void {
        $id = $values['id'] ? (int)$values['id'] : null;

        // the hidden id can be tampered with, so check permissions again
        if (!$this->canEdit($id)) {
            $this->error('Nemáte oprávnění.', 403);
        }

        try {
            if ($id) {
                $this->userFacade->editUser($id, [
                    'name' => $values['name'],
                    'surname' => $values['surname'],
                    'email' => $values['email'],
                    'password' => $values['password'],
                ]);
                $this->logger->log($this->getUserEmail() . ' edited own account', ILogger::INFO);
                $this->flashMessage('Účet byl upraven.', 'alert-success');
            } else {
                $this->userFacade->addUser($values['email'], $values['password'], $values['name'], $values['surname']);
                $this->logger->log($this->getUserEmail() . ' created user ' . $values['email'], ILogger::INFO);
                $this->flashMessage('Uživatel byl vytvořen.', 'alert-success');
            }
        } catch (DuplicateNameException $e) {
            $form->addError($e->getMessage());
            return;
        }

        if ($id) {
            $this->redirect('this');
        }
        $this->redirect('User:default');
    }

    public function actionEdit(?int $id = null): void {
        if (!$this->canEdit($id)) {
            $this->flashMessage('Nemáte oprávnění.', 'alert-danger');
            $this->redirect('Admin:default');
        }

        if ($id) {
            $userRow = $this->userFacade->get($id);

            if (!$userRow) {
                $this->error('Uživatel nenalezen.');
            }

            $this->template->id = $id;

            $this['userForm']->setDefaults([
                'id' => $userRow->id,
                'name' => $userRow->name,
                'surname' => $userRow->surname,
                'email' => $userRow->email,
            ]);
        }
    }

    /**
     * Only admin can create new users (no id), everyone can edit only their own account.
     */
    private function canEdit(?int $id): bool {
        return $id === null ? $this->user->isInRole('admin') : $this->isSelf($id);
    }

    private function isSelf(int $id): bool {
        return $id === (int)$this->user->getId();
    }

    private function requireAdmin(): void {
        if (!$this->user->isInRole('admin')) {
            $this->flashMessage('Nemáte oprávnění.', 'alert-danger');
            $this->redirect('Admin:default');
        }
    }
}
