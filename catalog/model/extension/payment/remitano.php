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

	public function requestCreateMerchantCharge($body) {
		$this->load->language('extension/payment/remitano');
		
		$target = '/api/v1/merchant/merchant_charges';
		$curl = curl_init($this->buildUrl($target));
		$headers = $this->buildHeader('POST', $target, $body);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

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

	public function requestGetMerchantCharge($remitano_id) {
		$target = "/api/v1/merchant/merchant_charges/{$remitano_id}";
		$curl = curl_init($this->buildUrl($target));
		$headers = $this->buildHeader('GET', $target, '');

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

	private function buildUrl($target) {
		if (!$this->config->get('payment_remitano_test')) {
			$apiUrl = 'https://api.remitano.com';
		} else {
			$apiUrl = 'https://api.remidemo.com';
		}

		return $apiUrl . $target;
	}

	private function buildHeader($method, $target, $body) {
		$content_type = 'application/json';
		$md5 = $this->calculateMd5($body);
		$date = $this->getDate();
		$canonical_string = $this->getCanonicalString($method, $content_type, $md5, $target, $date);
		$signature = $this->getHmacSignature($canonical_string);
		$authorization = "APIAuth {$this->config->get('payment_remitano_key')}:$signature";

		return array(
			"date: {$date}" ,
			"accept: {$content_type}" ,
			"content-type: {$content_type}" ,
			"content-md5: {$md5}" ,
			"authorization: {$authorization}"
		);
	}

	private function getDate($timestamp = null) {
		$timestamp = empty($timestamp) ? time() : $timestamp;
		return gmdate("D, d M Y H:i:s", $timestamp)." GMT";
	}

	private function calculateMd5($body) {
		return base64_encode(md5($body, true));
	}

	private function getCanonicalString($method, $type, $md5, $target, $date) {
		return join(",", array($method, $type, $md5, $target, $date));
	}

	private function getHmacSignature($canonical_string) {
		return trim(base64_encode(hash_hmac('sha1', $canonical_string, $this->config->get('payment_remitano_secret'), true)));
	}
}
