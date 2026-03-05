<?php
/**
 * @file
 * Contains \Drupal\utility\Plugin\Block\CustomUserNavigation.
 */
 

 
namespace Drupal\utility\Plugin\Block;
use Drupal\Core\Block\BlockBase;
 if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
/**
 * Provides a 'useraccount' block.
 *
 * @Block(
 *   id = "custom_user_navigation",
 *   admin_label = @Translation("User Navigation Block"),
 *   category = @Translation("Custom User Account Navigation block")
 * )
 */

class CustomUserNavigation extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {

     $uid = \Drupal::currentUser()->id();
     $user = \Drupal\user\Entity\User::load($uid);
     $username = $user->getDisplayName();

     $_SESSION['G_UID'] = $uid;

	 $_SESSION['G_UN'] = $username;
	
     if ($uid != 0) {
     	$output = "<div class='usernavigation'>
     				<div class ='left-side'>
     					<p> Hello, $username </p>
     				</div>
     				<div class='right-side'>
     					<div class='available-links'>
     						<a class = 'link-my-account' href='/user'>My Account</a>
     						<span>|</span>
     						<a class = 'link-logout' href='/user/logout'>Logout</a>
     					</div>
     				</div>
     			</div>";
     }else{
     	$link = "<a href='/user/login'>login</a>";
     		$output = "<div class='usernavigation'>
     				<div class ='left-side'>
     					<p>Hello, Guest. Please $link to play Ride Sims.</p>
     				</div>
     				</div>";
     }




    return array(
      '#type' => 'markup',
      '#markup' => $output,
     '#attached' => array(
      'library' => array(
        'utility/utility.custom.css',
      ),
    )
    );
  }


    public function getCacheMaxAge() {
        return 0;
    }
    
}