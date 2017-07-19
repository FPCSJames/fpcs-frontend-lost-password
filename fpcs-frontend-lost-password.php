<?php
/*
 * Plugin Name: FPCS Frontend Lost Password
 * Plugin URI: https://github.com/FPCSJames/fpcs-frontend-lost-password/
 * Version: 1.0.1
 * Description: Add a lost password shortcode for frontend usage.
 * Author: James M. Joyce, Flashpoint Computer Services, LLC
 * Author URI: http://www.flashpointcs.net
 * License: GPL 2.0+
 */
 
if(!defined('ABSPATH')) { exit; }

final class FPCS_Frontend_Lost_Password {
	
	const SLUG_ACCOUNT = '/account/';
	const SLUG_LOGIN = '/account/login/';
	const SLUG_LOST = '/account/lost-password/';
	
	public function __construct() {
		add_action('admin_init', array($this, 'admin_profile_block_page'), 1);
		add_action('init', array($this, 'front_lost_password_rewrite'));
		add_action('lostpassword_post', array($this, 'front_lost_password_validate_username'), 99);
		add_filter('lostpassword_url', function($url) { return site_url(self::SLUG_LOST); });
		add_filter('query_vars', array($this, 'front_lost_password_query_vars'), 10, 1);
		add_filter('retrieve_password_message', array($this, 'front_lost_password_email_message'), 10, 4);
		add_filter('show_admin_bar', array($this, 'front_show_admin_bar'));
		add_action('secure_signon_cookie', '__return_true');
		add_shortcode('lostpassword', array($this, 'front_lost_password_shortcode'));
	}

	public function admin_profile_block_page() {
		if($this->util_has_role('subscriber') && !(defined( 'DOING_AJAX' ) && DOING_AJAX)) {
			wp_redirect(site_url());
			exit;
		}
	}
	
	public function front_lost_password_email_message($message, $key, $user_login, $user_data) {
		$link = site_url(self::SLUG_LOST.'reset/?key='.rawurlencode($key).'&login='.rawurlencode($user_login));
		
		$message = __('Someone requested that the password be reset for your '.get_bloginfo('name').' account.') . "<br><br>\r\n\r\n";
		$message .= __('If you did not make this request, or it was accidental, just ignore this email and nothing will happen.') . "<br><br>\r\n\r\n";
		$message .= __('To reset your password, visit:') . "<br><br>\r\n\r\n";
		$message .= '<a href="'.$link.'">'.$link."</a><br>\r\n";
		
		return $message;
	}
	
	public function front_lost_password_shortcode() {
		if(is_user_logged_in()) {
			$output = '<div class="text-center">You\'re already logged in. To change your password, visit your <a href="'.self::SLUG_ACCOUNT.'">Account</a> page.</div>';
		} else {
			$data = get_query_var('d', '');
			$output = '<div class="text-center">'."\n";
			$lost_head = "<p>Enter your email address below and we'll send a reset link.</p>\n";
			ob_start();
			?>	
				<form name="lostpasswordform" id="lostpasswordform" class="form-horizontal" action="<?php echo site_url('wp-login.php?action=lostpassword', 'login_post'); ?>" method="post">
					<input type="hidden" name="redirect_to" value="<?php echo wp_lostpassword_url().'/success/'; ?>">
					<div class="form-group">
						<label for="user_login" class="sr-only">Your email address</label>
						<div class="col-sm-12">
							<input type="text" name="user_login" id="user_login" class="form-control" placeholder="Your email address">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-12">
							<input type="submit" name="wp-submit" id="wp-submit" class="btn btn-lg btn-block btn-custom" value="Send password reset email">
						</div>
					</div>
				</form>
			</div>
			<?php
			$form_lost = ob_get_clean();
			switch($data) {
				case 'retry':
					$output .= $lost_head.'<div class="alert alert-danger">That username or email was not found.</div>'."\n".$form_lost;
					break;
				case 'success':
					$output .= '<div class="alert alert-success">Check your email for the reset link. You can close this tab/window.</div>'."\n";
					break;
				case 'invalid':
					$output .= $lost_head.'<div class="alert alert-danger">Your reset link was not valid. Please re-enter your email address to get a new one.</div>'."\n".$form_lost;
					break;
				case 'reset':
					// Start WP core code - from wp-login.php as of WordPress 4.4.1
					list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
					$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
					if ( isset( $_GET['key'] ) ) {
						$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
						setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
						wp_safe_redirect( remove_query_arg( array( 'key', 'login' ) ) );
						exit;
					}
					if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
						list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
						$user = check_password_reset_key( $rp_key, $rp_login );
						if ( isset( $_POST['pass1'] ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
							$user = false;
						}
					} else {
						$user = false;
					}
					if ( ! $user || is_wp_error( $user ) ) {
						setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
						wp_redirect( site_url( self::SLUG_LOST.'invalid/' ) );
						exit;
					}
					$errors = new WP_Error();
					if ( isset($_POST['pass1']) && $_POST['pass1'] != $_POST['pass2'] ) {
						$errors->add( 'password_reset_mismatch', __( 'The passwords do not match.' ) );
					}
					/**
					 * Fires before the password reset procedure is validated.
					 *
					 * @since 3.5.0
					 *
					 * @param object           $errors WP Error object.
					 * @param WP_User|WP_Error $user   WP_User object if the login and reset key match. WP_Error object otherwise.
					 */
					do_action( 'validate_password_reset', $errors, $user );
					if ( ( ! $errors->get_error_code() ) && isset( $_POST['pass1'] ) && !empty( $_POST['pass1'] ) ) {
						reset_password($user, $_POST['pass1']);
						setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
						$output .= '<p class="bg-success text-success">Your password was successfully reset. You may now <a href="'.self::SLUG_LOGIN.'">log in</a>.</p></div>';
					} else {
						// End WP core code
						if($errors->get_error_code()) {
							foreach($errors->get_error_codes() as $code) {
								foreach($errors->get_error_messages($code) as $error_message) {
									$output .= '<p class="bg-danger text-danger">'.$error_message.'</p>';
								}
							}
						}
						ob_start();
						?>
						<form name="resetpassform" id="resetpassform" class="form-horizontal" action="<?php echo esc_url(site_url(self::SLUG_LOST.'reset/', 'login_post')); ?>" method="post" autocomplete="off">
							<input type="hidden" id="user_login" value="<?php echo esc_attr( $rp_login ); ?>" autocomplete="off">
							<div class="form-group">
								<div class="col-xs-12">Your username is: <strong><?php echo $rp_login; ?></strong></div>
							</div>
							<div class="form-group">
								<label for="pass1" class="sr-only"><?php _e('New password') ?></label>
								<div class="col-xs-12"><input type="password" name="pass1" id="pass1" class="form-control" size="20" placeholder="New password" autocomplete="off"></div>
							</div>
							<div class="form-group">
								<label for="pass2" class="sr-only"><?php _e('Confirm new password') ?></label>
								<div class="col-xs-12"><input type="password" name="pass2" id="pass2" class="form-control" size="20" placeholder="Confirm new password" autocomplete="off"></div>
							</div>
							<div class="form-group">
								<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
								<div class="col-xs-12"><input type="submit" name="wp-submit" id="wp-submit" class="btn btn-lg btn-block btn-custom" value="<?php esc_attr_e('Reset Password'); ?>"></div>
							</div>
						</form>
					</div>
						<?php
						$resetform = ob_get_clean();
						$output .= $resetform;
					}
					break;
				default:
					$output .= $lost_head.$form_lost;
					break;
			}
			
		}
		return $output;
	}
	
	public function front_lost_password_query_vars($vars){
		$vars[] = "d";
		return $vars;
	}
	
	public function front_lost_password_rewrite() {
		add_rewrite_rule('account/lost-password/([^/]*)/?','index.php?pagename=account/lost-password&d=$matches[1]','top');
	}
	
	// Lost password form error redirect - hook into lostpassword_post - https://stackoverflow.com/questions/21649497/wordpress-front-end-reset-password-page-redirect-after-username-or-email-doesn
	public function front_lost_password_validate_username() {
		$user_login = trim(sanitize_text_field($_POST['user_login']));
		$reject = false;
		
		if(!empty($user_login)) {
			if(is_email($user_login)) {
				if(!email_exists($user_login)) {
					$reject = true;
				}
			} else {
				if(!username_exists($user_login)) {
					$reject = true;
				}
			} 
		} else {
			$reject = true;
		}
		
		if($reject) {
			wp_redirect(site_url(self::SLUG_LOST.'retry/'));
			exit;
		}
	}
	
	public function front_show_admin_bar($show) {
		if($this->util_has_role('subscriber')) {
			return false;
		}
		
		return $show;
	}
	
	/* Via http://docs.appthemes.com/tutorials/wordpress-check-user-role-function/ via WP Codex 
	 * Check specified or current user for role, more reliable than user_can
	 */
	private function util_has_role($role, $user_id = null) {
		if(is_numeric($user_id)) {
			$user = get_userdata($user_id);
		} else {
			$user = wp_get_current_user();
		}

		if(empty($user)) {
			return false;
		}
		
		return in_array($role, (array) $user->roles);
	}
	
}

new FPCS_Frontend_Lost_Password();