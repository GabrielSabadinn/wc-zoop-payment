<?php
if (!defined('ABSPATH')) {
    error_log('WC Hubmais PIX: ABSPATH não definido, encerrando');
    exit;
}

class WC_Gateway_Zoop_PIX extends WC_Payment_Gateway {
    public function __construct() {
        error_log('WC Hubmais PIX: Entrando no construtor');
        $this->id = 'zoop_pix';
        $this->method_title = __('PIX Hubmais', 'wc-zoop-payments');
        $this->method_description = __('Pague com PIX via API Zoop', 'wc-zoop-payments');
        $this->title = $this->get_option('title', __('PIX', 'wc-zoop-payments'));
        $this->has_fields = true;
        $this->supports = ['products'];

        error_log('WC Hubmais PIX: ID do gateway: ' . $this->id);
        error_log('WC Hubmais PIX: Título: ' . $this->title);

        $this->init_form_fields();
        error_log('WC Hubmais PIX: Campos de formulário inicializados');

        $this->init_settings();
        error_log('WC Hubmais PIX: Configurações inicializadas');

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->description = $this->get_option('description', __('Pague instantaneamente com PIX via nossa API Hubmais segura', 'wc-zoop-payments'));
        error_log('WC Hubmais PIX: Habilitado: ' . $this->enabled);
        error_log('WC Hubmais PIX: Descrição: ' . $this->description);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        error_log('WC Hubmais PIX: Ações registradas');
    }

    public function init_form_fields() {
        error_log('WC Hubmais PIX: Inicializando campos de formulário');
        $this->form_fields = [
            'enabled' => [
                'title' => __('Ativar/Desativar', 'wc-zoop-payments'),
                'type' => 'checkbox',
                'label' => __('Ativar PIX Zoop', 'wc-zoop-payments'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Título', 'wc-zoop-payments'),
                'type' => 'text',
                'description' => __('Título exibido no checkout', 'wc-zoop-payments'),
                'default' => __('PIX', 'wc-zoop-payments')
            ],
            'description' => [
                'title' => __('Descrição', 'wc-zoop-payments'),
                'type' => 'textarea',
                'description' => __('Descrição exibida no checkout', 'wc-zoop-payments'),
                'default' => __('Pague instantaneamente com PIX via nossa API Hubmais segura', 'wc-zoop-payments')
            ]
        ];
        error_log('WC Hubmais PIX: Campos de formulário definidos: ' . print_r($this->form_fields, true));
    }

    public function payment_fields() {
        error_log('WC Hubmais PIX: Renderizando campos de pagamento');
        ?>
        <div id="zoop-pix-form">
            <p><?php echo esc_html($this->description); ?></p>
            <p><?php _e('Após realizar o pedido, você receberá um QR Code para completar o pagamento via PIX.', 'wc-zoop-payments'); ?></p>
        </div>
        <?php
        error_log('WC Zoop PIX: Campos de pagamento renderizados');
    }

    public function process_payment($order_id) {
        error_log('WC Zoop PIX: Processando pagamento para o pedido #' . $order_id);
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('WC Zoop PIX: Pedido #' . $order_id . ' não encontrado');
            wc_add_notice(__('Erro: Pedido não encontrado.', 'wc-zoop-payments'), 'error');
            return;
        }
        error_log('WC Zoop PIX: Total do pedido: ' . $order->get_total());

        $payload = [
            'amount' => floatval($order->get_total()),
            'description' => 'Pagamento PIX para o pedido #' . $order_id,
            'payment_type' => 'pix'
        ];

        error_log('WC Zoop PIX: Enviando payload: ' . json_encode($payload, JSON_PRETTY_PRINT));
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wc_add_notice(__('Payload da Requisição API: ', 'wc-zoop-payments') . '<pre>' . esc_html(json_encode($payload, JSON_PRETTY_PRINT)) . '</pre>', 'notice');
        }

        $response = wp_remote_post('http://localhost:9099/api/pix', [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        error_log('WC Zoop PIX: Requisição API enviada para http://localhost:9099/api/pix');

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WC Zoop PIX: Erro WP na API: ' . $error_message);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Erro na Resposta da API: ', 'wc-zoop-payments') . esc_html($error_message), 'error');
            } else {
                wc_add_notice(__('Erro ao processar o pagamento. Tente novamente.', 'wc-zoop-payments'), 'error');
            }
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('WC Zoop PIX: Código de resposta da API: ' . $response_code);
        error_log('WC Zoop PIX: Corpo da resposta da API: ' . $response_body);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            wc_add_notice(__('Resposta da API: ', 'wc-zoop-payments') . '<pre>' . esc_html($response_body) . '</pre>', 'notice');
        }

        $body = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WC Zoop PIX: Erro ao decodificar JSON: ' . json_last_error_msg());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Erro na Resposta da API: JSON inválido. Resposta bruta: ', 'wc-zoop-payments') . esc_html($response_body), 'error');
            } else {
                wc_add_notice(__('Erro ao processar o pagamento. Tente novamente.', 'wc-zoop-payments'), 'error');
            }
            return;
        }

        if ($response_code == 201 && isset($body['qr_code'])) {
            error_log('WC Zoop PIX: Pagamento iniciado para o pedido #' . $order_id);
            $order->update_status('on-hold', __('Aguardando confirmação do pagamento PIX. QR Code gerado.', 'wc-zoop-payments'));
            $order->add_order_note('Pagamento PIX iniciado. QR Code: ' . $body['qr_code']);
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Pagamento Iniciado. ID do Pedido: ', 'wc-zoop-payments') . $order_id . '. <a href="' . esc_url($this->get_return_url($order)) . '">Clique aqui para ver os detalhes do pedido.</a>', 'success');
                return; // Permanece na página de checkout para mostrar JSON
            } else {
                return [
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                ];
            }
        } else {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Erro desconhecido', 'wc-zoop-payments');
            error_log('WC Zoop PIX: Pagamento falhou: ' . $error_message);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Erro na Resposta da API: ', 'wc-zoop-payments') . '<pre>' . esc_html(json_encode($body, JSON_PRETTY_PRINT)) . '</pre>', 'error');
            } else {
                wc_add_notice(__('Pagamento falhou: ', 'wc-zoop-payments') . esc_html($error_message), 'error');
            }
            return;
        }
    }
}
?>