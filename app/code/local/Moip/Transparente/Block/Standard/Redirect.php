<?php
/**
 * MoIP - Moip Payment Module
 *
 * @title      Magento -> Custom Payment Module for Moip (Brazil)
 * @category   Payment Gateway
 * @package    Moip_Transparente
 * @author     MoIP Pagamentos S/a
 * @copyright  Copyright (c) 2010 MoIP Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Moip_Transparente_Block_Standard_Redirect extends Mage_Core_Block_Abstract {

  protected function _toHtml() {
    $standard = Mage::getModel('moip/standard');

    $form = new Varien_Data_Form();
    $api = Mage::getModel('moip/api');
    $api->setAmbiente($standard->getConfigData('ambiente'));
    $url = $api->generateUrl(Mage::registry('token'));
    $status_pgdireto = Mage::registry('StatusPgdireto');
    //Zend_Debug::dump(Mage::registry('xml'));

    $html = $this->__('');


    if (Mage::registry('token')) {
      if ($status_pgdireto <> "Cancelado")
        $html.= "<meta http-equiv='Refresh' content='4;URL=/index.php/checkout/onepage/success/'>";

      if ($status_pgdireto == "Cancelado")
        $html.= "O pagamento foi cancelado, você poderá realizar uma nova tentativa de compra utilizando outra forma de pagamento ou outro cartão.";
      elseif ($status_pgdireto == "Iniciado")
        $html.= "A transação ainda está sendo processada e não foi confirmada até o momento. Você será informado por email assim que o processamento for concluído.";
      elseif ($status_pgdireto == "Sucesso")
        $html.= "O pagamento foi concluído com sucesso e a loja será notificada notificado";
      else
        $html.= "O pagamento foi autorizado, porém não foi confirmado por nossa equipe. Aguarde. Você receberá a confirmação do pagamento por e-mail.   ";

      //$html.= $status_pgdireto;
    }
  }
  return $html;
}
