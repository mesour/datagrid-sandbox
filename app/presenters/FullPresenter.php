<?php

namespace App\Presenters;

use Mesour\DataGrid\_Grid;
use Mesour\DataGrid\BasicGrid;
use Nette,
    App\Model,
    \Mesour\DataGrid\Grid,
    \Mesour\DataGrid\NetteDbDataSource,
    \Mesour\DataGrid\Components\Link,
    \Mesour\DataGrid\DibiDataSource;


/**
 * Basic presenter.
 */
class FullPresenter extends BasePresenter {

	private function getSubGrid() {
		$_sub_grid = new BasicGrid;

		$_sub_grid->setPrimaryKey('id');

		$_sub_grid->enablePager(5);

		$_sub_grid->enableEditableCells();

		$_sub_grid->onEditCell[] = $this->editCell;

		$_sub_grid->enableSorting();

		$_sub_grid->onSort[] = $this->editSort;

		$_sub_grid->addText('description');

		$_sub_grid->addText('name');

		$selection = $_sub_grid->enableRowSelection();

		$selection->addLink('Active')
		    ->onCall[] = $this->activeSelected;

		$selection->addLink('Unactive')
		    ->setAjax(FALSE)
		    ->onCall[] = $this->unactiveSelected;

		$selection->addLink('Delete')
		    ->setConfirm('Really delete all selected users?')
		    ->onCall[] = $this->deleteSelected;

		return $_sub_grid;
	}

	public function createTestForm(Nette\ComponentModel\IContainer $parent , $name) {
		$form = new Nette\Application\UI\Form($parent, $name);

		$form->addText('name');

		$form->addHidden('primary_key');

		$form->addSubmit('send', 'Save');

		$form->onSuccess[] = $this->saveForm;

		return $form;
	}

	public function saveForm(Nette\Application\UI\Form $form) {
		$values = $form->getValues();
		print_r($values);
	}

 	protected function createComponentFullDataGrid($name) {
		$source = new NetteDbDataSource($this->demo_model->getUserSelection());

		$grid = new Grid($this, $name);

		$table_id = 'user_id';

		$grid->setPrimaryKey($table_id);

		$grid->setDataSource($source);

		$grid->enablePager(5);

		$subItems = $grid->enableSubItems();

		$subItems->addGridItem('groups', 'User groups', $this->getSubGrid())
			->setCallback(function(BasicGrid $sub_grid, $data) {
				$source = new NetteDbDataSource($this->demo_model->getUserGroupsSelection($data['user_id']));

				$sub_grid->setDataSource($source);
			});

		$subItems->addTemplateItem('description', 'Long description', __DIR__ . '/../templates/Full/_sub_item.latte', 'test')
			->setCallback(function(Nette\Application\UI\ITemplate $template, $data) {
				$template->name = $data['name'];
			});

		$subItems->addComponentItem('form', 'Test form', callback($this, 'createTestForm'))
			->setCallback(function(Nette\Application\UI\Form $form, $data) {
				$data['primary_key'] = $data['user_id'];
				$form->setDefaults($data);
			});

		$subItems->addCallbackItem('callback', 'User info', function($data) {
			return $data['name'] . ' ' . $data['surname'];
		});

		$grid->enableEditableCells();

		$grid->enableFilter();

		$grid->enableExport(__DIR__ . '/../../temp/cache');

		$grid->enableSorting();

		$selection = $grid->enableRowSelection();

		$selection->addLink('Active')
		    ->onCall[] = $this->activeSelected;

		$selection->addLink('Unactive')
		    ->setAjax(FALSE)
		    ->onCall[] = $this->unactiveSelected;

		$selection->addLink('Delete')
		    ->setConfirm('Really delete all selected users?')
		    ->onCall[] = $this->deleteSelected;

		$grid->onEditCell[] = $this->editCell;

		$grid->onSort[] = $this->editSort;

		$status_column = $grid->addStatus('action', 'S');

		$status_column->addButton()
		    ->setStatus('0') //! show if status == 0
		    ->setType('btn-danger')
		    ->setClassName('ajax')
		    ->setIcon('glyphicon-ban-circle')
		    ->setTitle('Set as active (unactive)')
		    ->addAttribute('href', new Link(array(
			Link::HREF => 'changeStatusUser!',
			Link::PARAMS => array(
			    'id' => '{' . $table_id . '}',
			    'status' => 1
			)
		    )));

		$status_column->addButton()
		    ->setStatus('1') //! show if status == 1
		    ->setType('btn-success')
		    ->setClassName('ajax')
		    ->setIcon('glyphicon-ok-circle')
		    ->setTitle('Set as unactive (active)')
		    ->addAttribute('href', new Link(array(
			Link::HREF => 'changeStatusUser!',
			Link::PARAMS => array(
			    'id' => '{' . $table_id . '}',
			    'status' => 0
			)
		    )));

		$grid->addImage('avatar')
		    ->setMaxHeight(80)
		    ->setMaxWidth(80);

		$grid->addTemplate('name', 'Template')
			->setTemplate(__DIR__ . '/../templates/Full/_test.latte')
		    	->setBlock('test')
			->setCallback(function($data, Nette\Application\UI\ITemplate $template) {
				$template->name = $data['name'];
			});

		$grid->addText('name', 'Name');

		$grid->addDate('last_login', 'Last login')
		    ->setFormat('j.n.Y');

		$grid->addNumber('amount', 'Amount')
		    	->setDecimals(2)
		    	->setThousandsSeparator(' ')
		    	->setDecimalPoint(',')
			->setUnit('EUR');

		$container = $grid->addContainer('name', 'Name');

		$container->addText('name')
		    ->addAttribute('class', 'bbb')
		    ->addAttribute('class', 'ccc', TRUE);

		$container->addText('surname');

		$container2 = $grid->addContainer('name', 'Actions')
		    ->setOrdering(FALSE);

		$actions = $container2->addActions('Actions');

		$actions->addDropDown()
		    ->setType('btn-danger')
		    ->addGroup('DropDown header')
		    ->addLink('DataGrid:editUser', 'Test link', array(
			'id' => '{' . $table_id . '}'
		    ))
		    ->addLink('DataGrid:editUser', 'Test link 2', array(
			'id' => '{' . $table_id . '}'
		    ))
		    ->addSeparator()
		    ->addGroup('DropDown header 2')
		    ->addLink('DataGrid:editUser', 'Test link 3', array(
			'id' => '{' . $table_id . '}'
		    ));

		$actions->addButton()
		    ->setType('btn-primary')
		    ->setIcon('glyphicon-pencil')
		    ->setTitle('Edit')
		    ->addAttribute('href', new Link(array(
			Link::HREF => 'DataGrid:editUser',
			Link::PARAMS => array(
			    'id' => '{' . $table_id . '}'
			)
		    )));

		$actions->addButton()
		    ->setType('btn-danger')
		    ->setIcon('glyphicon-trash')
		    ->setConfirm('Realy want to delete user?')
		    ->setTitle('Delete')
		    ->addAttribute('href', new Link(array(
			Link::HREF => 'deleteUser!',
			Link::PARAMS => array(
			    'id' => '{' . $table_id . '}'
			)
		    )));

		return $grid;
	}

}
