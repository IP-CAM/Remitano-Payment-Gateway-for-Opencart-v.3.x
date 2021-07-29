<?php
class ControllerExtensionPaymentRemitano extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/remitano');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_remitano', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/remitano', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['action'] = $this->url->link('extension/payment/remitano', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_remitano_key'])) {
			$data['payment_remitano_key'] = $this->request->post['payment_remitano_key'];
		} else {
			$data['payment_remitano_key'] = $this->config->get('payment_remitano_key');
		}

		if (isset($this->request->post['payment_remitano_secret'])) {
			$data['payment_remitano_secret'] = $this->request->post['payment_remitano_secret'];
		} else {
			$data['payment_remitano_secret'] = $this->config->get('payment_remitano_secret');
		}

		if (isset($this->request->post['payment_remitano_test'])) {
			$data['payment_remitano_test'] = $this->request->post['payment_remitano_test'];
		} else {
			$data['payment_remitano_test'] = $this->config->get('payment_remitano_test');
		}

		if (isset($this->request->post['payment_remitano_canceled_status_id'])) {
			$data['payment_remitano_canceled_status_id'] = $this->request->post['payment_remitano_canceled_status_id'];
		} else {
			$data['payment_remitano_canceled_status_id'] = $this->config->get('payment_remitano_canceled_status_id');
		}

		if (isset($this->request->post['payment_remitano_completed_status_id'])) {
			$data['payment_remitano_completed_status_id'] = $this->request->post['payment_remitano_completed_status_id'];
		} else {
			$data['payment_remitano_completed_status_id'] = $this->config->get('payment_remitano_completed_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_remitano_status'])) {
			$data['payment_remitano_status'] = $this->request->post['payment_remitano_status'];
		} else {
			$data['payment_remitano_status'] = $this->config->get('payment_remitano_status');
		}

		if (isset($this->request->post['payment_remitano_sort_order'])) {
			$data['payment_remitano_sort_order'] = $this->request->post['payment_remitano_sort_order'];
		} else {
			$data['payment_remitano_sort_order'] = $this->config->get('payment_remitano_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/remitano', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/remitano')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_remitano_key']) {
			$this->error['key'] = $this->language->get('error_key');
		}

		if (!$this->request->post['payment_remitano_secret']) {
			$this->error['secret'] = $this->language->get('error_secret');
		}

		return !$this->error;
	}
}
