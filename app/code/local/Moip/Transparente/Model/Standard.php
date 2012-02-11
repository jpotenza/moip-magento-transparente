<?php

/**
 * MoIP - Moip Payment Module
 *
 * @title      Magento -> Custom Payment Module for Moip (Brazil)
 * @category   Payment Gateway
 * @package    Moip_Transparente
 * @author     MoIP Pagamentos S/A
 * @copyright  Copyright (c) 2012 MoIP Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Moip_Transparente_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    /**
     * Availability options
     */
    protected $_code = 'moip_transparente_standard';
    protected $_formBlockType = 'moip/standard_form';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_allowCurrencyCode = array('BRL');

    /**
     * Armazena as informações passadas via formulário no frontend
     * @access public
     * @param array $data
     * @return Moip_Transparente_Model_Standard
     */
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setFormaPagamento($data->getFormaPagamento());
        $info->setDebitoInstituicao($data->getDebitoInstituicao());

        // cartao de credito
        $info->setCreditoInstituicao($data->getCreditoInstituicao());
        $info->setCreditoNumero($data->getCreditoNumero());
        $info->setCreditoExpiracaoMes($data->getCreditoExpiracaoMes());
        $info->setCreditoExpiracaoAno($data->getCreditoExpiracaoAno());
        $info->setCreditoCodigoSeguranca($data->getCreditoCodigoSeguranca());
        $info->setCreditoParcelamento($data->getCreditoParcelamento());

        $info->setCreditoPortadorNome($data->getCreditoPortadorNome());
        $info->setCreditoPortadorCpf($data->getCreditoPortadorCpf());
        $info->setCreditoPortadorTelefone($data->getCreditoPortadorTelefone());
        $info->setCreditoPortadorNascimento($data->getCreditoPortadorNascimento());

        return $this;
    }

    public function getPayment() {
        return $this->getQuote()->getPayment();
    }

    /**
     * Get Modulo session namespace
     *
     * @return Moip_Model_Session
     */
    public function getSession() {
        return Mage::getSingleton('moip/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function getPaymentMethods() {
        return $this->getConfigData('formas_pagamento');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return $this->getCheckout()->getQuote();
    }

    public function validate() {
        parent::validate();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        if (!in_array($currency_code, $this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('moip')->__('Selected currency code (' . $currency_code . ') is not compatabile with Moip'));
        }
        return $this;
    }

    /**
     * Prepara para armazenar todas as informações para serem usadas na API
     * @access public
     * @return void
     */
    public function prepare() {
        $info = $this->getInfoInstance();
        $pgtoArray = array();
        $pgtoArray['forma_pagamento'] = $info->getFormaPagamento();
        $pgtoArray['debito_instituicao'] = $info->getDebitoInstituicao();
        $pgtoArray['pagamento_direto'] = $this->getConfigData('pagamento_direto');
        $pgtoArray['conta_moip'] = $this->getConfigData('conta_moip');
        $pgtoArray['apelido'] = $this->getConfigData('apelido');

        $pgtoArray['credito_instituicao'] = $info->getCreditoInstituicao();
        $pgtoArray['credito_numero'] = $info->getCreditoNumero();
        $pgtoArray['credito_expiracao_mes'] = $info->getCreditoExpiracaoMes();
        $pgtoArray['credito_expiracao_ano'] = $info->getCreditoExpiracaoAno();
        $pgtoArray['credito_codigo_seguranca'] = $info->getCreditoCodigoSeguranca();
        $pgtoArray['credito_parcelamento'] = $info->getCreditoParcelamento();
        $pgtoArray['credito_portador_nome'] = $info->getCreditoPortadorNome();
        $pgtoArray['credito_portador_cpf'] = $info->getCreditoPortadorCpf();
        $pgtoArray['credito_portador_telefone'] = $info->getCreditoPortadorTelefone();
        $pgtoArray['credito_portador_nascimento'] = $info->getCreditoPortadorNascimento();

        $api = Mage::getModel('moip/api');
        $api->setAmbiente($this->getConfigData('ambiente'));

        $session = Mage::getSingleton('checkout/session');
        $session->setPgtoArray($pgtoArray);
        $session->setMoIPFields($this->getStandardCheckoutFormFields());
    }

    /**
     * Obtem lugar para redirecionar a Url
     *
     * @return void
     */
    public function getOrderPlaceRedirectUrl() {
        $this->prepare();

        return Mage::getUrl('moip/standard/form', array('_secure' => true));
    }

    public function getFormasPagamento() {
        $formas = $this->getConfigData('formas_pagamento');
        $formas = explode(",", $formas);
        return $formas;
    }

    /**
     * Monta array dos campos para a postagem
     *
     * @return array
     */
    public function getStandardCheckoutFormFields() {
        if ($this->getQuote()->getIsVirtual()) {
            $a = $this->getQuote()->getBillingAddress();
            $b = $this->getQuote()->getShippingAddress();
        } else {
            $a = $this->getQuote()->getShippingAddress();
            $b = $this->getQuote()->getBillingAddress();
        }

        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();

        $totalArr = $a->getTotals();

        $cep = substr(preg_replace("/[^0-9]/", "", $a->getPostcode()) . '00000000', 0, 8);

        $amount = $a->getGrandTotal();
        #$valor = str_replace(".", "", $amount);
        $valor = $amount;

        $dob = Mage::app()->getLocale()->date($this->getQuote()->getCustomerDob(), null, null, false)->toString('ddMMyyyy');

        $taxvat = $this->getQuote()->getCustomerTaxvat();

        $website_id = Mage::app()->getWebsite()->getId();
        $website_name = Mage::app()->getWebsite()->getName();
        $store_name = Mage::app()->getStore()->getName();

        $Arr = array(
            'id_carteira' => $this->getConfigData('conta_moip'),
            'valor' => $valor,
            'nome' => 'Pagamento a ' . $website_name,
            'descricao' => $this->getListaProdutos(),
            'id_transacao' => $this->getCheckout()->getLastRealOrderId(),
            'peso_compra' => $this->getPesoProdutosPedido(),
            'pagador_nome' => $a->getFirstname() . ' ' . $a->getLastname(),
            'pagador_email' => $a->getEmail(),
            'pagador_telefone' => $this->getNumberOrDDD($a->getTelephone(), true) . '' . $this->getNumberOrDDD($a->getTelephone()),
            'pagador_logradouro' => $a->getStreet(1),
            'pagador_numero' => $this->getNumEndereco($a->getStreet(1)),
            'pagador_complemento' => $a->getStreet(2),
            'pagador_bairro' => $a->getStreet(2),
            'pagador_cep' => $cep,
            'pagador_cidade' => $a->getCity(),
            'pagador_estado' => $a->getRegionCode(),
            'pagador_pais' => $a->getCountry(),
            'pagador_cpf' => $taxvat,
            'pagador_celular' => $this->getNumberOrDDD($a->getFax(), true) . '' . $this->getNumberOrDDD($a->getFax()),
            'pagador_sexo' => '',
            'pagador_data_nascimento' => $dob,
        );

        return $Arr;
    }

    /**
     * Obtem array com as faixas de parcelamento
     *
     * @return array
     */
    public function getInfoParcelamento() {

        $config = array();
        $max = 12;

        $config['de1'] = (int) $this->getConfigData('parcelamento_de1');
        $config['ate1'] = (int) $this->getConfigData('parcelamento_ate1');
        $config['juros1'] = $this->getConfigData('parcelamento_juros1');
        $config['de2'] = (int) $this->getConfigData('parcelamento_de2');
        $config['ate2'] = (int) $this->getConfigData('parcelamento_ate2');
        $config['juros2'] = $this->getConfigData('parcelamento_juros2');
        $config['de3'] = (int) $this->getConfigData('parcelamento_de3');
        $config['ate3'] = (int) $this->getConfigData('parcelamento_ate3');
        $config['juros3'] = $this->getConfigData('parcelamento_juros3');

        if ($config['ate1'] > $max)
            $config['ate1'] = $max;
        if ($config['ate2'] > $max)
            $config['ate2'] = $max;
        if ($config['ate3'] > $max)
            $config['ate3'] = $max;

        return $config;
    }

    /**
     * Usada para retornar o numero de uma string
     *
     * @return string
     */
    private function getNumEndereco($endereco) {
        $numEndereco = '';

        $posSeparador = $this->getPosSeparador($endereco, false);
        if ($posSeparador !== false)
            $numEndereco = trim(substr($endereco, $posSeparador + 1));

        $posComplemento = $this->getPosSeparador($numEndereco, true);
        if ($posComplemento !== false)
            $numEndereco = trim(substr($numEndereco, 0, $posComplemento));

        return($numEndereco);
    }

    function getPosSeparador($endereco, $procuraEspaco = false) {
        $posSeparador = strpos($endereco, ',');
        if ($posSeparador === false)
            $posSeparador = strpos($endereco, '-');

        if ($procuraEspaco)
            if ($posSeparador === false)
                $posSeparador = strrpos($endereco, ' ');

        return($posSeparador);
    }

    /**
     * Retorna o Numero ou DDD do Telefone
     *
     * @return string
     */
    function getNumberOrDDD($param_telefone, $param_ddd = false) {

        $cust_ddd = '00';
        $cust_telephone = preg_replace("/[^0-9]/", "", $param_telefone);
        $st = strlen($cust_telephone) - 8;

        if ($st > 0) { //No caso essa seqüência é mais de 8 caracteres
            $cust_ddd = substr($cust_telephone, 0, 2);
            $cust_telephone = substr($cust_telephone, $st, 8);
        }

        if ($param_ddd === false) {
            $retorno = $cust_telephone;
        } else {
            $retorno = $cust_ddd;
        }

        return $retorno;
    }

    /**
     * Retorna a lista dos produtos
     *
     * @return string
     */
    function getListaProdutos() {
        $items = $this->getQuote()->getAllVisibleItems();
        $lista = array();

        foreach ($items as $item) {
            $valor_item = ($item->getBaseCalculationPrice() - $item->getBaseDiscountAmount());
            $valor_item = Mage::helper('core')->currency($valor_item, true, false);

            $lista[] = $item->getName() . ' - ' . (int) $item->getQty() . ' - ' . $valor_item;
        }


        if ($this->getQuote()->getIsVirtual()) {
            $a = $this->getQuote()->getBillingAddress();
            $b = $this->getQuote()->getShippingAddress();
        } else {
            $a = $this->getQuote()->getShippingAddress();
            $b = $this->getQuote()->getBillingAddress();
        }

        $totalArr = $a->getTotals();

        if ($totalArr['shipping']) {
            $valor_frete = $totalArr['shipping']->getValue();
            $lista[] = 'Frete: R$ ' . number_format($valor_frete, 2, ",", ".");
        }

        return $lista;
    }

    /**
     * Retorna o peso total do pedido compra
     *
     * @return string
     */
    function getPesoProdutosPedido() {
        $items = $this->getQuote()->getAllVisibleItems();

        if ($items) {
            $item_peso = 0;

            foreach ($items as $item) {

                $item_peso = $item_peso + round($item->getWeight());
            }
        }

        return $item_peso;
    }

}
