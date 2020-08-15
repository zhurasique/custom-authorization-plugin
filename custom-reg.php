<?php
/*
  Plugin Name: Custom Registration
  Description: Custom registration and authorization.
  Version: 1.0
  Author: Serhii Zhura
 */


function custom_registration_registration_form($email) {
    echo '
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
    <div>
        <label for="email">Email <strong>*</strong></label>
        <input type="text" name="email" value="' . ( isset( $_POST['email']) ? $email : null ) . '">
    </div>
    
    <input type="submit" name="submit" value="Register"/>
    </form>
    ';
}

function custom_registration_registration_validation($email)
{
    global $reg_errors;
    $reg_errors = new WP_Error;

    if (!is_email($email) || empty($email)) {
        $reg_errors->add('email_invalid', 'Email is not valid');
    }

    if (email_exists($email)) {
        $reg_errors->add('email', 'Email Already in use');
    }

    if (is_wp_error($reg_errors)) {

        foreach ($reg_errors->get_error_messages() as $error) {

            echo '<div>';
            echo '<strong>ERROR</strong>:';
            echo $error . '<br/>';
            echo '</div>';

        }
    }
}

function custom_registration_complete_registration() {
    global $reg_errors, $email;
    if ( 1 > count( $reg_errors->get_error_messages() ) ) {
        $username = $email;
        $password = wp_generate_password(12, true, false);

        $userdata = array(
            'user_login'    =>   $username,
            'user_email'    =>   $email,
            'user_pass'     =>   $password
        );
        $user = wp_insert_user( $userdata );

        wp_mail($email, "Registration password", $password);
    }
}

function custom_registration_function() {
    global $email;
    if ( isset($_POST['submit'] ) ) {
        custom_registration_registration_validation($_POST['email']);

        $email = sanitize_email( $_POST['email'] );

        custom_registration_complete_registration($email);
    }

    custom_registration_registration_form(
        $email
    );
}

function custom_registration_create_roles( $role, $display_name, $capabilities = array() ) {
    if ( empty( $role ) ) {
        return;
    }
    return wp_roles()->add_role( $role, $display_name, $capabilities );
}

function custom_registration_change_user_role($user_id) {
    $user = get_userdata($user_id);

    foreach($user->roles as $value) {
        $user->remove_role($value);
    }

    $user->user_login = $user->user_email;

    custom_registration_create_roles('test_user', 'test_user', array( 'read' => true, 'level_0' => true ));

    $user->add_role('test_user');

}

function custom_registration_authorization_form()
{
    echo '
    <form name="loginform" id="loginform" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			<p>
				<label for="user_login">Имя пользователя или email</label>
				<input type="text" name="log" id="user_login" class="input" value="" size="20" autocapitalize="off">
			</p>

			<div class="user-pass-wrap">
				<label for="user_pass">Пароль</label>
				<div class="wp-pwd">
					<input type="password" name="pwd" id="user_pass" class="input password-input" value="" size="20">
				</div>
			</div>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Войти">
									<input type="hidden" name="testcookie" value="1">
			</p>
		</form>
    ';
}

// Register a new shortcode: [cr_custom_authorization]
add_shortcode('cr_custom_authorization', 'custom_authorization_shortcode' );

function custom_authorization_shortcode() {
    ob_start();
    custom_registration_authorization_form();
    return ob_get_clean();
}

// Register a new shortcode: [cr_custom_registration]
add_shortcode('cr_custom_registration', 'custom_registration_shortcode' );

function custom_registration_shortcode() {
    ob_start();
    custom_registration_function();
    return ob_get_clean();
}

add_action('user_register', 'custom_registration_change_user_role');