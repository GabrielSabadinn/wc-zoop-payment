<?php
if (!defined('ABSPATH')) {
    error_log('WC Zoop Cartão de Crédito: ABSPATH não definido, encerrando');
    exit;
}

class WC_Gateway_Zoop_Credit_Card extends WC_Payment_Gateway {
    public function __construct() {
        error_log('WC Zoop Cartão de Crédito: Entrando no construtor');
        $this->id = 'zoop_credit_card';
        $this->method_title = __('Cartão de Crédito Zoop', 'wc-zoop-payments');
        $this->method_description = __('Pague com cartão de crédito via API Zoop', 'wc-zoop-payments');
        $this->title = $this->get_option('title', __('Cartão de Crédito', 'wc-zoop-payments'));
        $this->has_fields = true;
        $this->supports = ['products'];

        error_log('WC Zoop Cartão de Crédito: ID do gateway: ' . $this->id);
        error_log('WC Zoop Cartão de Crédito: Título: ' . $this->title);
        error_log('WC Zoop Cartão de Crédito: Possui campos: ' . ($this->has_fields ? 'true' : 'false'));

        $this->init_form_fields();
        error_log('WC Zoop Cartão de Crédito: Campos de formulário inicializados');

        $this->init_settings();
        error_log('WC Zoop Cartão de Crédito: Configurações inicializadas');

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->description = $this->get_option('description', __('Pague com cartão de crédito via nossa API Zoop segura', 'wc-zoop-payments'));
        error_log('WC Zoop Cartão de Crédito: Habilitado: ' . $this->enabled);
        error_log('WC Zoop Cartão de Crédito: Descrição: ' . $this->description);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_footer', [$this, 'add_payment_scripts']);
        error_log('WC Zoop Cartão de Crédito: Ações registradas');
    }

    public function init_form_fields() {
        error_log('WC Zoop Cartão de Crédito: Inicializando campos de formulário');
        $this->form_fields = [
            'enabled' => [
                'title' => __('Ativar/Desativar', 'wc-zoop-payments'),
                'type' => 'checkbox',
                'label' => __('Ativar Cartão de Crédito Zoop', 'wc-zoop-payments'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Título', 'wc-zoop-payments'),
                'type' => 'text',
                'description' => __('Título exibido no checkout', 'wc-zoop-payments'),
                'default' => __('Cartão de Crédito', 'wc-zoop-payments')
            ],
            'description' => [
                'title' => __('Descrição', 'wc-zoop-payments'),
                'type' => 'textarea',
                'description' => __('Descrição exibida no checkout', 'wc-zoop-payments'),
                'default' => __('Pague com cartão de crédito via nossa API Zoop segura', 'wc-zoop-payments')
            ]
        ];
        error_log('WC Zoop Cartão de Crédito: Campos de formulário definidos: ' . print_r($this->form_fields, true));
    }

    public function add_payment_scripts() {
        error_log('WC Zoop Cartão de Crédito: Verificando se está na página de checkout');
        error_log('WC Zoop Cartão de Crédito: Resultado de is_checkout(): ' . (is_checkout() ? 'true' : 'false'));
        if (!is_checkout()) {
            error_log('WC Zoop Cartão de Crédito: Não está na página de checkout, ignorando scripts');
            return;
        }
        error_log('WC Zoop Cartão de Crédito: Adicionando scripts ao checkout');
        ?>
        <style>
            #zoop-credit-card-form .form-row {
                margin-bottom: 15px;
            }
            #zoop-credit-card-form input, #zoop-credit-card-form select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }
            #zoop-credit-card-form .form-row-inline {
                display: flex;
                gap: 10px;
            }
            #zoop-credit-card-form .form-col {
                flex: 1;
            }
            #zoop-credit-card-form label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
        </style>
        <script>
            console.log('WC Zoop Cartão de Crédito: JavaScript carregado na página de checkout');
            jQuery(document).ready(function($) {
                console.log('WC Zoop Cartão de Crédito: jQuery pronto, inicializando manipuladores de formulário');
                const cardNumber = $('#card_number');
                const expiryMonth = $('#card_expiry_month');
                const expiryYear = $('#card_expiry_year');
                const securityCode = $('#card_security_code');
                const cep = $('#enderCEP');

                if (cardNumber.length) {
                    console.log('WC Zoop Cartão de Crédito: Campo de número do cartão encontrado');
                    cardNumber.on('input', function() {
                        let value = $(this).val().replace(/\D/g, '');
                        value = value.replace(/(\d{4})/g, '$1 ').trim();
                        $(this).val(value);
                        console.log('WC Zoop Cartão de Crédito: Entrada do número do cartão: ' + value);
                    });
                } else {
                    console.log('WC Zoop Cartão de Crédito: Campo de número do cartão NÃO encontrado');
                }

                expiryMonth.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 2) value = value.slice(0, 2);
                    $(this).val(value);
                    console.log('WC Zoop Cartão de Crédito: Entrada do mês de expiração: ' + value);
                });

                expiryYear.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    $(this).val(value);
                    console.log('WC Zoop Cartão de Crédito: Entrada do ano de expiração: ' + value);
                });

                securityCode.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    $(this).val(value);
                    console.log('WC Zoop Cartão de Crédito: Entrada do CVV: ' + value);
                });

                cep.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 5) {
                        value = value.slice(0, 5) + '-' + value.slice(5, 8);
                    }
                    $(this).val(value);
                    console.log('WC Zoop Cartão de Crédito: Entrada do CEP: ' + value);
                });

                console.log('WC Zoop Cartão de Crédito: Adicionando campos ocultos de dispositivo');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_color_depth',
                    value: screen.colorDepth || 24
                }).appendTo('#zoop-credit-card-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_language',
                    value: navigator.language || 'pt-BR'
                }).appendTo('#zoop-credit-card-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_screen_height',
                    value: screen.height || 1080
                }).appendTo('#zoop-credit-card-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_screen_width',
                    value: screen.width || 1920
                }).appendTo('#zoop-credit-card-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_time_zone',
                    value: new Date().getTimezoneOffset()
                }).appendTo('#zoop-credit-card-form');
                console.log('WC Zoop Cartão de Crédito: Campos ocultos de dispositivo adicionados');
            });
        </script>
        <?php
    }

    public function payment_fields() {
        error_log('WC Zoop Cartão de Crédito: Renderizando campos de pagamento');
        error_log('WC Zoop Cartão de Crédito: ID da página atual: ' . get_the_ID());
        error_log('WC Zoop Cartão de Crédito: É página de checkout: ' . (is_checkout() ? 'true' : 'false'));
        ?>
        <div id="zoop-credit-card-form">
            <p><?php echo esc_html($this->description); ?></p>
            <div class="form-row">
                <label for="card_holder_name"><?php _e('Nome do Titular do Cartão', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="card_holder_name" name="card_holder_name" placeholder="João Silva" required>
            </div>
            <div class="form-row">
                <label for="card_number"><?php _e('Número do Cartão', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
            </div>
            <div class="form-row form-row-inline">
                <div class="form-col">
                    <label for="card_expiry_month"><?php _e('Mês de Expiração', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="card_expiry_month" name="card_expiry_month" placeholder="MM" maxlength="2" required>
                </div>
                <div class="form-col">
                    <label for="card_expiry_year"><?php _e('Ano de Expiração', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="card_expiry_year" name="card_expiry_year" placeholder="AAAA" maxlength="4" required>
                </div>
                <div class="form-col">
                    <label for="card_security_code"><?php _e('CVV', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="card_security_code" name="card_security_code" placeholder="123" maxlength="4" required>
                </div>
            </div>
            <div class="form-row">
                <label for="number_installments"><?php _e('Número de Parcelas', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <select id="number_installments" name="number_installments" required>
                    <option value="1"><?php _e('1x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="2"><?php _e('2x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="3"><?php _e('3x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="4"><?php _e('4x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="5"><?php _e('5x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="6"><?php _e('6x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="7"><?php _e('7x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="8"><?php _e('8x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="9"><?php _e('9x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="10"><?php _e('10x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="11"><?php _e('11x sem juros', 'wc-zoop-payments'); ?></option>
                    <option value="12"><?php _e('12x sem juros', 'wc-zoop-payments'); ?></option>
                </select>
            </div>
            <div class="form-row">
                <label for="enderCEP"><?php _e('CEP', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="enderCEP" name="enderCEP" placeholder="12345-678" maxlength="9" required>
            </div>
        </div>
        <?php
        error_log('WC Zoop Cartão de Crédito: Campos de pagamento renderizados');
    }

    public function process_payment($order_id) {
        error_log('WC Zoop Cartão de Crédito: Processando pagamento para o pedido #' . $order_id);
        error_log('WC Zoop Cartão de Crédito: URL atual: ' . esc_url_raw($_SERVER['REQUEST_URI']));
        error_log('WC Zoop Cartão de Crédito: Dados POST recebidos: ' . print_r($_POST, true));

        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('WC Zoop Cartão de Crédito: Pedido #' . $order_id . ' não encontrado');
            wc_add_notice(__('Erro: Pedido não encontrado.', 'wc-zoop-payments'), 'error');
            return;
        }
        error_log('WC Zoop Cartão de Crédito: Total do pedido: ' . $order->get_total());

     
        $required_fields = ['card_holder_name', 'card_number', 'card_expiry_month', 'card_expiry_year', 'card_security_code', 'number_installments', 'enderCEP'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                error_log('WC Zoop Cartão de Crédito: Campo ausente ou vazio: ' . $field);
                wc_add_notice(__('Erro: Por favor, preencha todos os campos obrigatórios.', 'wc-zoop-payments'), 'error');
                return;
            }
        }

        $payload = [
            'amount' => floatval($order->get_total()),
            'description' => 'Compra para o pedido #' . $order_id,
            'number_installments' => intval($_POST['number_installments']),
            'enderCEP' => sanitize_text_field($_POST['enderCEP']),
            'card' => [
                'holder_name' => sanitize_text_field($_POST['card_holder_name']),
                'expiration_month' => sanitize_text_field($_POST['card_expiry_month']),
                'expiration_year' => sanitize_text_field($_POST['card_expiry_year']),
                'card_number' => str_replace(' ', '', sanitize_text_field($_POST['card_number'])),
                'security_code' => sanitize_text_field($_POST['card_security_code'])
            ],
            'three_d_secure' => [
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'device' => [
                    'color_depth' => isset($_POST['device_color_depth']) ? intval($_POST['device_color_depth']) : 24,
                    'java_enabled' => false,
                    'language' => isset($_POST['device_language']) ? sanitize_text_field($_POST['device_language']) : 'pt-BR',
                    'screen_height' => isset($_POST['device_screen_height']) ? intval($_POST['device_screen_height']) : 1080,
                    'screen_width' => isset($_POST['device_screen_width']) ? intval($_POST['device_screen_width']) : 1920,
                    'time_zone_offset' => isset($_POST['device_time_zone']) ? intval($_POST['device_time_zone']) : -180
                ]
            ]
        ];

        error_log('WC Zoop Cartão de Crédito: Payload preparado: ' . json_encode($payload, JSON_PRETTY_PRINT));
        if (defined('WP_DEBUG') && WP_DEBUG) {
            wc_add_notice(__('Payload da Requisição API: ', 'wc-zoop-payments') . '<pre>' . esc_html(json_encode($payload, JSON_PRETTY_PRINT)) . '</pre>', 'notice');
        }

        $response = wp_remote_post('http://localhost:9099/api/transactions', [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ]);

        error_log('WC Zoop Cartão de Crédito: Requisição API enviada para http://localhost:9099/api/transactions');

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WC Zoop Cartão de Crédito: Erro WP na API: ' . $error_message);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Erro na Resposta da API: ', 'wc-zoop-payments') . esc_html($error_message), 'error');
            } else {
                wc_add_notice(__('Erro ao processar o pagamento. Tente novamente.', 'wc-zoop-payments'), 'error');
            }
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('WC Zoop Cartão de Crédito: Código de resposta da API: ' . $response_code);
        error_log('WC Zoop Cartão de Crédito: Corpo da resposta da API: ' . $response_body);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            wc_add_notice(__('Resposta da API: ', 'wc-zoop-payments') . '<pre>' . esc_html($response_body) . '</pre>', 'notice');
        }

        $body = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WC Zoop Cartão de Crédito: Erro ao decodificar JSON: ' . json_last_error_msg());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Erro na Resposta da API: JSON inválido. Resposta bruta: ', 'wc-zoop-payments') . esc_html($response_body), 'error');
            } else {
                wc_add_notice(__('Erro ao processar o pagamento. Tente novamente.', 'wc-zoop-payments'), 'error');
            }
            return;
        }

        if ($response_code == 201 && isset($body['ExResponse']['id'])) {
            error_log('WC Zoop Cartão de Crédito: Pagamento aprovado para o pedido #' . $order_id . ' com ID do token: ' . $body['ExResponse']['id']);
            $order->payment_complete($body['ExResponse']['id']);
            $order->add_order_note('Pagamento aprovado via Zoop. ID do token: ' . $body['ExResponse']['id']);
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Pagamento Bem-Sucedido. ID do Pedido: ', 'wc-zoop-payments') . $order_id . '. <a href="' . esc_url($this->get_return_url($order)) . '">Clique aqui para ver os detalhes do pedido.</a>', 'success');
                return; 
            } else {
                return [
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                ];
            }
        } else {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Erro desconhecido', 'wc-zoop-payments');
            error_log('WC Zoop Cartão de Crédito: Pagamento recusado: ' . $error_message);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                wc_add_notice(__('Erro na Resposta da API: ', 'wc-zoop-payments') . '<pre>' . esc_html(json_encode($body, JSON_PRETTY_PRINT)) . '</pre>', 'error');
            } else {
                wc_add_notice(__('Pagamento recusado: ', 'wc-zoop-payments') . esc_html($error_message), 'error');
            }
            return;
        }
    }
}