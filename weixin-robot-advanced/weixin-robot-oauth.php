<?php
function weixin_robot_get_oauth_redirect($scope='snsapi_userinfo', $state='', $redirect_uri=''){
	if(weixin_robot_get_setting('weixin_app_id') && weixin_robot_get_setting('weixin_app_secret')){

		$redirect_uri = ($redirect_uri)?$redirect_uri:remove_query_arg(array('get_userinfo','get_openid'),weixin_robot_get_current_page_url());

		$state = ($state)?$state:($scope=='snsapi_userinfo')?'userinfo':'openid';

		return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.weixin_robot_get_setting('weixin_app_id').'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope='.$scope.'&state='.$state.'#wechat_redirect';
	}
}

function weixin_robot_get_oauth_access_token($code){
	if(weixin_robot_get_setting('weixin_app_id') && weixin_robot_get_setting('weixin_app_secret') ){
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.weixin_robot_get_setting('weixin_app_id').'&secret='.weixin_robot_get_setting('weixin_app_secret').'&code='.$code.'&grant_type=authorization_code';

		$request = wp_remote_get($url);

		if ( is_wp_error( $request ) || 200 != wp_remote_retrieve_response_code( $request ) ){
			if(isset($_GET['debug'])){
				echo '<div class="error" style="color:red;"><p>错误：'.$request->get_error_code().'：'. $request->get_error_message().'</p></div>';	
			}
			return false;
		}

		$response = json_decode( wp_remote_retrieve_body( $request ) );

		if(isset($response->errcode)){
			if(isset($_GET['debug'])){
				echo '<div class="error" style="color:red;"><p>错误：'.$response->errcode.'：'. $response->errmsg.'</p></div>';	
			}
			return false;
		}

		return $response;
	}
}

function weixin_robot_get_oauth_userifo($weixin_openid, $access_token){
	$url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$weixin_openid.'&lang=zh_CN';
	$request = wp_remote_get($url);

	if ( is_wp_error( $request ) || 200 != wp_remote_retrieve_response_code( $request ) ){
		if(isset($_GET['debug'])){
			echo '<div class="error" style="color:red;"><p>错误：'.$request->get_error_code().'：'. $request->get_error_message().'</p></div>';	
		}
		return false;
	}

	$response = json_decode( wp_remote_retrieve_body( $request ) );

	if(isset($response->errcode)){
		if(isset($_GET['debug'])){
			echo '<div class="error" style="color:red;"><p>错误：'.$response->errcode.'：'. $response->errmsg.'</p></div>';	
		}
		return false;
	}

	return $response;
}

function weixin_robot_oauth_update_user($code){

	$response = weixin_robot_get_oauth_access_token($code);

	if(!$response) return;

	$access_token	= $response->access_token;
	$expires_in		= $response->expires_in;
	$refresh_token	= $response->refresh_token;
	$weixin_openid	= $response->openid;
	$scope			= $response->scope;

	$query_id = weixin_robot_get_user_query_id($weixin_openid);
	weixin_robot_set_query_cookie($query_id);

	// 确认下是否订阅的
	$weixin_user = weixin_robot_get_user($weixin_openid,'local');
	if($weixin_user){
		$subscribe = $weixin_user['subscribe'];
	}else{
		$subscribe = 0;
		$weixin_user = weixin_robot_get_user($weixin_openid,'oauth'); //先写入数据
	}
	
	$weixin_user['subscribe']		= $subscribe;
	$weixin_user['access_token']	= $access_token;
	$weixin_user['expires_in']		= time()+$expires_in;
	$weixin_user['refresh_token']	= $refresh_token;

	$userinfo = weixin_robot_get_oauth_userifo($weixin_openid, $access_token);

	$weixin_user['nickname']		= $userinfo->nickname;
	$weixin_user['sex']				= $userinfo->sex;
	$weixin_user['province']		= $userinfo->province;
	$weixin_user['city']			= $userinfo->city;
	$weixin_user['country']			= $userinfo->country;
	$weixin_user['headimgurl']		= $userinfo->headimgurl;
	$weixin_user['privilege']		= serialize($userinfo->privilege);

	weixin_robot_update_user($weixin_openid,$weixin_user);	
}