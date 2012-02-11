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

class Moip_Transparente_Model_Api {

  const TOKEN_TEST = "V0MFFJTVJGPOVCQJD3KZV62ITTC15RFG";
  const KEY_TEST = "OYSRNPUL885EJPPG10AEQUQZQYZBEZMZCUQBOMTA";
  const TOKEN_PROD = "E3DXZ3IHKEWGVWXFHQCSKCE0GTKT5PFP";
  const KEY_PROD = "QBDTYLNLCN7SAZ65J7LD6U18K73TJ1KSEURDEROP";

  private $ambiente = null;
  private $conta_moip = null;

  public function getContaMoip() {
    return $this->conta_moip;
  }

  public function setContaMoip($conta_moip) {
    $this->conta_moip = $conta_moip;
  }

  public function getAmbiente() {
    return $this->ambiente;
  }

  public function setAmbiente($ambiente) {
    $this->ambiente = $ambiente;
  }

  /**
   * Geração do XML
   * @access public
   * @param array $data
   * @param array $data
   * @return String
   */
  public function generateXML($data, $pgto) {
    $xml = new DomDocument('1.0', 'UTF-8');

    $instrucao = $xml->createElement('EnviarInstrucao', '');
    $xml->appendChild($instrucao);

    $unica = $xml->createElement('InstrucaoUnica', '');
    $instrucao->appendChild($unica);

    $razao = $xml->createElement('Razao', 'Compra Online');
    $unica->appendChild($razao);

    $id_transacao = substr($data['id_transacao'] . " - " . strrev(time()),0,31);
    $idproprio = $xml->createElement('IdProprio', $id_transacao);

    $unica->appendChild($idproprio);


    $recebedor = $xml->createElement('Recebedor', '');
    $unica->appendChild($recebedor);

    $login_moip = $xml->createElement('LoginMoIP', $pgto['conta_moip']);
    $recebedor->appendChild($login_moip);

    $apelido = $xml->createElement('Apelido', $pgto['apelido']);
    $recebedor->appendChild($apelido);


    // verifica o pagamento direto
    if ($pgto['pagamento_direto'] and $pgto['forma_pagamento']) {

      $pagdireto = $xml->createElement('PagamentoDireto', '');
      $unica->appendChild($pagdireto);

      $ip = $xml->createElement('IP', $_SERVER['REMOTE_ADDR']);
      $pagdireto->appendChild($ip);

      $formapgto = $xml->createElement('Forma', $pgto['forma_pagamento']);
      $pagdireto->appendChild($formapgto);

      if ($pgto['forma_pagamento'] == "DebitoBancario") {
        $instituicao = $xml->createElement('Instituicao', $pgto['debito_instituicao']);
        $pagdireto->appendChild($instituicao);
      }

      if ($pgto['forma_pagamento'] == "CartaoCredito") {

        $instituicao = $xml->createElement('Instituicao', $pgto['credito_instituicao']);
        $pagdireto->appendChild($instituicao);

        $cartaocredito = $xml->createElement('CartaoCredito', '');
        $pagdireto->appendChild($cartaocredito);

        $credito_numero = $xml->createElement('Numero', $pgto['credito_numero']);
        $cartaocredito->appendChild($credito_numero);

        $credito_expiracao = $xml->createElement('Expiracao', $pgto['credito_expiracao_mes'] . '/' . $pgto['credito_expiracao_ano']);
        $cartaocredito->appendChild($credito_expiracao);

        $credito_codigo = $xml->createElement('CodigoSeguranca', $pgto['credito_codigo_seguranca']);
        $cartaocredito->appendChild($credito_codigo);

        $credito_portador = $xml->createElement('Portador', '');
        $cartaocredito->appendChild($credito_portador);

        $credito_nome = $xml->createElement('Nome', $pgto['credito_portador_nome']);
        $credito_portador->appendChild($credito_nome);

        $credito_cpf = $xml->createElement('Identidade', $pgto['credito_portador_cpf']);
        $credito_portador->appendChild($credito_cpf);

        $credito_cpf_tipo = $xml->createAttribute("Tipo");
        $credito_cpf->appendChild($credito_cpf_tipo);

        $credito_cpf_tipo_value = $xml->createTextNode("CPF");
        $credito_cpf_tipo->appendChild($credito_cpf_tipo_value);

        $credito_telefone = $xml->createElement('Telefone', $pgto['credito_portador_telefone']);
        $credito_portador->appendChild($credito_telefone);

        $credito_nascimento = $xml->createElement('DataNascimento', $pgto['credito_portador_nascimento']);
        $credito_portador->appendChild($credito_nascimento);

        if ($pgto['credito_parcelamento'] <> "") {

          $pgto['credito_parcelamento'] = explode("|", $pgto['credito_parcelamento']);

          $parcelamento = $xml->createElement('Parcelamento');
          $pagdireto->appendChild($parcelamento);

          $parcelas = $xml->createElement('Parcelas', $pgto['credito_parcelamento'][0]);
          $parcelamento->appendChild($parcelas);

          $recebimento = $xml->createElement('Recebimento', 'AVista');
          $parcelamento->appendChild($recebimento);
        }
      }
    }


    // Pagador
    $pagador = $xml->createElement('Pagador', '');
    $unica->appendChild($pagador);

    $nome = $xml->createElement('Nome', $data['pagador_nome']);
    $pagador->appendChild($nome);

    $email = $xml->createElement('Email', $data['pagador_email']);
    $pagador->appendChild($email);


    // endereco cobranca
    $enderecocobranca = $xml->createElement('EnderecoCobranca', '');
    $pagador->appendChild($enderecocobranca);


    $logradouro = $xml->createElement('Logradouro', $data['pagador_logradouro']);
    $enderecocobranca->appendChild($logradouro);

    $numero = $xml->createElement('Numero', $data['pagador_numero']);
    $enderecocobranca->appendChild($numero);

    $complemento = $xml->createElement('Complemento', $data['pagador_complemento']);
    $enderecocobranca->appendChild($complemento);

    $bairro = $xml->createElement('Bairro', ' ');
    $enderecocobranca->appendChild($bairro);


    $cidade = $xml->createElement('Cidade', $data['pagador_cidade']);
    $enderecocobranca->appendChild($cidade);

    $estado = $xml->createElement('Estado', $data['pagador_estado']);
    $enderecocobranca->appendChild($estado);

    if ($data['pagador_pais'] == "BR")
      $data['pagador_pais'] = "BRA";
    $pais = $xml->createElement('Pais', $data['pagador_pais']);
    $enderecocobranca->appendChild($pais);


    $cep = $xml->createElement('CEP', $data['pagador_cep']);
    $enderecocobranca->appendChild($cep);


    $telefonefixo = $xml->createElement('TelefoneFixo', $data['pagador_telefone']);
    $enderecocobranca->appendChild($telefonefixo);

    $valores = $xml->createElement('Valores', '');
    $unica->appendChild($valores);

    if (isset($pgto['credito_parcelamento'][1]) and ($pgto['credito_parcelamento'][1] * $pgto['credito_parcelamento'][0]) > 0)
      $valor = $xml->createElement('Valor', $pgto['credito_parcelamento'][1] * $pgto['credito_parcelamento'][0]);
    else
      $valor = $xml->createElement('Valor', $data['valor']);
    $valores->appendChild($valor);

    $mensagens = $xml->createElement('Mensagens', '');
    $unica->appendChild($mensagens);

    foreach ($data['descricao'] as $msg) {
      $mensagem = $xml->createElement('Mensagem', $msg);
      $mensagens->appendChild($mensagem);
    }

    $entrega = $xml->createElement('Entrega', '');
    $unica->appendChild($entrega);

    $destino = $xml->createElement('Destino', 'MesmoCobranca');
    $entrega->appendChild($destino);
    return $xml->saveXML();
  }

  /**
   * Verificação se a loja possui pagamento direto
   * @access public
   * @return Array
   */
  public function hasPagamentoDireto() {
    if ($this->getAmbiente() == "teste") {
      $url = "https://desenvolvedor.moip.com.br/sandbox/ws/alpha/ChecarPagamentoDireto/" . $this->conta_moip;
      $header = "Authorization: Basic " . base64_encode(Moip_Transparente_Model_Api::TOKEN_TEST . ":" . Moip_Transparente_Model_Api::KEY_TEST);
    }
    else {
      $url = "https://www.moip.com.br/ws/alpha/ChecarPagamentoDireto/" . $this->conta_moip;
      $header = "Authorization: Basic " . base64_encode(Moip_Transparente_Model_Api::TOKEN_PROD . ":" . Moip_Transparente_Model_Api::KEY_PROD);
    }

    $httpClient = new Zend_Http_Client($url);
    $httpClient->setHeaders($header);
    $responseMoIP = $httpClient->request('GET');

    $res = simplexml_load_string($responseMoIP->getBody());

    return $res;
  }

  /**
   * Acesso aos valores do parcelamento de acordo com as faixas estipuladas na administração
   * @access public
   * @param Double $valor
   * @return Array
   */
  public function getParcelamento($valor) {
    $standard = Mage::getSingleton('moip/standard');
    $parcelamento = $standard->getInfoParcelamento();

    /* pega as 3 faixas de parcelamento. Essa rotina poderia ser feita com um
     * um único looping. Manteremos dessa forma para ficar mais didático
     */

    $result = array();
    if ($parcelamento['ate1'] > 0) {
      $result1 = $this->getRequestParcelamento($valor, $parcelamento['juros1'], $parcelamento['ate1']);
      foreach ($result1 as $k => $v) {
        if ($k >= $parcelamento['de1'])
          $result[$k] = $v;
      }
    }
    if ($parcelamento['ate2'] > $parcelamento['ate1']) {
      $result1 = $this->getRequestParcelamento($valor, $parcelamento['juros2'], $parcelamento['ate2']);
      foreach ($result1 as $k => $v) {
        if ($k >= $parcelamento['de2'])
          $result[$k] = $v;
      }
    }

    if ($parcelamento['ate3'] > $parcelamento['ate2']) {
      $result1 = $this->getRequestParcelamento($valor, $parcelamento['juros3'], $parcelamento['ate3']);
      foreach ($result1 as $k => $v) {
        if ($k >= $parcelamento['de3'])
          $result[$k] = $v;
      }
    }

    return $result;
  }

  /**
   * Faz a requisição para pegar os valores parcelados. Método utilizado pelo getParcelamento
   * @access public
   * @param Double $valor
   * @param Double $juros
   * @return Array
   */
  public function getRequestParcelamento($valor, $juros, $parcelas) {

    if ($this->getAmbiente() == "teste") {
      $url = "https://desenvolvedor.moip.com.br/sandbox/ws/alpha/ChecarValoresParcelamento/" . $this->conta_moip . "/" . $parcelas . "/" . $juros . "/" . $valor;
      $header = "Authorization: Basic " . base64_encode(Moip_Transparente_Model_Api::TOKEN_TEST . ":" . Moip_Transparente_Model_Api::KEY_TEST);
    }
    else {
      $url = "https://www.moip.com.br/ws/alpha/ChecarValoresParcelamento/" . $this->conta_moip . "/" . $parcelas . "/" . $juros . "/" . $valor;
      $header = "Authorization: Basic " . base64_encode(Moip_Transparente_Model_Api::TOKEN_PROD . ":" . Moip_Transparente_Model_Api::KEY_PROD);
    }

    $httpClient = new Zend_Http_Client($url);
    $httpClient->setHeaders($header);
    $responseMoIP = $httpClient->request('GET');

    $res = simplexml_load_string($responseMoIP->getBody());

    $result = array();
    $i = 1;
    foreach ($res as $resposta) {
      foreach ($resposta as $data) {
        if ($data->getName() == "ValorDaParcela") {
          $result[$i]['total'] = $data->attributes()->Total;
          $result[$i]['valor'] = $data->attributes()->Valor;
          $i++;
        }
      }
    }

    return $result;
  }

  /**
   * Solicita token pelo MoIP depois do XML informado
   * @access public
   * @param String $xml
   * @return Array
   */
  public function getToken($xml) {

    if ($this->getAmbiente() == "teste") { 
      $url = "https://desenvolvedor.moip.com.br/sandbox/ws/alpha/EnviarInstrucao/Unica";
      $header = "Authorization: Basic " . base64_encode(Moip_Transparente_Model_Api::TOKEN_TEST . ":" . Moip_Transparente_Model_Api::KEY_TEST);
    }
    else {
      $url = "https://www.moip.com.br/ws/alpha/EnviarInstrucao/Unica";
      $header = "Authorization: Basic " . base64_encode(Moip_Transparente_Model_Api::TOKEN_PROD . ":" . Moip_Transparente_Model_Api::KEY_PROD);
    }

    $httpClient = new Zend_Http_Client($url);
    $httpClient->setHeaders($header);
    $httpClient->setRawData($xml);

    $responseMoIP = $httpClient->request('POST');

    $res = simplexml_load_string($responseMoIP->getBody());

    /* Facilitando visualização do que iremos pegar no XML.
     * Poderíamos usar $res->Resposta->etc
     * Utilizando dessa forma temos melhor visualização para quem desejar customizar o
     * código
     */
    $pgdireto_status = "";
    $pgdireto_mensagem = "";
    $status = "";
    $moipToken = "";
    $pgdireto_codigoretorno = "";
    $erro = "";



    if ($res) {
      if($res->Resposta->Erro)
        $erro = $res->Resposta->Erro;
      /*
       * Rotina POG para facilitar o que estamos pegando.
       */
      foreach ($res->children() as $child) {
        foreach ($child as $c) {
          if ($c->getName() == 'Token')
            $moipToken = $c;

          if ($c->getName() == "Status")
            $status = $c;

          foreach ($c as $pgdireto) {
            if ($pgdireto->getName() == "Status")
              $pgdireto_status = $pgdireto;

            if ($pgdireto->getName() == "Mensagem")
              $pgdireto_mensagem = $pgdireto;

            if ($pgdireto->getName() == "CodigoRetorno")
              $pgdireto_codigoretorno = $pgdireto;
          }
        }
      }
    }

    $result = array();
    $result['erro'] = $erro;
    if ($status == "Sucesso") {      
      $result['status'] = $status;
      $result['token'] = $moipToken;
      $result['pgdireto_status'] = $pgdireto_status;
      $result['pgdireto_mensagem'] = $pgdireto_mensagem;
      $result['pgdireto_codigoretorno'] = $pgdireto_codigoretorno;

    }
    else {
      $result['status'] = $status;
      $result['token'] = "";
      $result['pgdireto_status'] = "";
      $result['pgdireto_mensagem'] = "";
      $result['pgdireto_codigoretorno'] = "";
    }
    return $result;
  }

  /**
   * Gera URL para fazer redirecionamento para o MoIP, de acordo com o token passado
   * @access public
   * @param String $token
   * @return String
   */
  public function generateUrl($token) {
    if ($this->getAmbiente() == "teste")
      $url = "https://desenvolvedor.moip.com.br/sandbox/Instrucao.do?token=" . $token;
    else
      $url = "https://www.moip.com.br/Instrucao.do?token=" . $token;
    return $url;
  }

}
