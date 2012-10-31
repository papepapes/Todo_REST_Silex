<?php

require_once __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SessionServiceProvider;


//Initialize the application
$app = new Silex\Application();

//register twig provider
$app->register(new TwigServiceProvider(), array(
'twig.path'=>__DIR__.'/views',
'twig.class'=>__DIR__.'/../vendor/twig/lib',
));
//register session provider to store todo resources
$app->register(new Silex\Provider\SessionServiceProvider());

$app->before(function() use($app){
	if(! $app['session']->has('todos'))
		$app['session']->set('todos', array());
});

//AFTER filter to set the correct Content-Type: application/json or text/xml
$app->after(function(Request $request, Response $response){
	if($request->headers->get('accept') == 'application/json'||$request->headers->get('accept') == 'text/xml')
		$response->headers->set('Content-Type',$request->headers->get('accept'));
	else $response->headers->set('Content-Type','application/json');		
});

//Configure the routes
//GET /todos 
$app->get('todos', function(Request $request) use($app){
	$ext = 'json';
	if($request->headers->get('accept')=='text/xml')
		$ext = 'xml';
	return $app['twig']->render('todo.'.$ext.'.twig', array('todos'=>$app['session']->get('todos')));
});


//POST /todos
$app->post('todos', function(Request $request) use ($app){
 list($microsec, $sec) = explode(' ',microtime());
 $id = $sec.$microsec.getmypid();
     
 $title = $request->get('title');
 $description = $request->get('description');
 $due_date = $request->get('due_date');
 $status = $request->get('status');
 $data = $app['session']->get('todos');    
 array_push($data, array(
 'id'=>$id,
 'title'=>$title,
 'description'=>$description,
 'due_date'=>$due_date,
 'status'=>$status,		     
 ));
 
 $app['session']->set('todos', $data);    
 return new Response('', 201, array('Location: '=>'todos/'.$id));    
});


//GET /todos/{id}
$app->get('todos/{id}', function(Request $request, $id) use ($app){
    $data = $app['session']->get('todos');
    $ret_todo = array();
    foreach($data as $todo){
     if($todo['id']==$id){
      $ret_todo = $todo;
      break;        
     }
    }    
    $ext = 'json';
    if($request->headers->get('accept')=='text/xml')
    	$ext = 'xml';
    return $app['twig']->render('todo.'.$ext.'.twig', array('todos'=>$ret_todo));
});


//DELETE /todos/{id}
$app->delete('todos/{id}', function($id) use ($app){
	$data = $app['session']->get('todos');
	$todo_index = -1;
	for($i=0; $i<count($data);$i++){
		if($data[$i]['id']==$id){
			$todo_index = $i;
			break;
		}
	}
	if($todo_index>-1){
		unset($data[$todo_index]);
		$app['session']->set('todos',$data);
		return new Response('', 204);
	}else{
		return new Response('',404);	
	}
	
});


//PUT /todos/{id}
$app->put('todos/{id}', function(Request $request , $id) use ($app){
	$data = $app['session']->get('todos');
	$todo_index = -1;
	for($i=0; $i<count($data);$i++){
		if($data[$i]['id']==$id){
			$todo_index = $i;
			break;
		}
	}
	
	if($todo_index>-1){
		$todo = $data[$todo_index];		
		if($request->get('title')!=null)
			$todo['title'] = $request->get('title');
		if($request->get('description')!=null)
			$todo['description'] = $request->get('description');
		if($request->get('due_date')!=null)
			$todo['due_date'] = $request->get('due_date');
		if($request->get('status')!=null)
			$todo['status'] = $request->get('status');		
		$data[$todo_index] = $todo;
		$app['session']->set('todos', $data);
		return new Response('', 201, array('Location'=>'todos/'.$todo['id']));
	}else{
		return new Response('',404);
	}	
});


//OPTIONS /
$app->get('api/methods', function() use ($app){
return new Response('',200, array('Allow: '=>'GET, PUT, POST, DELETE'));
});

$app->run();
