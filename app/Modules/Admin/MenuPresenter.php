<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Model\MenuFacade;
use App\Model\UserFacade;
use Nette\Application\UI\Form;
use Contributte\Datagrid\Column\Action\Confirmation\StringConfirmation;
use Contributte\Datagrid\Datagrid;
use Nette\Http\FileUpload;
use Nette\Utils\Html;
use Tracy\ILogger;

final class MenuPresenter extends AdminPresenter {

    public function handleDeleteMenu(int $id): void {
        $menuItem = $this->menuFacade->get($id);
        if (!$menuItem) {
            $this->error('Položka nenalezena.');
        }

        $this->menuFacade->delete($id);
        $this->deleteUnusedFile($menuItem->file_name);
        $this->logger->log($this->getUserEmail() . ' deleted menu item ' . $menuItem->button_name, ILogger::INFO);
        $this->flashMessage('Položka úspěšně odebrána.', 'alert-success');
        $this->redirect('this');
    }

    public function updateVisible($id, $visible): void {
        $this->menuFacade->edit((int)$id, ['visible' => $visible]);
        $this['menuGrid']->reload();
    }

    public function createComponentMenuGrid(): Datagrid {
        $grid = new DataGrid();

        $grid->setDataSource($this->menuFacade->findBy());
        $grid->setItemsPerPageList([10, 20, 50], true);
        $grid->setDefaultPerPage(20);

        $grid->addColumnText('id', 'ID');

        $grid->addColumnText('button_name', 'Název tlačítka');

        $grid->addColumnText('file_name', 'Soubor')
            ->setRenderer(function ($item) {
                return $item->file_name
                    ? Html::el('a')
                        ->href($this->getHttpRequest()->getUrl()->getBasePath() . 'uploads/' . $item->file_name)
                        ->setText('Otevřít PDF')
                        ->setAttribute('target', '_blank')
                    : 'Žádný soubor';
            })
            ->addCellAttributes(['class' => 'text-center']);

        $grid->addColumnNumber('order', 'Pořadí')
            ->addCellAttributes(['class' => 'text-center']);

        $gridVisible = $grid->addColumnStatus('visible', 'Viditelnost')
            ->addCellAttributes(['class' => 'text-center']);
        $gridVisible
            ->addOption(1, 'Ano')
            ->setIcon('check')
            ->setClass('btn-success')
            ->endOption()
            ->addOption(0, 'Ne')
            ->setIcon('times')
            ->setClass('btn-danger')
            ->endOption();

        $gridVisible
            ->onChange[] = [$this, 'updateVisible'];

        $grid->addAction('editMenu', '')
            ->setIcon('pencil')
            ->setClass('btn-sm btn-primary');

        $grid->addAction('deleteMenu!', '')
            ->setIcon('trash')
            ->setClass('btn-sm btn-danger')
            ->setConfirmation(
                new StringConfirmation('Opravdu chcete odebrat?')
            );

        return $grid;
    }

    public function createComponentMenuForm(): Form {
        $form = new Form();
        $form->getElementPrototype()->enctype = 'multipart/form-data';

        $form->addHidden('id');

        $form->addText('button_name', 'Název tlačítka:')
            ->setRequired('Zadejte název tlačítka.');

        $form->addUpload('file', 'Soubor (PDF):')
            ->setRequired(false)
            ->addRule(Form::MIME_TYPE, 'Musíte nahrát PDF soubor.', ['application/pdf']);

        $form->addInteger('order', 'Pořadí:')
            ->setRequired('Zadejte pořadí.')
            ->setDefaultValue(1);

        $form->addSelect('visible', 'Viditelnost:', [
            1 => 'Ano',
            0 => 'Ne'
        ])->setDefaultValue(1);

        $form->addSubmit('save', 'Uložit');

        $form->onSuccess[] = [$this, 'processMenuForm'];

        return $form;
    }

    public function processMenuForm(Form $form, array $values): void {
        $uploadDir = $this->uploadDir;
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
            }
        }

        /** @var FileUpload $file */
        $file = $values['file'];

        if ($file->isOk() && $file->getContentType() === 'application/pdf') {
            $originalFileName = $file->getSanitizedName();
            $filePath = $uploadDir . $originalFileName;

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $file->move($filePath);

            $values['file_name'] = $originalFileName;
        } else {
            unset($values['file']);
        }

        if (!empty($values['id'])) {
            $menuItem = $this->menuFacade->get((int)$values['id']);
            $oldFileName = $menuItem?->file_name;

            if (!isset($values['file_name'])) {
                $values['file_name'] = $oldFileName;
            }

            $this->menuFacade->edit((int)$values['id'], [
                'button_name' => $values['button_name'],
                'file_name' => $values['file_name'],
                'order' => $values['order'],
                'visible' => $values['visible'],
            ]);

            if ($oldFileName !== $values['file_name']) {
                $this->deleteUnusedFile($oldFileName);
            }

            $this->flashMessage('Položka menu byla upravena.', 'alert-success');
        } else {
            $this->menuFacade->create([
                'button_name' => $values['button_name'],
                'file_name' => $values['file_name'] ?? '',
                'file_path' => '',
                'order' => $values['order'],
                'visible' => $values['visible']
            ]);

            $this->flashMessage('Nová položka menu byla vytvořena.', 'alert-success');
        }

        $this->redirect('Menu:default');
    }

    /**
     * Deletes the file from disk if no menu item references it anymore.
     */
    private function deleteUnusedFile(?string $fileName): void {
        if (!$fileName || $this->menuFacade->findBy(['file_name' => $fileName])) {
            return;
        }

        $filePath = $this->uploadDir . basename($fileName);

        if (is_file($filePath) && !unlink($filePath)) {
            $this->logger->log('Failed to delete file ' . $filePath, ILogger::WARNING);
        }
    }

    public function actionEditMenu(?int $id = null): void {
        if ($id) {
            $menuItem = $this->menuFacade->get($id);

            if (!$menuItem) {
                $this->error('Položka nenalezena.');
            }

            $this->template->id = $id;

            $this['menuForm']->setDefaults([
                'id' => $menuItem->id,
                'button_name' => $menuItem->button_name,
                'file_name' => $menuItem->file_name,
                'order' => $menuItem->order,
                'visible' => $menuItem->visible
            ]);
        }
    }
}