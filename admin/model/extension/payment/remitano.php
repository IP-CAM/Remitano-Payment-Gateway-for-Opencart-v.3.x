<?php
class ModelExtensionPaymentRemitano extends Model {
	public function hasSupportedCurrency() {
		$this->load->model('localisation/currency');
		$oc_currencies = array_column($this->model_localisation_currency->getCurrencies(), 'code');

		$remitano_supported_currencies = array("AED", "ARS", "AUD", "BND", "BOB", "BRL", "BYN", "CAD", "CDF", "CFA",
						"CHF", "CNY", "COP", "DKK", "DZD", "EUR", "GBP", "GHS", "HKD", "IDR", "ILS", "INR", "JPY",
						"KES", "KRW", "LAK", "MMK", "MXN", "MYR", "NAD", "NGN", "NOK", "NPR", "NZD", "OMR", "PEN",
						"PHP", "PKR", "PLN", "QAR", "RUB", "RWF", "SEK", "SGD", "THB", "TRY", "TWD", "TZS", "UAH",
						"UGX", "USD", "VES", "VND", "XAF", "ZAR", "ZMW", "USDT");

		return count(array_intersect($oc_currencies, $remitano_supported_currencies)) > 0;
	}
}
