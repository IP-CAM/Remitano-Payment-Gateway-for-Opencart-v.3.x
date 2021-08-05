<?php
class ControllerExtensionPaymentRemitano extends Controller {
	public function index() {
		$this->load->language('extension/payment/remitano');
		$this->load->model('extension/payment/remitano');
		$data['text_testmode'] = $this->language->get('text_testmode');
		$data['text_does_not_support_currency'] = $this->language->get('text_does_not_support_currency');
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['testmode'] = $this->config->get('payment_remitano_test');
		$order_id = $this->session->data['order_id'];
		$order_info = $this->model_checkout_order->getOrder($order_id);
		$currency_code = $order_info['currency_code'];
		$data['current_currency_is_supported'] = $this->model_extension_payment_remitano->isCurrencySupported($currency_code);
		$this->load->model('checkout/order');

		$data['action'] = $this->url->link('extension/payment/remitano/checkout', '', true);

		return $this->load->view('extension/payment/remitano', $data);
	}

	public function checkout() {
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/remitano');
		$this->load->language('extension/payment/remitano');

		$order_id = $this->session->data['order_id'];

		if(!isset($order_id)) {
			return false;
		}

		$order_info = $this->model_checkout_order->getOrder($order_id);

		$currency_code = $order_info['currency_code'];

		$submission_data = array(
			'cancelled_or_completed_callback_url' => $this->url->link('extension/payment/remitano/callback', '', true),
			'cancelled_or_completed_redirect_url' => $this->url->link('extension/payment/remitano/callback', '', true),
			'payload' => array(
				'order_id' => $order_id
			),
			'description' => "Order #$order_id from {$_SERVER['SERVER_NAME']}"
		);

		if ($currency_code == 'USDT') {
			$submission_data['coin_currency'] = 'usdt';
			$submission_data['coin_amount'] = $order_info['total'];
		} else {
			$submission_data['fiat_currency'] = $currency_code;
			$submission_data['fiat_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		}

		$body = json_encode($submission_data, true);

		$result = $this->model_extension_payment_remitano->requestCreateMerchantCharge($body);

		if ($result['sucess']) {
			$this->response->redirect($result['response']['remitano_payment_url']);
		} else {
			print_r($result);
		}
	}

	public function callback() {
		$this->load->model('extension/payment/remitano');
		$this->load->model('checkout/order');
		$merchant_charge_id = $_GET['remitano_id'];
		$result = $this->model_extension_payment_remitano->requestGetMerchantCharge($merchant_charge_id);

		if (!$result['sucess']) {
			print_r($result['response']);
			return;
		}

		$merchant_charge = $result['response'];
		$order_id = $merchant_charge['payload']['order_id'];

		$order_status_id = $this->config->get('config_order_status_id');
		$callback_link = $this->url->link('checkout/success');
		switch ($merchant_charge['status']) {
			case 'cancelled':
			$order_status_id = $this->config->get('payment_remitano_canceled_status_id');
			// If order cancel, redirect to the checkout page again so user can choose another payment method
			$callback_link = $this->url->link('checkout/checkout');
			break;
			case 'completed':
			$order_status_id = $this->config->get('payment_remitano_completed_status_id');
			$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
			break;
		}

		$this->response->redirect($callback_link);
	}
}
