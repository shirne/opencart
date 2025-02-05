<?php
namespace Opencart\Catalog\Controller\Mail;
class Gdpr extends \Opencart\System\Engine\Controller {
	// catalog/model/account/gdpr/addGdpr
	public function index(string &$route, array &$args, mixed &$output): void {
		// $args[0] $code
		// $args[1] $email
		// $args[2] $action

		$this->load->language('mail/gdpr');

		$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		if ($this->config->get('config_logo')) {
			$data['logo'] = $this->config->get('config_url') . 'image/' . html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
		} else {
			$data['logo'] = '';
		}

		$data['text_request'] = $this->language->get('text_' . $args[2]);

		$data['button_confirm'] = $this->language->get('button_' . $args[2]);

		$data['confirm'] = $this->url->link('information/gdpr.success', 'language=' . $this->config->get('config_language') . '&code=' . $args[0], true);

		$data['ip'] = $this->request->server['REMOTE_ADDR'];

		$data['store_name'] = $store_name;
		$data['store_url'] = $this->config->get('config_url');

		if ($this->config->get('config_mail_engine')) {
			$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($args[1]);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($store_name);
			$mail->setSubject(sprintf($this->language->get('text_subject'), $store_name));
			$mail->setHtml($this->load->view('mail/gdpr', $data));
			$mail->send();
		}
	}

	// catalog/model/account/gdpr/editStatus/after
	public function remove(string &$route, array &$args, mixed &$output): void {
		if (isset($args[0])) {
			$gdpr_id = $args[0];
		} else {
			$gdpr_id = 0;
		}

		if (isset($args[0])) {
			$status = $args[0];
		} else {
			$status = 0;
		}

		$this->load->model('account/gdpr');

		$gdpr_info = $this->model_account_gdpr->getGdpr($gdpr_id);

		if ($gdpr_info && $gdpr_info['action'] == 'remove' && $status == 3) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore($gdpr_info['store_id']);

			if ($store_info) {
				$this->load->model('setting/setting');

				$store_logo = html_entity_decode($this->model_setting_setting->getValue('config_logo', $store_info['store_id']), ENT_QUOTES, 'UTF-8');
				$store_name = html_entity_decode($store_info['name'], ENT_QUOTES, 'UTF-8');
				$store_url = $store_info['url'];
			} else {
				$store_logo = html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8');
				$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
				$store_url = HTTP_CATALOG;
			}

			// Send the email in the correct language
			$this->load->model('localisation/language');

			$language_info = $this->model_localisation_language->getLanguage($gdpr_info['language_id']);

			if ($language_info) {
				$language_code = $language_info['code'];
			} else {
				$language_code = $this->config->get('config_language');
			}

			// Load the language for any mails using a different country code and prefixing it so it does not pollute the main data pool.
			$this->language->load($language_code, 'mail', $language_code);
			$this->language->load('mail/gdpr_delete', 'mail', $language_code);

			// Add language vars to the template folder
			$results = $this->language->all('mail');

			foreach ($results as $key => $value) {
				$data[$key] = $value;
			}

			$subject = sprintf($this->language->get('mail_text_subject'), $store_name);

			$this->load->model('tool/image');

			if (is_file(DIR_IMAGE . $store_logo)) {
				$data['logo'] = $store_url . 'image/' . $store_logo;
			} else {
				$data['logo'] = '';
			}

			$this->load->model('customer/customer');

			$customer_info = $this->model_customer_customer->getCustomerByEmail($gdpr_info['email']);

			if ($customer_info) {
				$data['text_hello'] = sprintf($this->language->get('mail_text_hello'), html_entity_decode($customer_info['firstname'], ENT_QUOTES, 'UTF-8'));
			} else {
				$data['text_hello'] = sprintf($this->language->get('mail_text_hello'), $this->language->get('mail_text_user'));
			}

			$data['store_name'] = $store_name;
			$data['store_url'] = $store_url;
			$data['contact'] = $store_url . 'index.php?route=information/contact';

			if ($this->config->get('config_mail_engine')) {
				$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'));
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

				$mail->setTo($gdpr_info['email']);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($store_name);
				$mail->setSubject($subject);
				$mail->setHtml($this->load->view('mail/gdpr_delete', $data));
				$mail->send();
			}
		}
	}
}
