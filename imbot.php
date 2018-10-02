<?php

if (!isset($_REQUEST)) {
  return;
}

define('VK_API_ACCESS_TOKEN', '123456576878qwerrtty');
define('VK_API_VERSION', '5.67'); //Используемая версия API
define('VK_API_ENDPOINT', 'https://api.vk.com/method/');


function _vkApi_call($method, $params = array()) {
  $params['access_token'] = VK_API_ACCESS_TOKEN;
  $params['v'] = VK_API_VERSION;
  $query = http_build_query($params);
  $url = VK_API_ENDPOINT.$method.'?'.$query;
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  $json = curl_exec($curl);
  $error = curl_error($curl);
  if ($error) {
    log_error($error);
    throw new Exception("Failed {$method} request");
  }
  curl_close($curl);
  $response = json_decode($json, true);
  if (!$response || !isset($response['response'])) {
    log_error($json);
    throw new Exception("Invalid response for {$method} request");
  }
  return $response['response'];
}



function vkApi_messagesSend($peer_id, $message, $attachments = array()) {
  return _vkApi_call('messages.send', array(
    'peer_id'    => $peer_id,
    'message'    => $message,
    'attachment' => implode(',', $attachments)
  ));
}
function vkApi_usersGet($user_id) {
  return _vkApi_call('users.get', array(
    'user_id' => $user_id,
  ));
}
function vkApi_photosGetMessagesUploadServer($peer_id) {
  return _vkApi_call('photos.getMessagesUploadServer', array(
    'peer_id' => $peer_id,
  ));
}
function vkApi_photosSaveMessagesPhoto($photo, $server, $hash) {
  return _vkApi_call('photos.saveMessagesPhoto', array(
    'photo'  => $photo,
    'server' => $server,
    'hash'   => $hash,
  ));
}
function vkApi_docsGetMessagesUploadServer($peer_id, $type) {
  return _vkApi_call('docs.getMessagesUploadServer', array(
    'peer_id' => $peer_id,
    'type'    => $type,
  ));
}
function vkApi_docsSave($file, $title) {
  return _vkApi_call('docs.save', array(
    'file'  => $file,
    'title' => $title,
  ));
}

function vkApi_upload($url, $file_name) {
  if (!file_exists($file_name)) {
    throw new Exception('File not found: '.$file_name);
  }
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new CURLfile($file_name)));
  $json = curl_exec($curl);
  $error = curl_error($curl);
  if ($error) {
    log_error($error);
    throw new Exception("Failed {$url} request");
  }
  curl_close($curl);
  $response = json_decode($json, true);
  if (!$response) {
    throw new Exception("Invalid response for {$url} request");
  }
  return $response;
}



function file_get_contents_curl($url) {
   $ch = curl_init();
   
   curl_setopt ($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
   curl_setopt($ch, CURLOPT_URL, $url);
   
   $data = curl_exec($ch);
   curl_close($ch);
   
   return $data;
}
// грузим фото вк



//Строка для подтверждения адреса сервера из настроек Callback API
$confirmationToken = '123345';
$secretKey = '12344566778qwertrytyuu';

callback_handleEvent($confirmationToken, $secretKey);


function callback_handleEvent($confirmationToken, $secretKey){
//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));


try{
// проверяем secretKey
if(strcmp($data->secret, $secretKey) !== 0 && strcmp($data->type, 'confirmation') !== 0)
    return;

//Проверяем, что находится в поле "type"
switch ($data->type) {
    //Если это уведомление для подтверждения адреса сервера...
    case 'confirmation':
        //...отправляем строку для подтверждения адреса
        echo $confirmationToken;
		echo('ok');
		http_response_code(200);
        break;

    //Если это уведомление о новом сообщении...
    case 'message_new':
        //...получаем id его автора
		
        $userId = $data->object->user_id;
		$text = $data->object->body;
        //затем с помощью users.get получаем данные об авторе
        $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$userId}&v=5.0"));
		
        //и извлекаем из ответа его имя
        $user_name = $userInfo->response[0]->first_name;
		//$ye = 1;
		
		$message = "Расписание, мемы";
		
		switch (true){
			case mb_stripos($text,'расписание') !== false:
				$message = "Расписание будет добавлено позже";
				
				$c = curl_init('http://www.ifmo.ru/ru/schedule/raspisanie_zanyatiy.htm');
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				$ifmo_data = curl_exec($c);
				$num = stristr($ifmo_data,'<div class="page-content">');
				
				if ($num !== false){
					$len = 300;
					$num = strip_tags($num);
					$message = mb_substr($num, 0, $len, 'UTF-8');
					//$message = $num;
				}
				//page-content
				//vkApi_messagesSend($userId, $message );
				//$ye == 0;
				break;
			case mb_stripos($text,'мем') !== false:
				//$message = "Мемы скоро завезем";
				$memes = file_get_contents('http://www.9gag.com');
				$pattern = '/[\w\-]+\.(jpg|png|gif|jpeg)/';
				
				
				preg_match_all($pattern,$memes,$matches);
				//now matches holds all the image urls which you can print to javascript/html and show
				
			//	$path = './images/new.jpg';
			//	file_put_contents($path, file_get_contents($matches[0]));
			
			if (isset($matches[0]) && isset($matches[0][1]))
			  $message = "https://img-9gag-fun.9cache.com/photo/" . $matches[0][1];
			else
				$message = "Не сегодня, пес";
				
				
				//vkApi_messagesSend($userId, $message);
				
				//$ye == 0;
				break;
			case mb_stripos($text,'хуй') !== false:
				$message = "Кто обзывается, {$user_name}, тот так и назывется";
				//vkApi_messagesSend($userId, $message );
				//$ye == 0;
				break;
			
			
			default:
				break;
			}
		
		
		
		
		//if ($ye == 1){
		vkApi_messagesSend($userId, $message );
		//}
        //С помощью messages.send и токена сообщества отправляем ответное сообщение
        
//$message = "Добро пожаловать в наше сообщество, {$user_name}!<br> Если у Вас возникнут вопросы, то вы всегда можете обратиться к администраторам сообщества.<br> Их контакты можно найти в соответсвующем разделе группы.<br> Успехов в учёбе!";
		
		
		
       // $get_params = http_build_query($request_params);

       // file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
		
        //Возвращаем "ok" серверу Callback API
        echo('ok');
		http_response_code(200);

        break;

    // Если это уведомление о вступлении в группу
    case 'group_join':
        //...получаем id нового участника
        $userId = $data->object->user_id;

        //затем с помощью users.get получаем данные об авторе
        $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$userId}&v=5.0"));

        //и извлекаем из ответа его имя
        $user_name = $userInfo->response[0]->first_name;
		
        //С помощью messages.send и токена сообщества отправляем ответное сообщение
        
        $message = "Добро пожаловать в наше сообщество, {$user_name}!<br> Если у Вас возникнут вопросы, то вы всегда можете обратиться к администраторам сообщества.<br> Их контакты можно найти в соответсвующем разделе группы.<br> Успехов в учёбе!";
        
		
        //Возвращаем "ok" серверу Callback API
        echo('ok');
		http_response_code(200);

        break;
	case 'message_reply':
		echo('ok');
		http_response_code(200);
		
		default:
		
		break;
}
}
catch (Exception $e) {
    log_error($e);
  }
}
?>