<?php
class ControllerExtensionPaymentRemitano extends Controller
{
    //Create index method. Index method is called automatically if no parameters are passed, check this video tutorial for details https://www.youtube.com/watch?v=X6bsMmReT-4.
    //In payment extension you don't need to pass any parameter in index() method.
    public function index() {
        //Loads the language file by which the varaibles of language file are accessible in twig files
        $this->load->language('extension/payment/remitano');
        //Text to show when it is in test mode.
        $data['text_testmode'] = $this->language->get('text_testmode');
        //Text to show for the button.
        $data['button_confirm'] = $this->language->get('button_confirm');
        //Get the configured value, and find when it is on test mode or not.
        $data['testmode'] = $this->config->get('payment_remitano_test');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

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

      $body  = json_encode( array(
        'coin_currency' => 'usdt',
        'coin_amount' => $order_info['total'],
        'cancelled_or_completed_callback_url' => $this->url->link('extension/payment/remitano/callback', '', true),
        'cancelled_or_completed_redirect_url' => $this->url->link('extension/payment/remitano/callback', '', true),
        'payload' => array(
          'order_id' => $order_id
        ),
        'description' => "Order #$order_id from {$_SERVER['SERVER_NAME']}",
      ) );

      $result = $this->model_extension_payment_remitano->request_create_merchant_charge($body);
      if ($result['sucess']) {
        $this->response->redirect($result['response']['remitano_payment_url']);
      } else {
        print_r($result['response']);
      }
    }

    public function callback()
    {
      $this->load->model('extension/payment/remitano');
      $this->load->model('checkout/order');
      $merchant_charge_id = $_GET['remitano_id'];
      $result = $this->model_extension_payment_remitano->request_get_merchant_charge($merchant_charge_id);

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
