<?php
/**
 * Copyright 2011 MKN Web Solutions
 * http://mknwebsolutions.com
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
 
require 'facebook.php';
session_cache_expire(120);

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

$user = $facebook->getUser();

if($user){
  try{
  	$user_profile = $facebook->api('/me');
  }catch (FacebookApiException $e){
  	$user = null;
  }
}

if($user){
  $logoutUrl = $facebook->getLogoutUrl();
}else{
	$params = array('scope'=>'publish_stream,read_stream,friends_checkins,friends_online_presence,friends_location');
  //header("Location:{$facebook->getLoginUrl($params)}");
  echo "<script>top.location.href='".$facebook->getLoginUrl($params)."';</script>";
  return;
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Facebook Map Feed</title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js"></script>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=true&callback=initialize" async="async" async></script>
<script>
//globals
var map, geocoder, marker, refresh, timestamp = "", infowindow, liveMarkers = [];

function initialize() {
	alert("Loading latest post with locations...");
	var initialLocation;
	var newyork = new google.maps.LatLng(40.69847032728747, -73.9514422416687);
	var browserSupportFlag =  new Boolean();
	
  var myOptions = {
    zoom: 3,
    mapTypeId: google.maps.MapTypeId.ROADMAP
  };
  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
  geocoder = new google.maps.Geocoder();
  infowindow = new google.maps.InfoWindow();	
  
  fapi.loadApi();
  refresh = setInterval("fapi.loadApi()", 10000);
  
   
  //get geolocation
  if(navigator.geolocation) {
    browserSupportFlag = true;
    navigator.geolocation.getCurrentPosition(function(position) {
      initialLocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
      map.setCenter(initialLocation);
    }, function() {
      handleNoGeolocation(browserSupportFlag);
    });
  }
  
  function handleNoGeolocation(errorFlag) {
    if (errorFlag == true) {
      alert("Geolocation service failed.");
      initialLocation = newyork;
    } else {
      alert("Your browser doesn't support geolocation. We've placed you in New York.");
      initialLocation = newyork;
    }
    map.setCenter(initialLocation);
  }
}

var checkr = null;
function alert(t){
	clearTimeout(checkr);
	$('#alert').html(t);
	$('#alert').show(300);
	checkr = setTimeout("hideAlert()", 5000);
}

function hideAlert(){
	$('#alert').hide(300);
}

</script>
<script>
//setup facebook API
var fapi, fapi = {}

fapi.loadApi = function(){
	
	//need to add last timestamp check
	$.getJSON("api.php?callback=?",{"timestamp":timestamp},function(d){
		timestamp = (d.stamp != null)? d.stamp : timestamp;
		$.each(d.data, function(k,v){
			geocoder.geocode({'address':v.location}, function(r,s){
				
				if(s == google.maps.GeocoderStatus.OK){
					marker = new google.maps.Marker({
					    position:r[0].geometry.location,
					    title:v.name,
					    draggable:false,
					    animation: google.maps.Animation.DROP
					});
					marker.timestamp = v.timestamp;
					marker.setMap(map);
					map.setCenter(r[0].geometry.location);
					
					var htmlContent = '<a href="https://www.facebook.com/profile.php?id='+v.fromid+'" target="_blank"><img style="vertical-align:middle; margin:5px; border-radius:5px; border:0px" src="https://graph.facebook.com/'+v.fromid+'/picture" ></a><p style="vertical-align:middle">'+ v.message + "</p>";
					
					infowindow.setContent(htmlContent);
					infowindow.setPosition(r[0].geometry.location);
					infowindow.open(map,marker);
					
					liveMarkers.push(marker);
					
					google.maps.event.addListener(marker, 'mouseover', function(e){
						infowindow.setContent(htmlContent);
						infowindow.setPosition(r[0].geometry.location);
						infowindow.open(map);
					});
					google.maps.event.addListener(marker, 'mouseout', function(e){
						setTimeout(function(){
							infowindow.setContent("");
							infowindow.close(map);
						},10000);
					});
				}
				
			});
			
		});
		
	});
}


</script>
<style type="text/css">
html { height: 100% }
body { height: 100%; margin: 0; padding: 0 }
#map_canvas { height: 100% }
#alert{
	position:fixed; display:none; left:50%; margin-left:-250px; bottom:120px; width:400px; 
	text-align:center; height:auto; background:#eee; padding:30px 10px; font-size:20px; 
	font-weight: bold; border-radius:10px; 
	box-shadow:0px 5px 15px rgba(0,0,0,.4); -webkit-box-shadow:0px 5px 15px rgba(0,0,0.4); -moz-box-shadow:0px 5px 15px rgba(0,0,0.4); 
	border:5px solid rgba(0,0,0,.5);
	background-clip: padding-box; text-shadow:0px 1px 0px #FFF;
}
p{
	display:inline-block;
}
</style>
</head>

<body>
  <div id="map_canvas" style="width:100%; height:100%"></div>

	<div id="alert"><div id="alertText">...</div></div>  
</body>
</html>