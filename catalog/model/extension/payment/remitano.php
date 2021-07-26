<?php
class ModelExtensionPaymentRemitano extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/remitano');

		$method_data = array(
			'code'       => 'remitano',
			'title'      => $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_remitano_sort_order')
		);

		return $method_data;
	}

	public function request_create_merchant_charge($body) {
		$this->load->language('extension/payment/remitano');

		$curl = curl_init();
		if (!$this->config->get('payment_remitano_test')) {
			curl_setopt($curl, CURLOPT_URL, 'https://api.remitano.com/api/v1/merchant/merchant_charges');
		} else {
			curl_setopt($curl, CURLOPT_URL, 'https://api.remidemo.com/api/v1/merchant/merchant_charges');
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$target = '/api/v1/merchant/merchant_charges';
		$headers = $this->build_header('POST', $target, $body);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);
		$success = true;
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($http_code != 201) {
			$success = false;
		}
		curl_close($curl);

		return array(
			'sucess' => $success,
			'response' => json_decode($response, true)
		);
	}

	public function request_get_merchant_charge($remitano_id) {
		$curl = curl_init();

		if (!$this->config->get('payment_remitano_test')) {
			curl_setopt($curl, CURLOPT_URL, "https://api.remitano.com/api/v1/merchant/merchant_charges/{$remitano_id}");
		} else {
			curl_setopt($curl, CURLOPT_URL, "https://api.remidemo.com/api/v1/merchant/merchant_charges/{$remitano_id}");
		}

		$target = "/api/v1/merchant/merchant_charges/{$remitano_id}";
		$headers = $this->build_header('GET', $target, '');

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($curl);
		$success = true;
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($http_code != 200) {
			$success = false;
		}

		curl_close($curl);

		return array(
			'sucess' => $success,
			'response' => json_decode($response, true)
		);
	}

	public function build_header($method, $target, $body) {
		$content_type = 'application/json';
		$md5 = $this->calculate_md5($body);
		$date = $this->get_date();
		$canonical_string = $this->get_canonical_string($method, $content_type, $md5, $target, $date);
		$signature = $this->get_hmac_signature($canonical_string);
		$authorization = "APIAuth {$this->config->get('payment_remitano_key')}:$signature";

		return array(
			"date: {$date}" ,
			"accept: {$content_type}" ,
			"content-type: {$content_type}" ,
			"content-md5: {$md5}" ,
			"authorization: {$authorization}"
		);
	}

	public function get_date($timestamp = null) {
		$timestamp = empty($timestamp) ? time() : $timestamp;
		return gmdate("D, d M Y H:i:s", $timestamp)." GMT";
	}

	public function calculate_md5($body) {
		return base64_encode( md5( $body, true ) );
	}

	public function get_canonical_string($method, $type, $md5, $target, $date) {
		return join(",", array( $method, $type, $md5, $target, $date ));
	}

	public function get_hmac_signature($canonical_string) {
		return trim( base64_encode( hash_hmac( 'sha1', $canonical_string, $this->config->get('payment_remitano_secret'), true ) ) );
	}
}
