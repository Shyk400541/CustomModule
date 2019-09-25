<?php

namespace Drupal\my_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
use Drupal\ckeditor\Plugin\Editor;

class MyForm extends FormBase {

	/**
	*{@inheritdoc}
	*/
	public function getFormId(){

		return 'my_registery';
	}

	/**
	*{@inheritdoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state){

		$form['first_name'] = ['#type'=>'textfield',
								'#title'=>'First Name',];

		$form['last_name'] = ['#type'=>'textfield',
								'#title'=>'Last Name'];

		$form['subject'] = ['#type'=> 'textarea',
							'#title'=>'Subject'];

		$form['message'] = ['#type'=>'text_format',
							'#title'=>'Message',];

		$form['email'] = ['#type'=>'email',
							'#title'=>'Email'];

		$form['submit'] = ['#type'=>'submit',
							'#value'=>$this->t('Submit')];

		return $form;
	}

	/**
	*{@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state){

		if(empty($form_state->getValue('first_name'))){
		$form_state->setErrorByName('first_name', $this->t('Field "First Name" must be not empty'));
		return;
		}

		if(empty($form_state->getValue('last_name'))){
		$form_state->setErrorByName('last_name', $this->t('Field "Last Name" must be not empty'));
		return;
		}

		if(empty($form_state->getValue('subject'))){
		$form_state->setErrorByName('subject', $this->t('Field "Subject" must be not empty'));
		return;
		}

		if(empty($form_state->getValue('message'))){
		$form_state->setErrorByName('message', $this->t('Field "Message" must be not empty'));
		return;
		}

		if(empty($form_state->getValue('email'))){
		$form_state->setErrorByName('email', $this->t('Field "Email" must be not empty'));
		return;
		}

		else if((filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) == FALSE){
			$form_state->setErrorByName('email', $this->t('Your email is incorrect'));
			return;
		}
		//$this->messenger()->addStatus($this->t('Your email is @email',['@email'=>$form_state->getValue('email')]));
	}

	/**
	*{@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state){

		$this->send_mail($form, $form_state);
		
		$this->create_contact($form, $form_state);

		return;
	}

	/**
 	* Implements hook_mail().
 	*/
	public function my_form_mail($key, &$message, $params){

		$options = ['langcode' => $message['langcode'],];

		switch ($key) {
			case 'submit':
				$message['from'] = \Drupal::config('system.site')->get('mail');
				$message['subject'] = Html::escape($params['subject']);
				$message['body'][] = Html::escape($params['message']);
				break;
		}
		return;
	}

	public function create_contact(array &$form, FormStateInterface $form_state){

		$email = $form_state->getValue('email');
		$firstname = $form_state->getValue('first_name');
		$lastname = $form_state->getValue('last_name');

		$url = "https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/".$email."/?hapikey=ed189448-eb49-4e06-bbcc-b0f68d5fa75d";

		$data = array ('properties' => [
			['property' => 'firstname',
			'value' => $firstname],
			['property' => 'lastname',
			'value' => $lastname]
		]);

		if(($json = json_encode($data, true)) == FALSE){
			$this->messenger()->addStatus($this->t('Json encoding is fail'));
		}

		$request = \Drupal::httpClient()->post($url.'&_format=json',['headers' => ['Content-Type' => 'application/json'], 'body' => $json]);
		//$response = json_decode($request->getBody());
		//$this->messenger()->addStatus($this->t($response));
		return;
	}

	public function send_mail(array &$form, FormStateInterface $form_state){

		$mailManager = \Drupal::service('plugin.manager.mail');
		$module = 'my_form';
		$key = 'submit';
		$to = 'killyouall@bk.ru';
		$params['message'] = $form_state->getValue('message');
		$params['subject'] = $form_state->getValue('subject');
		$langcode = 'en';
		$send = true;

		$result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
		if($result['result'] != true) {
			$this->messenger()->addStatus($this->t('There was a problem sending your email'));
		}
		
		else {
			$this->messenger()->addStatus($this->t('Your email has been sent'));
			$file = 'log.txt';
			$message = date('l jS \of F Y h:i:s A').''.'Mail has been send to '.$form_state->getValue('email');
			file_put_contents($file, $message, FILE_APPEND);
		}

		return;
	}
}