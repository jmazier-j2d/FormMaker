<?php

use Grafite\FormMaker\Fields\Text;
use Grafite\FormMaker\Fields\Email;
use Illuminate\Support\Facades\Route;
use Grafite\FormMaker\Forms\ModelForm;

class UserForm extends ModelForm
{
    public $model = User::class;

    public $routePrefix = 'users';

    public $buttons = [
        'save' => 'Save'
    ];

    public function fields()
    {
        return [
            Text::make('name'),
            Email::make('email'),
        ];
    }
}

class FormModelTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->session([
            'token' => 'tester',
        ]);

        Route::post('users')->name('users');
        Route::get('users')->name('users.index');
        Route::put('users/{id}')->name('users.update');
        Route::delete('users/{id}')->name('users.destroy');

        $this->form = app(UserForm::class);
    }

    public function testCreate()
    {
        $form = $this->form->create();

        $this->assertStringContainsString('http://localhost/users', $form);
        $this->assertStringContainsString('method="POST"', $form);

        $this->assertStringContainsString('<div class="form-group"><label class="control-label" for="Name">Name</label><input  class="form-control" id="Name" name="name" type="text" value=""></div>', $form);
        $this->assertStringContainsString('<div class="form-group"><label class="control-label" for="Email">Email</label><input  class="form-control" id="Email" name="email" type="email" value=""></div>', $form);
    }

    public function testUpdate()
    {
        $user = new User();

        $form = $this->form->edit($user);

        $this->assertStringContainsString('http://localhost/users/3', $form);
        $this->assertStringContainsString('PUT', $form);
    }

    public function testDelete()
    {
        $user = new User();

        $form = $this->form->delete($user);

        $this->assertStringContainsString('http://localhost/users/3', $form);
        $this->assertStringContainsString('DELETE', $form);
    }
}