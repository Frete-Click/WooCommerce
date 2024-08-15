<?php

/**
 * WC_FreteClick
 */
class WC_FreteClick extends WC_Shipping_Method {
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
	public function __construct($instance_id = 0 ) {
        $this->id           = 'freteclick';
        $this->instance_id 	= absint( $instance_id );
        $this->title        = __( 'Frete CLick', 'freteclick-shipping-gateway' );
		$this->method_title = __( 'Frete CLick', 'freteclick-shipping-gateway' );
        $this->method_description = 'Cálculo do frete com o serviço da web Frete Click';
        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal'
        );

        $this->init();
    }

	/**
	 * Convert class to string.
	 *
	 * @return string Class ID.
	 */
	public function __toString()
	{
	    return 'WC_FreteClick::' . $this->id . '::' . $this->instance_id . '::' . $this->method_title;
	}

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();
        
        // Actions.
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }
    
	/**
	 * Backwards compatibility with version prior to 2.1.
	 *
	 * @return object Returns the main instance of WooCommerce class.
	 */
	protected function woocommerce_method() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}

    function init_form_fields() {
        $this->instance_form_fields = array(
            'FC_IS_ACTIVE' => array(
                'title' => __( 'Status do Frete Click' ),
                'type' => 'checkbox',
                'description' => __( 'Para ativar os métodos de entrega do frete click você deve checar essa opção.' ),
                'label' => 'Ativar o Frete Click como Método de Entrega?'
            ),
            'FC_CEP_ORIGIN' => array(
                    'title' => __( 'CEP de Origem' ),
                    'type' => 'text',
                    'description' => __( '' ),
                    'class' => 'fc-input-cep cep-origin'
            ),
            'FC_STREET_ORIGIN' => array(
                    'title' => __( 'Rua' ),
                    'type' => 'text',
                    'description' => __( '' ),
                    'class' => 'street-origin'
            ),
            'FC_NUMBER_ORIGIN' => array(
                    'title' => __( 'Número' ),
                    'type' => 'text',
                    'description' => __( '' )
            ),
            'FC_COMPLEMENT_ORIGIN' => array(
                    'title' => __( 'Complemento' ),
                    'type' => 'text',
                    'description' => __( '' )
            ),
            'FC_DISTRICT_ORIGIN' => array(
                    'title' => __( 'Bairro' ),
                    'type' => 'text',
                    'description' => __( '' ),
                    'class' => 'district-origin'
            ),
            'FC_CITY_ORIGIN' => array(
                    'title' => __( 'Cidade de Origem' ),
                    'type' => 'text',
                    'description' => __( '' ),
                    'class' => 'city-origin'
            ),
            'FC_STATE_ORIGIN' => array(
                    'title' => __( 'Estado de Origem' ),
                    'type' => 'select',
                    'description' => __( '' ),
                    'options' => array(
                        'AC' => 'Acre',
                        'AL' => 'Alagoas',
                        'AP' => 'Amapá',
                        'AM' => 'Amazonas',
                        'BA' => 'Bahia',
                        'CE' => 'Ceará',
                        'DF' => 'Distrito Federal',
                        'ES' => 'Espírito Santo',
                        'GO' => 'Goiás',
                        'MA' => 'Maranhão',
                        'MT' => 'Mato Grosso',
                        'MS' => 'Mato Grosso do Sul',
                        'MG' => 'Minas Gerais',
                        'PA' => 'Pará',
                        'PB' => 'Paraíba',
                        'PR' => 'Paraná',
                        'PE' => 'Pernambuco',
                        'PI' => 'Piauí',
                        'RJ' => 'Rio de Janeiro',
                        'RN' => 'Rio Grande do Norte',
                        'RS' => 'Rio Grande do Sul',
                        'RO' => 'Rondônia',
                        'RR' => 'Roraima',
                        'SC' => 'Santa Catarina',
                        'SP' => 'São Paulo',
                        'SE' => 'Sergipe',
                        'TO' => 'Tocantins'
                    ),
                    'class' => 'state-origin'
            ),
            'FC_CONTRY_ORIGIN' => array(
                    'title' => __( 'Paìs de Origem' ),
                    'type' => 'text',
                    'description' => __( '' ),
                    'default' => 'Brasil',
                    'class' => 'country-origin'
            )
        );
    }

	/**
	 * Checks if the method is available.
	 *
	 * @param array $package Order package.
	 *
	 * @return bool
	 */
	public function is_available( $package ) {
		$is_available = true;

		if ( 'no' == $this->enabled ) {
			$is_available = false;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

    /**
    * calculate_shipping function.
    *
    * @access public
    * @param mixed $package
    * @return void
    */
    public function calculate_shipping( $package = [] ) {
		$rates  = [];
        $errors = [];

        $array_resp = WC_FreteClick_Shipping_Simulator::fc_calculate_shipping($package);

        if (! empty( $array_resp )){
            
            $order_id = $array_resp->response->data->order->id;						

            foreach ($array_resp->response->data->order->quotes as $key => $quote){
                $quote = (array) $quote;
                
                $fc_get_deadline = intval($quote['retrieveDeadline']) + intval($quote['deliveryDeadline']);
                $fc_deadline =  $fc_get_deadline + intval(get_option("FC_PRAZO_EXTRA"));
                $fc_deadline_variation = "";
                
                if(! empty( get_option("FC_PRAZO_VARIADO") )) {
                    $fc_deadline_variation = " até " . get_option("FC_PRAZO_VARIADO");
                }
                
                $rates[] = array(
                    'id' => $quote['id'],
                    'label' =>  $quote['carrier']->alias . "  (" . $fc_deadline . $fc_deadline_variation . "  dias úteis)",
                    'cost' => $quote['total'], 
                    'calc_tax' => 'per_item',
                    'meta_data' => array(
                        'Pedido'			=> '#'. $order_id,
                        'Código de Rastreamento' 	=> $order_id,
                        'Nome da Transportadora' 	=> $quote['carrier']->name,
                        'Cotação' 					=> $quote['id']
                    )
                );
            
            }

            foreach ( $rates as $rate ) {
                $this->add_rate( $rate );
            }
        }
    }
}