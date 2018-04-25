<?php

/**
 * Class MeioPagamento - Library
 * 
 * @author Kainan Salles <kainan@abacos.com.br>
 * @link http://www.abacos.com.br Software para eventos
 */
defined('BASEPATH') OR exit('No direct script access allowed');

require_once (APPPATH . '/libraries/bradesco/Validator.php');

class MeioPagamento extends Validator {

    private $email;
    private $senha;
    private $merchant_id;
    private $chave_seguranca;
    
    /**
     * @param array $params - Array de dados para conexão com webservice Bradesco
     */
    public function __construct($params) {
        $this->email = $params['email'];
        $this->senha = $params['senha'];
        $this->merchant_id = $params['merchant_id'];
        $this->chave_seguranca = $params['chave_seguranca'];
    }

    /**
      @param array $data array de dados para criar boleto
     */
    public function createPedido($data) {
        $validate = $this->validateData($data);

        if ($validate) {
            $data_service_pedido = array(
                "numero" => $data['pedido_numero'],
                "valor" => $data['pedido_valor'],
                "descricao" => $data['pedido_descricao']);

            $data_service_comprador_endereco = array(
                "nome" => $data['comprador_nome'],
                "documento" => $data['comprador_documento'],
                "endereco" => array(
                    "cep" => $data['comprador_cep'],
                    "logradouro" => $data['comprador_logradouro'],
                    "numero" => $data['comprador_numero'],
                    "complemento" => $data['comprador_complemento'],
                    "bairro" => $data['comprador_bairro'],
                    "cidade" => $data['comprador_cidade'],
                    "uf" => $data['comprador_uf']));

            $data_service_boleto = array(
                "beneficiario" => $data['service_beneficiario'],
                "carteira" => $data['service_carteira'],
                "nosso_numero" => $data['service_nosso_numero'],
                "data_emissao" => $data['service_data_emissao'],
                "data_vencimento" => $data['service_data_vencimento'],
                "valor_titulo" => $data['service_valor_titulo'],
                "url_logotipo" => $data['service_url_logotipo'],
                "mensagem_cabecalho" => $data['service_mensagem_cabecalho'],
                "tipo_renderizacao" => $data['service_tipo_renderizacao']); //Modelo - PDF

            $data_service_request = array(
                "merchant_id" => $this->merchant_id,
                "meio_pagamento" => $data['meio_pagamento'],
                "pedido" => $data_service_pedido,
                "comprador" => $data_service_comprador_endereco,
                "boleto" => $data_service_boleto);

            $data_post = json_encode($data_service_request);

            $url = "https://homolog.meiosdepagamentobradesco.com.br/apiboleto" . "/transacao";

            $result = array_merge($data_service_request, json_decode($this->getCurlPOST($url, $data_post), true));

            return json_encode($result);
        } else {
            echo $validate;
        }
    }

    /**
      Gera um Token valido por 2h
     */
    public function getAuthLogista($merchant_id) {
        $url = "https://homolog.meiosdepagamentobradesco.com.br/SPSConsulta/Authentication/" . $merchant_id;
        return $this->getCurl($url);
    }

    /**
      @param array $data - array de dados
      @return json da consulta
     */
    public function getListPedidos($data) {
        $url = "https://homolog.meiosdepagamentobradesco.com.br/SPSConsulta/GetOrderListPayment/{$this->merchant_id}/boleto?token={$data['token']}&dataInicial={$data['data_inicial']}&dataFinal={$data['data_final']}&status={$data['status']}&offset={$data['offset']}&limit={$data['limit']}";
        return $this->getCurl($url);
    }

    /**
      @param var $token - token de autenticação
      @param var $numPedido - Numero do pedido
      @return json da consulta
     */
    public function getPedido($token, $numPedido) {
        $url = "https://homolog.meiosdepagamentobradesco.com.br/SPSConsulta/GetOrderById/{$this->merchant_id}?token={$token}&orderId={$numPedido}";
        return $this->getCurl($url);
    }

    /**
      @param array $data - array de dados para ser validados
      @return boolean
     */
    private function validateData($data) {
        unset($data['comprador_complemento']);
        $langDir = sprintf('%s/libraries/bradesco/lang/', dirname(dirname(__DIR__)));
        $v = new Validator($data, array(), 'pt-br', $langDir);
        foreach ($data as $key => $value) {
            $v->rule('required', $key);
        }
        $rules = [
            'integer' => [
                ['pedido_valor', 'service_valor_titulo']
            ],
            'length' => [
                ['merchant_id', 9],
                ['meio_pagamento', 3],
                ['service_carteira', 2],
                ['service_nosso_numero', 11],
            ],
            'lengthMin' => [
                ['comprador_documento', 11]
            ],
            'lengthMax' => [
                ['pedido_valor', 13],
                ['pedido_numero', 27],
                ['pedido_descricao', 255],
                ['comprador_nome', 40],
                ['comprador_documento', 14],
                ['comprador_logradouro', 70],
                ['comprador_numero', 10],
                ['comprador_bairro', 50],
                ['comprador_cidade', 50],
                ['comprador_uf', 2],
                ['service_beneficiario', 150],
            ],
            'date' => [
                ['service_data_emissao', 'service_data_vencimento']
            ],
        ];
        $v->rules($rules);

        if ($v->validate()) {
            return true;
        } else {
            // Errors
            echo json_encode($v->errors());
            return false;
        }
    }

    /**
      @param var $url - URL de requisição - Method GET
      @return json Status da operação
     */
    private function getCurl($url) {
        $mediaType = "application/json";
        $charSet = "UTF-8";

        //Configuracao do cabecalho da requisicao
        $headers = array();
        $headers[] = "Accept: " . $mediaType;
        $headers[] = "Accept-Charset: " . $charSet;
        $headers[] = "Accept-Encoding: " . $mediaType;
        $headers[] = "Content-Type: " . $mediaType . ";charset=" . $charSet;
        $AuthorizationHeaderBase64 = base64_encode($this->email . ":" . $this->chave_seguranca);
        $headers[] = "Authorization: Basic " . $AuthorizationHeaderBase64;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        return $result;
    }

    /**
      @param var $url - URL de requisição - Method POST
      @param array $data_post - configuração do boleto
      @return json Status da operação
     */
    private function getCurlPOST($url, $data_post) {
        $mediaType = "application/json";
        $charSet = "UTF-8";

        //Configuracao do cabecalho da requisicao
        $headers = array();
        $headers[] = "Accept: " . $mediaType;
        $headers[] = "Accept-Charset: " . $charSet;
        $headers[] = "Accept-Encoding: " . $mediaType;
        $headers[] = "Content-Type: " . $mediaType . ";charset=" . $charSet;
        $AuthorizationHeader = $this->merchant_id . ":" . $this->chave_seguranca;
        $AuthorizationHeaderBase64 = base64_encode($AuthorizationHeader);
        $headers[] = "Authorization: Basic " . $AuthorizationHeaderBase64;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        return $result;
    }

}
