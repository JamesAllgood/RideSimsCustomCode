<?php

namespace Drupal\utility\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

//database includes
use Drupal\Core\Database\Database;
use Drupal\views\ViewExecutable;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Class OffersController.
 ** @package Drupal\utility\Controller
 */ 
 
 	function output_variables($output) {
		global $sim_session;
		
		if (is_string($output)) {
			$output = array("result" => "error", "error" => $output);
		} else {
			$output['result'] = "success";
		}
		
		$output['session'] = $sim_session;
		
		
		if (isset($_GET['format']) == false) {
			$format = "url";
		} else {
			$format = $_GET['format'];
		}
		
		
		switch ($format) {
			case "url": {
				header("Content-type: text/plain");
				echo(http_build_query($output));
			} break;
			
			case "json": {
				header("Content-type: application/json");
				echo(json_encode($output));
			} break;
		}
		
		
		exit;
	}
 
class UserDetails extends ControllerBase
 {
    /**
      * Username   
      * * @return string
      * Return Logged in user's username.
      */
    public function username()
    {

    	// Check the time of submission is within 1 hour 10 minutes...
// 		if (!(isset($_GET['time']) && abs(time() - floatval($_GET['time'])) < 60*70 || true)) {
// 			exit("Invalid request time");
// 		}
		// Generate the hash...
		$query_string = "";
		foreach ($_POST as $key => $value) {
			$query_string .= $key . $value;
		}
		
		$salt = '3y2N0TI73oFIHm41fm58sSzdeVcfhj6v';
		$hash = md5($query_string . $salt . $_GET['time']);
		
		// Check the hashes match...
		if (!(isset($_GET['hash']) && $_GET['hash'] == $hash)) {
			exit("Invalid response");
		}
		
		
		//trying to beef it up and make it harder 
		
		//if sim is empty kill the code 
		if (empty($_POST['sim'])) {
         	//file_put_contents("/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_live/debuglogs/debug_main.txt",date("r")."sim hasnt been set? ".$_POST['sim']."\r\n", FILE_APPEND);
 			exit("Invalid response");
		}	
		
		//if action is empty kill the code 
		if (empty($_POST['action'])) {
         	//file_put_contents("/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_live/debuglogs/debug_main.txt",date("r")."action hasnt been set? ".$_POST['action']."\r\n", FILE_APPEND);
 			exit("Invalid response");
		}	
		
		    	
      $uid = \Drupal::currentUser()->id();
      if ($uid==0 && akv($_SESSION,'G_UID')!="") $uid=$_SESSION['G_UID'];
      $user = \Drupal\user\Entity\User::load($uid);
	  
      //Logging requests to the file and if they work (if uploads of sims arnt working uncomment line below)
      //file_put_contents("/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_live/debuglogs/debug_main.txt",date("r")." userid = ".$uid." GUID=".akv($_SESSION,'G_UID')." user = ".$user->getDisplayName()." GUN=".akv($_SESSION,'G_UID')." POST".print_r($_POST,true)."\r\n", FILE_APPEND);
      
      if ($uid != 0) {
		  switch (trim($_POST['action'])){
		  
           case "get_user_details":
            
                //file_put_contents("/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_live/debuglogs/debug_usersinfo.txt",date("r")." userid = ".$uid." GUID=".akv($_SESSION,'G_UID')." user = ".$user->getDisplayName()." GUN=".akv($_SESSION,'G_UID')." POST".print_r($_POST,true)."\r\n", FILE_APPEND);
                
                $usersroles = checkUsersRoles($uid);
                $rolesexploded = explode('$$', $usersroles);
    
                $SystemRoll = "";
    
                for ($i=0; $i<count($rolesexploded); $i++){
        
                $CurrentUsersRole = trim($rolesexploded[$i]);

                switch ($CurrentUsersRole) {

                case "ride_sims_user_basic":
                    $SystemRoll = "0";
                break;

                case "ride_sim_supporter_":
                    if($SystemRoll <= "1"){
                        $SystemRoll = "1";
                    }
                break;
            
                case "_ride_sims_patron":
                    if($SystemRoll <= "2"){
                        $SystemRoll = "2";
                    }
                break;
            
                case "vip_user":
                    if($SystemRoll <= "3"){
                        $SystemRoll = "3";
                    }
                break;
            
                case "ride_sims_platinum_member":
                    if($SystemRoll <= "4"){
                        $SystemRoll = "4";
                    }
                break;
            
                case "administrator":
                    if($SystemRoll <= "4"){
                        $SystemRoll = "4";
                    }
                break;
            }
        }
                $currentUser = [
                    "id" => $uid,                
                    "username" => $user->getDisplayName(),
                    "userLevel" => $SystemRoll
                ];
                                
                //sending to the sim 
                output_variables($currentUser);
				

			break;
			
			case "challenge_information":
			
			break;
		
			
			case "submit_score":
			
				//Log File Testing 
				//file_put_contents("/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_live/debuglogs/debug_submit.txt",date("r"). json_encode($_POST)."  \r\n",FILE_APPEND);
		  		
		  		$conn = \Drupal\Core\Database\Database::getConnection('default');
 		  		
 		  		//Check the score hasnt been submitted twice
				$result = $conn->query("SELECT * FROM `playerdata` where `sim`=:sim and `username`=:user and `DateTime` > DATE_SUB('".date('Y/m/d H:i:s')."', INTERVAL 5 MINUTE) LIMIT 1 ", array(":sim"=>$_POST['sim'], ":user"=>$uid));
				$data = $result->fetchAll();
				$found=false;
				foreach($data as $item) {
					$found=true;
				}
				if ($found) return;
				//first username etc is the database table secition

				//submit normal sim data into database		
				$result = $conn->query("INSERT INTO `playerdata` (`DateTime`,`username`,`sim`,`score`,`simduration`,`challenge1`,`challenge2`,`challenge3`,`challenge4`,`challenge5`) VALUES (:dt, :username, :sim, :score, :simduration, :challenge1, :challenge2, :challenge3, :challenge4, :challenge5)", 
				array(
					":dt"=>date('Y/m/d H:i:s'),
					":username" => $uid,
					":sim" => $_POST['sim'],
					":score" => $_POST['score'],
 					":simduration" => $_POST['simduration'],
 					":challenge1" => $_POST['challenge1'],
 					":challenge2" => $_POST['challenge2'],
 					":challenge3" => $_POST['challenge3'],
 					":challenge4" => $_POST['challenge4'],
 					":challenge5" => $_POST['challenge5']
				)) or handleError(mysqli_error($conn), '');
					
			//tells the sim the user score has been posted to server
			output_variables(array());
		
			break;
			
			default:
			/// Something else was sent
			break;
			
		}
        
      }else{
      
        $username = "no user found";
        
        //No User Found
        //file_put_contents("/kunden/homepages/34/d764919300/htdocs/www_ridesims_com_live/debug.log",date("r")." NO USER FOUND userid = ".$uid."\r\n", FILE_APPEND);
    
      }
      $response = new Response();
      
      	//fix undfined error in response
      	$username = "";
    	
    	/*return array('#type' => 'markup','#markup' => $this->t('Welcome to my website!'),);*/
        
        $response->setContent($username);
        return $response;
    }
 }
 
 
 
 
 if (!function_exists("akv")){
 	function akv($array, $key, $notempty=false, $default=''){ 
	    if (is_array($array) && array_key_exists($key, $array) && ($notempty || (($array[$key] !== '') && ($array[$key] !== null)))) return $array[$key];

        return $default;
    }
 }


function checkUsersRoles($userid){
	$con = \Drupal\Core\Database\Database::getConnection('default');
 
 	$query = $con->query("SELECT `roles_target_id` FROM `ex1i_user__roles` where `entity_id` = :uuid", array("uuid"=>$userid));

	$data = $query->fetchAll();
	$name='';
	foreach($data as $item) {
		$name.= $item->roles_target_id;
		$name.=" $$ ";
	}
	return ($name);
}
