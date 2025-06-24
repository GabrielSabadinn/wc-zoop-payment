<?php
if (!defined('ABSPATH')) {
    error_log('WC Zoop Recorrência: ABSPATH não definido, encerrando');
    exit;
}

class WC_Gateway_Zoop_Recurrence extends WC_Payment_Gateway {
    public function __construct() {
        error_log('WC Zoop Recorrência: Entrando no construtor');
        $this->id = 'zoop_recurrence';
        $this->method_title = __('Recorrência Zoop', 'wc-zoop-payments');
        $this->method_description = __('Pague com cartão de crédito recorrente via API Zoop', 'wc-zoop-payments');
        $this->title = $this->get_option('title', __('Pagamento Recorrente', 'wc-zoop-payments'));
        $this->has_fields = true;
        $this->supports = ['products', 'subscriptions'];

        error_log('WC Zoop Recorrência: ID do gateway: ' . $this->id);
        error_log('WC Zoop Recorrência: Título: ' . $this->title);

        $this->init_form_fields();
        error_log('WC Zoop Recorrência: Campos de formulário inicializados');

        $this->init_settings();
        error_log('WC Zoop Recorrência: Configurações inicializadas');

        $this->enabled = $this->get_option('enabled', 'yes');
        $this->description = $this->get_option('description', __('Configure um pagamento recorrente com cartão de crédito via nossa API Zoop segura', 'wc-zoop-payments'));
        error_log('WC Zoop Recorrência: Habilitado: ' . $this->enabled);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_footer', [$this, 'add_payment_scripts']);
        error_log('WC Zoop Recorrência: Ações registradas');
    }

    public function init_form_fields() {
        error_log('WC Zoop Recorrência: Inicializando campos de formulário');
        $this->form_fields = [
            'enabled' => [
                'title' => __('Ativar/Desativar', 'wc-zoop-payments'),
                'type' => 'checkbox',
                'label' => __('Ativar Recorrência Zoop', 'wc-zoop-payments'),
                'default' => 'yes'
            ],
            'title' => [
                'title' => __('Título', 'wc-zoop-payments'),
                'type' => 'text',
                'description' => __('Título exibido no checkout', 'wc-zoop-payments'),
                'default' => __('Pagamento Recorrente', 'wc-zoop-payments')
            ],
            'description' => [
                'title' => __('Descrição', 'wc-zoop-payments'),
                'type' => 'textarea',
                'description' => __('Descrição exibida no checkout', 'wc-zoop-payments'),
                'default' => __('Configure um pagamento recorrente com cartão de crédito via nossa API Zoop segura', 'wc-zoop-payments')
            ],
            'marketplace_id' => [
                'title' => __('Marketplace ID', 'wc-zoop-payments'),
                'type' => 'text',
                'description' => __('ID do marketplace fornecido pela Zoop', 'wc-zoop-payments'),
                'default' => ''
            ],
            'api_key' => [
                'title' => __('Chave API', 'wc-zoop-payments'),
                'type' => 'text',
                'description' => __('Chave API fornecida pela Zoop', 'wc-zoop-payments'),
                'default' => ''
            ]
        ];
        error_log('WC Zoop Recorrência: Campos de formulário definidos: ' . print_r($this->form_fields, true));
    }

    public function add_payment_scripts() {
        error_log('WC Zoop Recorrência: Verificando se está na página de checkout');
        if (!is_checkout()) {
            error_log('WC Zoop Recorrência: Não está na página de checkout, ignorando scripts');
            return;
        }
        error_log('WC Zoop Recorrência: Adicionando scripts ao checkout');
        ?>
        <style>
            #zoop-recurrence-form .form-row {
                margin-bottom: 15px;
            }
            #zoop-recurrence-form input, #zoop-recurrence-form select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box;
            }
            #zoop-recurrence-form .form-row-inline {
                display: flex;
                gap: 10px;
            }
            #zoop-recurrence-form .form-col {
                flex: 1;
            }
            #zoop-recurrence-form label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .error-message {
                color: red;
                font-size: 12px;
                margin-top: 5px;
                display: none;
            }
        </style>
        <script>
            console.log('WC Zoop Recorrência: JavaScript carregado na página de checkout');
            jQuery(document).ready(function($) {
                console.log('WC Zoop Recorrência: jQuery pronto, inicializando manipuladores de formulário');
                const cardNumber = $('#card_number_recurrence');
                const expiryMonth = $('#card_expiry_month_recurrence');
                const expiryYear = $('#card_expiry_year_recurrence');
                const securityCode = $('#card_security_code_recurrence');
                const cep = $('#enderCEP_recurrence');
                const cpf = $('#taxpayer_id_recurrence');
                const phone = $('#phone_number_recurrence');
                const birthdate = $('#birthdate_recurrence');
                const dueDate = $('#due_date_recurrence');
                const expirationDate = $('#expiration_date_recurrence');
                const planName = $('#plan_name_recurrence');

                // Função para validar CPF
                function validateCPF(value) {
                    value = value.replace(/\D/g, '');
                    if (value.length !== 11) return false;
                    let sum = 0;
                    let rest;
                    for (let i = 1; i <= 9; i++) sum += parseInt(value.charAt(i-1)) * (11 - i);
                    rest = (sum * 10) % 11;
                    if ((rest === 10) || (rest === 11)) rest = 0;
                    if (rest !== parseInt(value.charAt(9))) return false;
                    sum = 0;
                    for (let i = 1; i <= 10; i++) sum += parseInt(value.charAt(i-1)) * (12 - i);
                    rest = (sum * 10) % 11;
                    if ((rest === 10) || (rest === 11)) rest = 0;
                    if (rest !== parseInt(value.charAt(10))) return false;
                    return true;
                }

                // Função para validar data no formato DD/MM/AAAA
                function validateDate(value) {
                    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
                    if (!regex.test(value)) return false;
                    const [, day, month, year] = value.match(regex);
                    const date = new Date(year, month - 1, day);
                    return date.getDate() == day && date.getMonth() == month - 1 && date.getFullYear() == year;
                }

                // Função para validar data/hora no formato DD/MM/AAAA HH:MM
                function validateDateTime(value) {
                    const regex = /^(\d{2})\/(\d{2})\/(\d{4})\s(\d{2}):(\d{2})$/;
                    if (!regex.test(value)) return false;
                    const [, day, month, year, hour, minute] = value.match(regex);
                    const date = new Date(year, month - 1, day);
                    return date.getDate() == day && date.getMonth() == month - 1 && date.getFullYear() == year && hour <= 23 && minute <= 59;
                }

                // Adicionar mensagens de erro
                function addErrorMessage(input, message) {
                    let error = input.next('.error-message');
                    if (!error.length) {
                        error = $('<div class="error-message"></div>').insertAfter(input);
                    }
                    error.text(message).show();
                }

                function clearErrorMessage(input) {
                    const error = input.next('.error-message');
                    if (error.length) error.hide();
                }

                if (cardNumber.length) {
                    console.log('WC Zoop Recorrência: Campo de número do cartão encontrado');
                    cardNumber.on('input', function() {
                        let value = $(this).val().replace(/\D/g, '');
                        value = value.replace(/(\d{4})/g, '$1 ').trim();
                        $(this).val(value);
                        console.log('WC Zoop Recorrência: Entrada do número do cartão: ' + value);
                    });
                } else {
                    console.log('WC Zoop Recorrência: Campo de número do cartão NÃO encontrado');
                }

                expiryMonth.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 2) value = value.slice(0, 2);
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada do mês de expiração: ' + value);
                });

                expiryYear.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada do ano de expiração: ' + value);
                });

                securityCode.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 4) value = value.slice(0, 4);
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada do CVV: ' + value);
                });

                cep.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 5) {
                        value = value.slice(0, 5) + '-' + value.slice(5, 8);
                    }
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada do CEP: ' + value);
                });

                cpf.on('input', function() {
                    clearErrorMessage($(this));
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 11) value = value.slice(0, 11);
                    if (value.length === 11) {
                        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                        if (!validateCPF(value)) {
                            addErrorMessage($(this), 'CPF inválido');
                        }
                    }
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada do CPF: ' + value);
                });

                phone.on('input', function() {
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 11) value = value.slice(0, 11);
                    if (value.length >= 10) {
                        value = value.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3');
                    }
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada do telefone: ' + value);
                });

                birthdate.on('input', function() {
                    clearErrorMessage($(this));
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 8) value = value.slice(0, 8);
                    if (value.length === 8) {
                        value = value.replace(/(\d{2})(\d{2})(\d{4})/, '$1/$2/$3');
                        if (!validateDate(value)) {
                            addErrorMessage($(this), 'Data de nascimento inválida');
                        }
                    }
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada da data de nascimento: ' + value);
                });

                dueDate.on('input', function() {
                    clearErrorMessage($(this));
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 8) value = value.slice(0, 8);
                    if (value.length === 8) {
                        value = value.replace(/(\d{2})(\d{2})(\d{4})/, '$1/$2/$3');
                        if (!validateDate(value)) {
                            addErrorMessage($(this), 'Data de vencimento inválida');
                        }
                    }
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada da data de vencimento: ' + value);
                });

                expirationDate.on('input', function() {
                    clearErrorMessage($(this));
                    let value = $(this).val().replace(/\D/g, '');
                    if (value.length > 12) value = value.slice(0, 12);
                    if (value.length === 12) {
                        value = value.replace(/(\d{2})(\d{2})(\d{4})(\d{2})(\d{2})/, '$1/$2/$3 $4:$5');
                        if (!validateDateTime(value)) {
                            addErrorMessage($(this), 'Data de expiração inválida');
                        }
                    }
                    $(this).val(value);
                    console.log('WC Zoop Recorrência: Entrada da data de expiração: ' + value);
                });

                planName.on('input', function() {
                    clearErrorMessage($(this));
                    let value = $(this).val();
                    if (value.length < 3) {
                        addErrorMessage($(this), 'O nome do plano deve ter pelo menos 3 caracteres');
                    }
                    console.log('WC Zoop Recorrência: Entrada do nome do plano: ' + value);
                });

                // Validação no envio do formulário
                $('#zoop-recurrence-form').closest('form').on('submit', function(e) {
                    let hasErrors = false;
                    if (cpf.val().replace(/\D/g, '').length !== 11 || !validateCPF(cpf.val())) {
                        addErrorMessage(cpf, 'Por favor, insira um CPF válido (11 dígitos)');
                        hasErrors = true;
                    }
                    if (!validateDate(birthdate.val())) {
                        addErrorMessage(birthdate, 'Por favor, insira uma data de nascimento válida (DD/MM/AAAA)');
                        hasErrors = true;
                    }
                    if (!validateDate(dueDate.val())) {
                        addErrorMessage(dueDate, 'Por favor, insira uma data de vencimento válida (DD/MM/AAAA)');
                        hasErrors = true;
                    }
                    if (!validateDateTime(expirationDate.val())) {
                        addErrorMessage(expirationDate, 'Por favor, insira uma data de expiração válida (DD/MM/AAAA HH:MM)');
                        hasErrors = true;
                    }
                    if (planName.val().length < 3) {
                        addErrorMessage(planName, 'Por favor, insira um nome de plano com pelo menos 3 caracteres');
                        hasErrors = true;
                    }
                    if (hasErrors) {
                        e.preventDefault();
                        console.log('WC Zoop Recorrência: Erros de validação detectados, bloqueando envio do formulário');
                    }
                });

                console.log('WC Zoop Recorrência: Adicionando campos ocultos de dispositivo');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_color_depth_recurrence',
                    value: screen.colorDepth || 24
                }).appendTo('#zoop-recurrence-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_language_recurrence',
                    value: navigator.language || 'pt-BR'
                }).appendTo('#zoop-recurrence-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_screen_height_recurrence',
                    value: screen.height || 1080
                }).appendTo('#zoop-recurrence-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_screen_width_recurrence',
                    value: screen.width || 1920
                }).appendTo('#zoop-recurrence-form');
                $('<input>').attr({
                    type: 'hidden',
                    name: 'device_time_zone_recurrence',
                    value: new Date().getTimezoneOffset()
                }).appendTo('#zoop-recurrence-form');
                console.log('WC Zoop Recorrência: Campos ocultos de dispositivo adicionados');
            });
        </script>
        <?php
    }

    public function payment_fields() {
        error_log('WC Zoop Recorrência: Renderizando campos de pagamento');
        ?>
        <div id="zoop-recurrence-form">
            <p><?php echo esc_html($this->description); ?></p>
            <h4><?php _e('Informações do Cartão', 'wc-zoop-payments'); ?></h4>
            <div class="form-row">
                <label for="card_holder_name_recurrence"><?php _e('Nome do Titular do Cartão', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="card_holder_name_recurrence" name="card_holder_name_recurrence" placeholder="João Silva" required>
            </div>
            <div class="form-row">
                <label for="card_number_recurrence"><?php _e('Número do Cartão', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="card_number_recurrence" name="card_number_recurrence" placeholder="1234 5678 9012 3456" maxlength="19" required>
            </div>
            <div class="form-row form-row-inline">
                <div class="form-col">
                    <label for="card_expiry_month_recurrence"><?php _e('Mês de Expiração', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="card_expiry_month_recurrence" name="card_expiry_month_recurrence" placeholder="MM" maxlength="2" required>
                </div>
                <div class="form-col">
                    <label for="card_expiry_year_recurrence"><?php _e('Ano de Expiração', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="card_expiry_year_recurrence" name="card_expiry_year_recurrence" placeholder="AAAA" maxlength="4" required>
                </div>
                <div class="form-col">
                    <label for="card_security_code_recurrence"><?php _e('CVV', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="card_security_code_recurrence" name="card_security_code_recurrence" placeholder="123" maxlength="4" required>
                </div>
            </div>
            <h4><?php _e('Informações do Comprador', 'wc-zoop-payments'); ?></h4>
            <div class="form-row form-row-inline">
                <div class="form-col">
                    <label for="first_name_recurrence"><?php _e('Nome', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="first_name_recurrence" name="first_name_recurrence" placeholder="Fulano" required>
                </div>
                <div class="form-col">
                    <label for="last_name_recurrence"><?php _e('Sobrenome', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="last_name_recurrence" name="last_name_recurrence" placeholder="Ciclano" required>
                </div>
            </div>
            <div class="form-row">
                <label for="email_recurrence"><?php _e('E-mail', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="email" id="email_recurrence" name="email_recurrence" placeholder="fulano@ciclano.com" required>
            </div>
            <div class="form-row form-row-inline">
                <div class="form-col">
                    <label for="phone_number_recurrence"><?php _e('Telefone', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="phone_number_recurrence" name="phone_number_recurrence" placeholder="(51) 99999-9999" maxlength="15" required>
                </div>
                <div class="form-col">
                    <label for="taxpayer_id_recurrence"><?php _e('CPF', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="taxpayer_id_recurrence" name="taxpayer_id_recurrence" placeholder="999.999.999-99" maxlength="14" required>
                </div>
            </div>
            <div class="form-row">
                <label for="birthdate_recurrence"><?php _e('Data de Nascimento', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="birthdate_recurrence" name="birthdate_recurrence" placeholder="DD/MM/AAAA" maxlength="10" required>
            </div>
            <h4><?php _e('Endereço', 'wc-zoop-payments'); ?></h4>
            <div class="form-row">
                <label for="enderCEP_recurrence"><?php _e('CEP', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="enderCEP_recurrence" name="enderCEP_recurrence" placeholder="99999-999" maxlength="9" required>
            </div>
            <div class="form-row">
                <label for="address_line1_recurrence"><?php _e('Endereço', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="address_line1_recurrence" name="address_line1_recurrence" placeholder="Rua Exemplo, 123" required>
            </div>
            <div class="form-row">
                <label for="address_line2_recurrence"><?php _e('Complemento', 'wc-zoop-payments'); ?></label>
                <input type="text" id="address_line2_recurrence" name="address_line2_recurrence" placeholder="Apto 999">
            </div>
            <div class="form-row">
                <label for="address_line3_recurrence"><?php _e('Referência', 'wc-zoop-payments'); ?></label>
                <input type="text" id="address_line3_recurrence" name="address_line3_recurrence" placeholder="Próximo ao mercado">
            </div>
            <div class="form-row form-row-inline">
                <div class="form-col">
                    <label for="neighborhood_recurrence"><?php _e('Bairro', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="neighborhood_recurrence" name="neighborhood_recurrence" placeholder="Centro" required>
                </div>
                <div class="form-col">
                    <label for="city_recurrence"><?php _e('Cidade', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <input type="text" id="city_recurrence" name="city_recurrence" placeholder="Porto Alegre" required>
                </div>
            </div>
            <div class="form-row form-row-inline">
                <div class="form-col">
                    <label for="state_recurrence"><?php _e('Estado', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                    <select id="state_recurrence" name="state_recurrence" required>
                        <option value=""><?php _e('Selecione', 'wc-zoop-payments'); ?></option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>
            </div>
            <h4><?php _e('Plano de Recorrência', 'wc-zoop-payments'); ?></h4>
            <div class="form-row">
                <label for="plan_name_recurrence"><?php _e('Nome do Plano', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="plan_name_recurrence" name="plan_name_recurrence" placeholder="Plano de Assinatura" required>
            </div>
            <div class="form-row">
                <label for="due_date_recurrence"><?php _e('Data de Vencimento', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="due_date_recurrence" name="due_date_recurrence" placeholder="DD/MM/AAAA" maxlength="10" required>
            </div>
            <div class="form-row">
                <label for="expiration_date_recurrence"><?php _e('Data de Expiração do Plano', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="text" id="expiration_date_recurrence" name="expiration_date_recurrence" placeholder="DD/MM/AAAA HH:MM" maxlength="16" required>
            </div>
            <div class="form-row">
                <label for="recurrence_frequency"><?php _e('Frequência de Recorrência', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <select id="recurrence_frequency" name="recurrence_frequency" required>
                    <option value="daily"><?php _e('Diária', 'wc-zoop-payments'); ?></option>
                    <option value="weekly"><?php _e('Semanal', 'wc-zoop-payments'); ?></option>
                    <option value="monthly"><?php _e('Mensal', 'wc-zoop-payments'); ?></option>
                    <option value="quarterly"><?php _e('Trimestral', 'wc-zoop-payments'); ?></option>
                    <option value="semiannually"><?php _e('Semestral', 'wc-zoop-payments'); ?></option>
                    <option value="annually"><?php _e('Anual', 'wc-zoop-payments'); ?></option>
                </select>
            </div>
            <div class="form-row">
                <label for="recurrence_interval"><?php _e('Intervalo (em períodos)', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="number" id="recurrence_interval" name="recurrence_interval" min="1" value="1" required>
            </div>
            <div class="form-row">
                <label for="recurrence_duration"><?php _e('Duração (em meses)', 'wc-zoop-payments'); ?> <span class="required">*</span></label>
                <input type="number" id="recurrence_duration" name="recurrence_duration" min="1" value="12" required>
            </div>
        </div>
        <?php
        error_log('WC Zoop Recorrência: Campos de pagamento renderizados');
    }

    public function process_payment($order_id) {
        error_log('WC Zoop Recorrência: Processando pagamento para o pedido #' . $order_id);
        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('WC Zoop Recorrência: Pedido #' . $order_id . ' não encontrado');
            wc_add_notice(__('Erro ao processar o pagamento: Pedido não encontrado.', 'wc-zoop-payments'), 'error');
            return;
        }
        error_log('WC Zoop Recorrência: Total do pedido: ' . $order->get_total());

        // Converter o valor para centavos (Zoop espera valores em centavos)
        $amount_in_cents = floatval($order->get_total()) * 100;

        $due_date = isset($_POST['due_date_recurrence']) ? $this->convert_date_format(sanitize_text_field($_POST['due_date_recurrence'])) : '';
        $expiration_date = isset($_POST['expiration_date_recurrence']) ? $this->convert_datetime_format(sanitize_text_field($_POST['expiration_date_recurrence'])) : '';
        $birthdate = isset($_POST['birthdate_recurrence']) ? $this->convert_date_format(sanitize_text_field($_POST['birthdate_recurrence'])) : '';
        $taxpayer_id = isset($_POST['taxpayer_id_recurrence']) ? str_replace(['.', '-'], '', sanitize_text_field($_POST['taxpayer_id_recurrence'])) : '';

        // Validações no backend
        if (empty($due_date) || $due_date === $_POST['due_date_recurrence']) {
            error_log('WC Zoop Recorrência: Erro na formatação da data de vencimento: ' . $_POST['due_date_recurrence']);
            wc_add_notice(__('Erro ao processar o pagamento: Data de vencimento inválida. Use o formato DD/MM/AAAA.', 'wc-zoop-payments'), 'error');
            return;
        }

        if (empty($expiration_date) || $expiration_date === $_POST['expiration_date_recurrence']) {
            error_log('WC Zoop Recorrência: Erro na formatação da data de expiração: ' . $_POST['expiration_date_recurrence']);
            wc_add_notice(__('Erro ao processar o pagamento: Data de expiração inválida. Use o formato DD/MM/AAAA HH:MM.', 'wc-zoop-payments'), 'error');
            return;
        }

        if (empty($birthdate) || $birthdate === $_POST['birthdate_recurrence']) {
            error_log('WC Zoop Recorrência: Erro na formatação da data de nascimento: ' . $_POST['birthdate_recurrence']);
            wc_add_notice(__('Erro ao processar o pagamento: Data de nascimento inválida. Use o formato DD/MM/AAAA.', 'wc-zoop-payments'), 'error');
            return;
        }

        if (strlen($taxpayer_id) !== 11 || !preg_match('/^\d{11}$/', $taxpayer_id)) {
            error_log('WC Zoop Recorrência: CPF inválido: ' . $taxpayer_id);
            wc_add_notice(__('Erro ao processar o pagamento: CPF inválido. Deve conter 11 dígitos.', 'wc-zoop-payments'), 'error');
            return;
        }

        if (!isset($_POST['plan_name_recurrence']) || strlen(sanitize_text_field($_POST['plan_name_recurrence'])) < 3) {
            error_log('WC Zoop Recorrência: Nome do plano inválido: ' . ($_POST['plan_name_recurrence'] ?? ''));
            wc_add_notice(__('Erro ao processar o pagamento: Nome do plano inválido. Deve ter pelo menos 3 caracteres.', 'wc-zoop-payments'), 'error');
            return;
        }

        $payload = [
            'amount' => $amount_in_cents,
            'description' => 'Pagamento recorrente para o pedido #' . $order_id,
            'due_date' => $due_date,
            'expiration_date' => $expiration_date,
            'card' => [
                'holder_name' => isset($_POST['card_holder_name_recurrence']) ? sanitize_text_field($_POST['card_holder_name_recurrence']) : '',
                'expiration_month' => isset($_POST['card_expiry_month_recurrence']) ? sanitize_text_field($_POST['card_expiry_month_recurrence']) : '',
                'expiration_year' => isset($_POST['card_expiry_year_recurrence']) ? sanitize_text_field($_POST['card_expiry_year_recurrence']) : '',
                'card_number' => isset($_POST['card_number_recurrence']) ? str_replace(' ', '', sanitize_text_field($_POST['card_number_recurrence'])) : '',
                'security_code' => isset($_POST['card_security_code_recurrence']) ? sanitize_text_field($_POST['card_security_code_recurrence']) : ''
            ],
            'buyer' => [
                'address' => [
                    'line1' => isset($_POST['address_line1_recurrence']) ? sanitize_text_field($_POST['address_line1_recurrence']) : '',
                    'neighborhood' => isset($_POST['neighborhood_recurrence']) ? sanitize_text_field($_POST['neighborhood_recurrence']) : '',
                    'city' => isset($_POST['city_recurrence']) ? sanitize_text_field($_POST['city_recurrence']) : '',
                    'state' => isset($_POST['state_recurrence']) ? sanitize_text_field($_POST['state_recurrence']) : '',
                    'postal_code' => isset($_POST['enderCEP_recurrence']) ? str_replace('-', '', sanitize_text_field($_POST['enderCEP_recurrence'])) : '',
                    'country_code' => 'BR',
                    'line2' => isset($_POST['address_line2_recurrence']) ? sanitize_text_field($_POST['address_line2_recurrence']) : '',
                    'line3' => isset($_POST['address_line3_recurrence']) ? sanitize_text_field($_POST['address_line3_recurrence']) : ''
                ],
                'first_name' => isset($_POST['first_name_recurrence']) ? sanitize_text_field($_POST['first_name_recurrence']) : '',
                'last_name' => isset($_POST['last_name_recurrence']) ? sanitize_text_field($_POST['last_name_recurrence']) : '',
                'email' => isset($_POST['email_recurrence']) ? sanitize_email($_POST['email_recurrence']) : '',
                'phone_number' => isset($_POST['phone_number_recurrence']) ? str_replace(['(', ')', '-', ' '], '', sanitize_text_field($_POST['phone_number_recurrence'])) : '',
                'taxpayer_id' => $taxpayer_id,
                'birthdate' => $birthdate
            ],
            'plan' => [
                'frequency' => isset($_POST['recurrence_frequency']) ? sanitize_text_field($_POST['recurrence_frequency']) : 'monthly',
                'interval' => isset($_POST['recurrence_interval']) ? intval($_POST['recurrence_interval']) : 1,
                'amount' => $amount_in_cents,
                'setup_amount' => 10,
                'description' => 'Plano de recorrência para o pedido #' . $order_id,
                'name' => isset($_POST['plan_name_recurrence']) ? sanitize_text_field($_POST['plan_name_recurrence']) : 'Plano de Assinatura',
                'duration' => isset($_POST['recurrence_duration']) ? intval($_POST['recurrence_duration']) : 12
            ]
        ];

        error_log('WC Zoop Recorrência: Enviando payload: ' . json_encode($payload, JSON_PRETTY_PRINT));

        $api_key = $this->get_option('api_key');
        $endpoint = "http://localhost:9099/api/transactions/recurrent";

        $response = wp_remote_post($endpoint, [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 30
        ]);

        error_log('WC Zoop Recorrência: Requisição API enviada para ' . $endpoint);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WC Zoop Recorrência: Erro WP na API: ' . $error_message);
            wc_add_notice(__('Erro ao processar o pagamento: ', 'wc-zoop-payments') . esc_html($error_message), 'error');
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('WC Zoop Recorrência: Código de resposta da API: ' . $response_code);
        error_log('WC Zoop Recorrência: Corpo da resposta da API: ' . $response_body);

        $body = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WC Zoop Recorrência: Erro ao decodificar JSON: ' . json_last_error_msg());
            wc_add_notice(__('Erro ao processar o pagamento: JSON inválido.', 'wc-zoop-payments'), 'error');
            return;
        }

        if ($response_code == 201 && isset($body['id'])) {
            error_log('WC Zoop Recorrência: Plano de recorrência criado para o pedido #' . $order_id);
            $order->payment_complete($body['id']);
            $order->add_order_note('Plano de recorrência iniciado via Zoop. ID do Plano: ' . $body['id']);
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
                'message' => 'OK'
            ];
        } else {
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : __('Erro desconhecido', 'wc-zoop-payments');
            error_log('WC Zoop Recorrência: Pagamento recusado: ' . $error_message);
            wc_add_notice(__('Erro ao processar o pagamento: ', 'wc-zoop-payments') . esc_html($error_message), 'error');
            return;
        }
    }

    private function convert_date_format($date) {
        // Converte DD/MM/AAAA para AAAA-MM-DD
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $date, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            // Valida a data
            if (!checkdate($month, $day, $year)) {
                error_log('WC Zoop Recorrência: Data inválida em convert_date_format: ' . $date);
                return '';
            }

            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
        error_log('WC Zoop Recorrência: Formato de data inválido em convert_date_format: ' . $date);
        return '';
    }

    private function convert_datetime_format($datetime) {
        // Converte DD/MM/AAAA HH:MM para AAAA-MM-DD 00:00:00+00:00
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s(\d{2}):(\d{2})$/', $datetime, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            // Valida a data
            if (!checkdate($month, $day, $year)) {
                error_log('WC Zoop Recorrência: Data inválida em convert_datetime_format: ' . $datetime);
                return '';
            }

            return sprintf('%04d-%02d-%02d 00:00:00+00:00', $year, $month, $day);
        }
        error_log('WC Zoop Recorrência: Formato de data/hora inválido em convert_datetime_format: ' . $datetime);
        return '';
    }
}
?>