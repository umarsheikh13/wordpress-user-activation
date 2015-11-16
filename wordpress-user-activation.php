<?php
/**
 * WordPress User Activation
 *
 * These set of hooks and functions enables your users to activate 
 * their accounts before they can log in. All you need to do 
 * is drop the code into your theme's functions.php file or turn this 
 * into a plugin and install it.
 *
 * @version 0.1.0
 * @author Umar Sheikh <hello@umarsheikh.co.uk>
 */


// Login page messages

add_filter( 'login_message', 'ua_activation_login_message' );

function ua_activation_login_message() {

	// Activated

	if ( $_GET['ua_status'] == 'true' ) {
		return __( '<p class="message">Your account has been activated successfully.</p>', 'domain' );
	}

	// Not activated

	if ( $_GET['ua_status'] == 'false' ) {
		return __( '<p id="login_error" class="message">Please activate your account.</p>', 'domain' );
	}

	// Already activated

	if ( $_GET['ua_status'] == 'already' ) {
		return __( '<p id="login_error" class="message">This account has already been activated.</p>', 'domain' );
	}

	// Invalid key

	if ( $_GET['ua_status'] == 'invalid' ) {
		return __( '<p id="login_error" class="message">This activation key is invalid.</p>', 'domain' );
	}

	// Activation email sent

	if ( $_GET['ua_status'] == 'email' ) {
		return __( '<p class="message">You have been sent an activation email.</p>', 'domain' );
	}

}

// Check activation status after login

add_action( 'wp_login', 'ua_activation_login_check', 10, 2 );

function ua_activation_login_check( $user_login, $user ) {

	// Get activation status

	$activation_status = get_user_meta( $user->ID, 'ua_status', true );

	// Check to see if user isn't an admin

	if ( !current_user_can( 'activate_plugins' ) ) {

		// Check activation status

		if ( $activation_status == 'false' ) {

			// Log user out and redirect
			
			wp_logout();
			wp_redirect( wp_login_url() . '?ua_status=false' );
			exit;

		}

	}

}

// Activate account with key

add_action( 'init', 'ua_activation_function' );

function ua_activation_function(){

	if ( isset( $_GET['ua_key'] ) ) {

		// Get users with key defined

		$get_users = get_users( 'meta_key=ua_key&meta_value=' . $_GET['ua_key'] );

		// Check if a user has the same key

		if ( count( $get_users ) ) {

			foreach ( $get_users as $get_user ) {

				$activation_status = get_user_meta( $get_user->ID, 'ua_status', true );

				if ( $activation_status == 'false' ) {

					// Activate user and redirect to login page

					update_user_meta( $get_user->ID, 'ua_status', 'true' );
					wp_redirect( wp_login_url() . '?ua_status=true' );
					exit;

				}

			}

		}else{

			// Redirect to login page as key is invalid

			wp_redirect( wp_login_url() . '?ua_status=invalid' );
			exit;

		}

	}

}

// Send activation link after registration

add_action( 'user_register', 'ua_user_register' );

function ua_user_register( $user_id ) {

	// Check if user is being created from the backend

	if ( is_admin() ) {

		// Admin is creating user from backend so activate user straight away
		
		update_user_meta( $user_id, 'ua_status', 'true' );

	} else {

		// Set activation key and status

		$activation_key = md5( home_url() . $user_id . time() );

		update_user_meta( $user_id, 'ua_key', $activation_key );
		update_user_meta( $user_id, 'ua_status', 'false' );

		// Set password, send activation link and redirect to login page

		$get_user = get_user_by( 'id', $user_id );

		$user_password = wp_generate_password();
		wp_set_password( $user_password, $user_id );

		$email_subject = 'Activate Account';
		$email_message = sprintf( __( "Hi %s,\n\nThanks for registering with us, these are your login details:\n\nUsername: %s\nPassword: %s\n\nPlease click on the link below to activate your account:\n\n%s\n\n%s\n%s", 'domain' ),
			$get_user->user_login,
			$get_user->user_login,
			$user_password,
			home_url( '/?ua_key=' . urlencode( $activation_key ) ),
			get_bloginfo( 'name' ),
			home_url( '/' )
		);

		if ( wp_mail( $get_user->user_email, $email_subject, $email_message ) ) {
			wp_redirect( wp_login_url() . '?ua_status=email' );
			exit;
		}

	}

}

?>
