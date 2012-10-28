<?php
//Initialize the application
require_once __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SessionServiceProvider;

$app = new Silex\Application();
$ext = 'json';

$app->register(new TwigServiceProvider(), array(
'twig.path'=>__DIR__.'/views',
'twig.class'=>__DIR__.'/../vendor/twig/lib',
));
$app->register(new Silex\Provider\SessionServiceProvider());

//Register two filters
$app->before(function(Request $request){
    $accept = $request->headers->get('Accept');
    switch($accept){
    case 'text/xml': $ext='xml';break;
    case 'text/html': $ext='html';break;    
    default: break;
    }
    
    if($app['session']->get('todos')==null)
        $app['session']->set('todos', array());
});

$app->after(function(Request $request, Response $response){
    switch($ext){
    case 'json': $response->headers->set('Content-Type', 'application/json'); break;
    case 'xml': $response->headers->set('Content-Type', 'text/xml'); break;
    case 'html': $response->headers->set('Content-Type', 'text/html'); break;  
    default: break;    
    }    
    
});



//Configure the routes
$app->get('todos', function() use ($app){
    return $app['twig']->render('todo.'.$ext.'.twig');
});
   
$app->post('todos', function() use ($app){
 $id = time();    
 $title = $request->get('title');
 $description = $request->get('description');
 $due_date = $request->get('due_date');
 $data = $app['session']->get('todos');    
 array_push($data, array(
 'id'=>$id,
 'title'=>$title,
 'description'=>$description,
 'due_date'=>$due_date,     
 ));
 $app['session']->set('todos', $data);    
 return new Response('', 201, array('Location: '=>'todos/'.$id));    
});

$app->get('todos/{id}', function($id) use ($app){
    $data = $app['session']->get('todos');
    $ret_todo = array();
    foreach($data as $todo){
     if($todo['id']==$id){
      $ret_todo = $todo;
      break;        
     }
    }    
    return $app['twig']->render('todo.'.$ext.'.twig', array('todo'=>$ret_todo));
});

$app->delete('/todos/{id}', function($id) use ($app){
    $data = $app['session']->get('todos');
    $index = -1;
    fo($i=0;$i<count($data);$i++){
     if($data[$i]['id']==$id){
      $index = $i;
      break;        
     }
    } 
    unset($data[$index]);
    $app['session']->set('todos', $data);
    return new Response('',204);
});

$app->put('/todos/{id}', function($id) use ($app){
    $data = $app['session']->get('todos');
    $index = -1;
    fo($i=0; $i<count($data);$i++){
     if($data[$i]['id']==$id){
      $index = $i;
      break;        
     }
    } 
    $data[$index] = array(
    'title'=> ,
    'description'=>,
    'due_date'=>,
    'status'=>,    
    );
    $app['session']->set('todos', $data);

    
return new Response('',204, array('Location:'=>'todos/'.$id));
});

$app->options('/', function() use ($app){
return new Response('',200, array('Allow: '=>'GET, PUT, POST, DELETE, HEAD'));
});

$app->run();
