<?php

declare(strict_types=1);

use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', 'HomeController@index');
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->post('/logout', 'AuthController@logout');
    $router->get('/account', 'AccountController@index');
    $router->post('/account/security', 'AccountController@updateSecurity');

    $router->get('/dashboard', 'DashboardController@index');

    $router->get('/users', 'UsersController@index');
    $router->get('/users/create', 'UsersController@create');
    $router->post('/users/store', 'UsersController@store');
    $router->get('/website-content', 'WebsiteContentController@index');
    $router->post('/website-content/update', 'WebsiteContentController@update');

    $router->get('/students', 'StudentsController@index');
    $router->get('/students/create', 'StudentsController@create');
    $router->post('/students/store', 'StudentsController@store');
    $router->get('/students/{id}/edit', 'StudentsController@edit');
    $router->post('/students/{id}/update', 'StudentsController@update');
    $router->post('/students/{id}/behavior', 'StudentsController@addBehavior');
    $router->post('/students/behavior/{id}/delete', 'StudentsController@deleteBehavior');
    $router->get('/students/{id}/results', 'StudentsController@results');
    $router->get('/students/{id}/certificate', 'StudentsController@certificate');
    $router->get('/students/{id}/appreciation', 'StudentsController@appreciation');
    $router->get('/students/{id}/id-card', 'StudentsController@idCard');
    $router->post('/students/{id}/promote/moteseta', 'StudentsController@promoteToMoteseta');

    $router->get('/teachers', 'TeachersController@index');
    $router->get('/teachers/create', 'TeachersController@create');
    $router->post('/teachers/store', 'TeachersController@store');
    $router->get('/teachers/{id}/edit', 'TeachersController@edit');
    $router->post('/teachers/{id}/update', 'TeachersController@update');
    $router->post('/teachers/{id}/delete', 'TeachersController@destroy');
    $router->post('/teachers/{id}/behavior', 'TeachersController@addBehavior');
    $router->post('/teachers/behavior/{id}/delete', 'TeachersController@deleteBehavior');
    $router->get('/teachers/{id}/appreciation', 'TeachersController@appreciation');

    $router->get('/articles', 'ArticlesController@publicIndex');
    $router->get('/articles/manage', 'ArticlesController@index');
    $router->post('/articles/store', 'ArticlesController@store');
    $router->get('/api/articles/teachers', 'ArticlesController@apiTeachers');

    $router->get('/classes', 'ClassesController@index');
    $router->get('/classes/create', 'ClassesController@create');
    $router->post('/classes/store', 'ClassesController@store');
    $router->get('/classes/{id}/edit', 'ClassesController@edit');
    $router->post('/classes/{id}/update', 'ClassesController@update');
    $router->post('/classes/{id}/delete', 'ClassesController@destroy');
    $router->get('/api/classes/search', 'ClassesController@apiSearch');

    $router->get('/subjects', 'SubjectsController@index');
    $router->get('/subjects/create', 'SubjectsController@create');
    $router->post('/subjects/store', 'SubjectsController@store');
    $router->get('/subjects/{id}/edit', 'SubjectsController@edit');
    $router->post('/subjects/{id}/update', 'SubjectsController@update');
    $router->post('/subjects/{id}/delete', 'SubjectsController@destroy');
    $router->get('/api/subjects/teachers', 'SubjectsController@apiTeachers');

    $router->get('/grades', 'GradesController@index');
    $router->get('/grades/student/{id}/modal-data', 'GradesController@studentModalData');
    $router->post('/grades/store', 'GradesController@store');

    $router->get('/contracts/{teacherId}', 'ContractsController@show');
    $router->post('/contracts/{teacherId}/save', 'ContractsController@save');
};
