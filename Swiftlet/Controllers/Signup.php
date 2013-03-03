<?php

namespace Swiftlet\Controllers;

class Signup extends \Swiftlet\Controller
{
	protected
		$title = 'Create account'
		;

	/**
	 * Default action
	 */
	public function index()
	{
		$email          = isset($_POST['email'])           ? $_POST['email']           : '';
		$password       = isset($_POST['password'])        ? $_POST['password']        : '';
		$passwordRepeat = isset($_POST['password-repeat']) ? $_POST['password-repeat'] : '';

		$this->view->set('email', $email);

		if ( !empty($_POST) ) {
			$success = false;
			$error   = false;

			if ( $password != $passwordRepeat ) {
				$error = 'The provided passwords don\'t match.';
			} else {
				try {
					$auth = $this->app->getSingleton('auth');

					$result = $auth->register($email, $password);

					if ( $result ) {
						$success = 'Your account has been created. You may now sign in.';
					} else {
						$error = 'An unknown error occured. Please try again.';
					}
				} catch ( \Exception $e ) {
					$error = 'An unknown error ocurred.';

					switch ( $e->getCode() ) {
						case $auth::EMAIL_INVALID:
							$error = 'Please provide a valid email address.';

							break;
						case $auth::EMAIL_IN_USE:
							$error = 'The provided email address is already in use.';

							break;
					}
				}
			}

			$this->view->set('success', $success);
			$this->view->set('error', $error);
		}
	}
}