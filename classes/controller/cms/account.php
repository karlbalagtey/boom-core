<?php

/**
* Controller to handle CMS account related actions - login, logout, reset password, etc.
* Most of the actual work is done by the Auth module but this controller adds extra stuff like logging and displaying the templates.
* Although this is part of the CMS, and part of the /cms controller directory it extends the site controller.
* This is because the cms controller requires a user to be logged in.
* Which would just be silly here.
*
* @package Controller
* @author Hoop Associates	www.thisishoop.com	mail@hoopassociates.co.uk
* @copyright 2011, Hoop Associates Ltd
*/
class Controller_Cms_Account extends Kohana_Controller
{	
	/**
	* Log a user into the CMS
	* @uses Auth::login()
	* @return void
	*/
	public function action_login() 
	{
		$protocol = $this->request->protocol();
		
		if (Auth::instance()->logged_in())
		{
			//You're already logged in dummy, just go away.
			$uri = '/';
			$cms_uri = Cookie::get( 'cms_uri' );
			
			if ($cms_uri != 'cms')
				$uri .= $cms_uri;

			$this->request->redirect( $uri );
		}
		
		// Gather form data.
		$email = Arr::get( $_POST, 'email', null );
		$password = Arr::get( $_POST, 'password', null );
		$persist = Arr::get( $_POST, 'persist', false );
		$msg = '';
		
		if ($email && $password)
		{
			// A nice little touch to save Hoop people's fingers.
			// If there's no @ in the email address add @hoopassociates.co.uk to the end.
			if (!strstr( $email, '@' ))
				$email .= "@hoopassociates.co.uk";
				
			// Do this now and we can pass it to Auth::login() so we only have to query the database once.
			$person = ORM::factory('person')->where( 'emailaddress', '=', $email )->find();
			
			// $this->auth does the actual logging in, we just do some cleaning up after.
			if (Auth::instance()->login( $person, $password, $persist ))
			{				
				// Log the activity, so we can see what everyone's been getting up to.
				Model_Activitylog::log( $person, 'login' );

				// Where shall we send them next?
				$uri = '/';
				$cms_uri = Cookie::get( 'cms_uri' );
				
				if ($cms_uri != 'cms')
					$uri .= $cms_uri;

				// Be gone with you.
				$this->request->redirect( $uri );
			} else
				$msg = "We couldn't find your account.	Please try again or <a class=\"resetpasswordlink\" href=\"/cms/forgot-password\">click here</a> to reset your password.";
		}
		else
		{
			if ($email && !$password)
				$msg = "Please enter your password.";
			else if ($password && !$email)
				$msg = "Sorry, you gave us your password but we don't know who you are.";
		}
		
		//We've not given up already? Oh well, best show them a template I guess.
		// Login form
		$template = View::factory( 'cms/tpl_login' );
		$template->client = Kohana::$config->load('core')->get('client_name');
		$template->msg = $msg;
		$template->email = $email;
		$template->persist = $persist;
		echo $template;
		exit();
	}
	
	/**
	* Log the user out of the CMS
	*
	* @uses Auth::logout()
	* @return void
	*/
	public function action_logout()
	{
		if (Auth::instance()->logged_in())
		{
			Model_Activitylog::log( Auth::instance()->get_user(), 'logout' );
		
			Auth::instance()->logout(TRUE);
		}
		
		$this->request->redirect( '/' );
	}
	
	/**
	* Reset the user's CMS password, the muppet's forgotten it.
	*
	* @uses Text_Password
	* @return void
	*/
	public function action_reset()
	{
		$email = $this->input->post( 'email', null, true );
		$client = $this->input->get( 'client', 'Default client name', true );
		$msg = '';
		$cache = Cache::Instance();

		if (!empty($_POST)) {
			// find the user with this email address, return an error if we can't find them, make sure we do the search case insensitively

			if (!$person = $cache->get( 'user_person_by_email_' . $email ))
				$person = O::fa('person')->find_by_emailaddress(strtolower( $email ));
			
			if ($person->emailaddress) {	
				// Log that someone's done something
				Model_Activitylog::log( $this->request->client_ip, $this->person, 'password reset' );

				// Create a new password and update the user.
				include 'Text/Password.php';
				$tp = new Text_Password();
				$passwd = $tp->create(8);
				$person->password =  '{SHA}' . base64_encode(sha1($passwd, true));
				$person->consecutive_failed_login_counter = 0;
				$person->save_activeversion();
				$to = $person->emailaddress;
				$subject = Kohana::config('core.clientnamelong') . ' CMS: Your password has been reset';
				$message = new View('cms/email/tpl_login_reset');
				$headers = 'From: hoopmaster@hoopassociates.co.uk' . "\r\n" .
							'Reply-To: mail@hoopassociates.co.uk' . "\r\n" ;
				mail($to, $subject, $message, $headers);
				
				// Make sure we update the cache.
				$cache->set( 'user_person_by_email_' . $email, $person, 'user_setting' );
				$cache->set( 'user_person_by_rid_' . $person->rid, $person, 'user_setting' );

				$msg = 'Your password will be emailed to you shortly.  If you do not receive it today, contact the hoop team for assistance.';
				$this->passwordresetflag = 1;
			} else
				$msg = "Sorry, we don't seem to know that one.	Either try again or contact the hoop team for assistance.";
		}
		
		// Main template.
		$v = new View( 'cms/standard_template');
		$v->set_global( 'title', 'Password reset' );
		// Password reset subtemplate
		$v->subtpl = new View( 'cms/templates/tpl_password' );
		$v->subtpl->msg = $msg;
		$v->subtpl->email = $email;
		
		$v->render( true );
	}
}
