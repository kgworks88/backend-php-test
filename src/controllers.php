<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}'";
        $todos = $app['db']->fetchAll($sql);

        return $app['twig']->render('todos.html', [
            'todos' => $todos,
        ]);
    }
})
->value('id', null);

/* ADD TODO */
$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');

    // 3-12-22, Kate: TASK-1: fix for empty description on add todo
    if($description != ''){
        $sql = "INSERT INTO todos (user_id, description) VALUES ('$user_id', '$description')";
        // 3-12-22, Kate: TASK-4: Added confirmation message when added TODO
        if($app['db']->executeUpdate($sql)){
            $app['session']->getFlashBag()->add(
                    "msg", ["class" => "success", "message" => "Yay! You added new TODO!"]
            );
        }
    }else{
        // 3-12-22, Kate: TASK-4: Added error message when no description
        $app['session']->getFlashBag()->add(
            "msg", ["class" => "danger", "message" => "Please set description before adding TODO ^___^"]
        );
    }

    return $app->redirect('/todo');
});

/* DELETE TODO */
$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = '$id'";
    
    // 3-12-22, Kate: TASK-4: Added confirmation message when TODO is deleted
    if($app['db']->executeUpdate($sql)){
        $app['session']->getFlashBag()->add(
                    "msg", ["class" => "info", "message" => "Hooray! TODO deleted successfully!"]
        );
    }

    return $app->redirect('/todo');
});

/* 4-12-22, Kate, TASK-2: SET COMPLETED STATUS */
$app->match('/todo/set_completed/{id}', function ($id) use ($app) {

    // select clicked TODO to get current completed status
    $sql1 = "SELECT completed FROM todos WHERE id = '$id'";
    $todoToUpdate = $app['db']->fetchAssoc($sql1);

    $todo_completed_current = $todoToUpdate['completed'];

    $todo_completed_to_set = 'N';
    if($todo_completed_current == 'Y'){
        $todo_completed_to_set = 'N';
    }else{
        $todo_completed_to_set = 'Y';
    }

    //update completed staus
    $sql2 = "UPDATE todos SET completed = '$todo_completed_to_set' WHERE id = '$id'";

    if($app['db']->executeUpdate($sql2)){
        if($todo_completed_to_set == 'Y'){
            $app['session']->getFlashBag()->add(
                        "msg", ["class" => "success", "message" => "Geronimo! TODO #".$id." has being completed!"]
            );
        }else{
             $app['session']->getFlashBag()->add(
                        "msg", ["class" => "info", "message" => "TODO #".$id." is to be continued..."]
            );
        }
    }

    return $app->redirect('/todo');
});

/* 4-12-22, Kate, TASK-3: VIEW IN JSON */
$app->match('/todo/view_in_json/{id}', function ($id) use ($app) {

    // select clicked TODO to get current completed status
    $sql1 = "SELECT * FROM todos WHERE id = '$id'";
    $todoToView = $app['db']->fetchAssoc($sql1);

    $app['session']->getFlashBag()->add(
        "msg", ["class" => "info", "message" => "/todo/".$id."/json => ".json_encode($todoToView)]
    );
       
  
    return $app->redirect('/todo');
});
