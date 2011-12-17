<?php
/**
 * Copyright 2011 MKN Web Solutions
 * http://mknwebsolutions.com
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
 
header('Content-type: application/json');

ini_set("display_errors",0);
require 'facebook.php';
$api = new facebookAPI();

class facebookAPI{

	function __construct(){
	
		//checks for development environment
		if(preg_match('/(dev)+/',$_SERVER['HTTP_HOST'])){
			//development
			$facebook = new Facebook(array(
				'appId'  => '#',
				'secret' => '#',
			));
		}else{
			//production 
			$facebook = new Facebook(array(
				'appId'  => '#',
				'secret' => '#',
			));
		}
		
		//gets the latest timestamp
		$incomingtimestamp = ($_GET['timestamp'] && $_GET['timestamp']!="null")? $_GET['timestamp'] : strtotime("-1 day");
	
		//old slow code
		//$getfeedarray = $facebook->api('/me/home?since=' . $incomingtimestamp);
		//filter_key in (SELECT filter_key FROM stream_filter WHERE uid=me() AND type='newsfeed')
		$mq = array(
			"q1"=>"SELECT post_id, actor_id, created_time, message, description FROM stream WHERE filter_key = 'others' AND created_time > $incomingtimestamp ORDER BY created_time DESC",
			"q2"=>"SELECT uid, username, first_name, last_name, current_location FROM user WHERE uid IN (SELECT actor_id FROM #q1)"
		);
		
		$fql = array(
			'method' => 'fql.multiquery',
			'queries' => $mq,
			'callback' => ''
		);
		$getfeedarray = $facebook->api($fql);
		
		
		$preload = array();
		$preload = $getfeedarray[1]['fql_result_set'];
		
		$users = array();
		foreach($preload as $out){
			$users[$out['uid']] = $out;
		}
		
		$buffer = array();
		foreach($getfeedarray[0]['fql_result_set'] as $post){
			
			if($post['message']){
				$message = $post['message'];
			}elseif($post['description']){
				$message = $post['description'];
			}else{
				$message = "";
			}
		
			$buffer['data'][] = array("id"=>$post['post_id'],
																"from"=>$users[ $post['actor_id'] ]['first_name']." ".$users[ $post['actor_id'] ]['last_name'],
																"fromid"=>$post['actor_id'],
																"timestamp"=>$post['created_time'], //no need for strtotime
																"location"=>$users[ $post['actor_id'] ]['current_location']['name'],
																"message"=> $message);
		}
		
		$buffer['stamp'] = $buffer['data'][0]['timestamp'];
		print $_GET['callback']."(".json_encode($buffer).")";
	
	}

}


?>