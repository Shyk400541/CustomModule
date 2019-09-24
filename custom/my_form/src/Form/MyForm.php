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
		$this->messenger()->addStatus($this->t('Field "First Name" must be not empty'));
		}

		if(empty($form_state->getValue('last_name'))){
		$this->messenger()->addStatus($this->t('Field "Last Name" must be not empty'));
		}

		if(empty($form_state->getValue('subject'))){
		$this->messenger()->addStatus($this->t('Field "Subject" must be not empty'));
		}

		if(empty($form_state->getValue('message'))){
		$this->messenger()->addStatus($this->t('Field "Message" must be not empty'));
		}

		if(empty($form_state->getValue('email'))){
		$this->messenger()->addStatus($this->t('Field "Email" must be not empty'));
		}
		else if(filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)){
		$this->messenger()->addStatus($this->t('Your email is @email',['@email'=>$form_state->getValue('email')]));
		}
		else $this->messenger()->addStatus($this->t('Your email is incorrect'));

	}

	/**
	*{@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state){

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
			return;
		}
		
		else $this->messenger()->addStatus($this->t('Your email has been sent'));
		return;
	}

	public function my_form_mail($key, &$message, $params){

		$options = ['langcode' => $message['langcode'],];

		switch ($key) {
			case 'submit':
				$message['from'] = \Drupal::config('system.site')->get('mail');
				$message['subject'] = Html::escape($params['subject']);
				$message['body'][] = Html::escape($params['message']);
				break;
		}
	}
}