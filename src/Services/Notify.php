<?php

namespace App\Services;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class Notify
{
	private $sms;
	private $mailer;
	
	public function __construct(MailerInterface $mailer)
	{
	$this->mailer = $mailer;
	$this->sender = new Address('support@mysoleas.com', 'MYSOLEAS');
	}

	public function welcome($user){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($user->getEmail())
										->subject('Welcome')
										->htmlTemplate('email/welcome.html.twig')
										->context(array('username' => $user->getUsername()))
		;
		$this->mailer->send($email);
	}

	public function newAffiliate($user){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($user->getEmail())
										->subject('Please Confirm your Email')
										->htmlTemplate('email/new_affiliate.html.twig')
										->context(array('user' => $user))
		;
		$this->mailer->send($email);
	}

	public function newOrder($order, $distribution){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($order->getEmail())
										->subject('New Order')
										->htmlTemplate('email/order.html.twig')
										->context(array('cart' => $order, 'type' => 'CUSTOMER'))
		;
		$this->mailer->send($email);
		foreach($distribution as $seller){
			$ownerEmail =  (new TemplatedEmail())->from($this->sender)
											->to($seller['user']->getEmail())
											->subject('New Order')
											->htmlTemplate('email/order.html.twig')
											->context(array('cart' => $order, 'user' => $seller['user'], 'items' => $seller['items'], 'type' => 'OWNER'))
			;
			$this->mailer->send($ownerEmail);
		}
	}

	public function transaction($history){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($history->getUser()->getEmail())
										->subject('New Transaction')
										->htmlTemplate('email/transaction.html.twig')
										->context(array('history' => $history))
		;
		$this->mailer->send($email);
	}

	public function newMessage($user, $title, $message){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($user->getEmail())
										->subject($title)
										->htmlTemplate('email/message.html.twig')
										->context(array('user' => $user, 'title' => $title, 'message' => $message))
		;
		$this->mailer->send($email);
	}

	public function resetPassword($user, $url){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($user->getEmail())
										->subject('Reset Password')
										->htmlTemplate('email/passwordResetQuery.html.twig')
										->context(array('username' => $user->getUsername(), 'url' => $url))
		;
		$this->mailer->send($email);
	}

	public function passwordUpdate($user){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to($user->getEmail())
										->subject('Password Update')
										->htmlTemplate('email/passwordResetSuccess.html.twig')
										->context(array('username' => $user->getUsername()))
		;
		$this->mailer->send($email);
	}

	public function contact($name, $email, $subject, $message){
		$email =  (new TemplatedEmail())->from($this->sender)
										->to('infos@leutch.com')
										->subject($subject.' from '.$name)
										->htmlTemplate('email/contact.html.twig')
										->context(array('message' => $message, 'email' => $email))
		;
		$this->mailer->send($email);
	}
}