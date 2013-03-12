<?php

namespace Swiftlet\Controllers;

class Account extends \Swiftlet\Controller
{
	protected
		$title = 'Account'
		;

	/**
	 * Default action
	 */
	public function index()
	{
		$session = $this->app->getSingleton('session');

		if ( !( $userId = $session->get('id') ) ) {
			header('Location: ' . $this->app->getRootPath() . 'signin');

			exit;
		}

		$this->view->set('timeZones', array(
			'-720' => '(GMT -12:00) Eniwetok, Kwajalein',
			'-660' => '(GMT -11:00) Midway Island, Samoa',
			'-600' => '(GMT -10:00) Hawaii',
			'-540' => '(GMT -9:00) Alaska',
			'-480' => '(GMT -8:00) Pacific Time (US &amp; Canada)',
			'-420' => '(GMT -7:00) Mountain Time (US &amp; Canada)',
			'-360' => '(GMT -6:00) Central Time (US &amp; Canada), Mexico City',
			'-300' => '(GMT -5:00) Eastern Time (US &amp; Canada), Bogota, Lima',
			'-240' => '(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz',
			'-210' => '(GMT -3:30) Newfoundland',
			'-180' => '(GMT -3:00) Brazil, Buenos Aires, Georgetown',
			'-120' => '(GMT -2:00) Mid-Atlantic',
			'-60'  => '(GMT -1:00) Azores, Cape Verde Islands',
			'0'    => '(GMT) Western Europe Time, London, Lisbon, Casablanca',
			'60'   => '(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris',
			'120'  => '(GMT +2:00) Kaliningrad, South Africa',
			'180'  => '(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg',
			'210'  => '(GMT +3:30) Tehran',
			'240'  => '(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi',
			'270'  => '(GMT +4:30) Kabul',
			'300'  => '(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
			'330'  => '(GMT +5:30) Bombay, Calcutta, Madras, New Delhi',
			'345'  => '(GMT +5:45) Kathmandu',
			'360'  => '(GMT +6:00) Almaty, Dhaka, Colombo',
			'420'  => '(GMT +7:00) Bangkok, Hanoi, Jakarta',
			'480'  => '(GMT +8:00) Beijing, Perth, Singapore, Hong Kong',
			'540'  => '(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
			'570'  => '(GMT +9:30) Adelaide, Darwin',
			'600'  => '(GMT +10:00) Eastern Australia, Guam, Vladivostok',
			'660'  => '(GMT +11:00) Magadan, Solomon Islands, New Caledonia',
			'720'  => '(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka'
			));

		$this->view->set('email',    $session->get('email'));
		$this->view->set('timezone', $session->get('timezone'));

		$email           = isset($_POST['email'])            ? $_POST['email']            : '';
		$password        = isset($_POST['password'])         ? $_POST['password']         : '';
		$passwordRepeat  = isset($_POST['password-repeat'])  ? $_POST['password-repeat']  : '';
		$timeZone        = isset($_POST['timezone'])         ? $_POST['timezone']         : '';
		$currentPassword = isset($_POST['current-password']) ? $_POST['current-password'] : '';

		if ( !empty($_POST) ) {
			$success = false;
			$error   = array();

			try {
				$auth = $this->app->getSingleton('auth');

				$auth->authenticate($session->get('email'), $currentPassword);

				if ( $password || $passwordRepeat ) {
					if ( $password != $passwordRepeat ) {
						$error[] = 'The provided passwords don\'t match, please try again.';

						$this->view->set('error-password',        true);
						$this->view->set('error-password-repeat', true);
					} else {
						$auth->setPassword($userId, $password);
					}
				}

				if ( !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
					$error[] = 'Please provide a valid email address.';
				}

				if ( !$error ) {
					$dbh = $this->app->getSingleton('pdo')->getHandle();

					$sth = $dbh->prepare('
						UPDATE users SET
							email      = :email,
							timezone   = :timezone,
							updated_at = UTC_TIMESTAMP()
						WHERE
							id = :id
						LIMIT 1
						;');

					$sth->bindParam(':id',       $userId);
					$sth->bindParam(':email',    $email);
					$sth->bindParam(':timezone', $timeZone);

					$sth->execute();

					$success = 'Your account has been updated.';

					$session->set('email',    $email);
					$session->set('timezone', $timeZone);
				}
			} catch ( \Exception $e ) {
				switch ( $e->getCode() ) {
					case $auth::PASSWORD_INCORRECT:
						$error[] = 'Current password incorrect, please try again';

						$this->view->set('error-current-password', true);

						break;
					default:
						$error[] = 'An unknown error ocurred.' . $e->getMessage();
				}
			}

			if ( $success ) {
				$this->view->set('success', $success);
			} else {
				$this->view->set('error', implode('<br>', $error));
			}

			$this->view->set('email',    $email);
			$this->view->set('timezone', $timeZone);
		}
	}
}
