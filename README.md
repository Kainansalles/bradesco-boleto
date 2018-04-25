# Boleto Bancário

Meio de pagamento - Bradesco (Boleto Bancário) API


## Getting Started

Esse módulo foi desenvolvido para atender as necessidades de um projeto em Codelgniter, ou seja, uma library. em um futuro proximo realizarei atualizações para atender diversas plataformas.

### Prerequisites

```
PHP 5.6 +
```

## Step 1 - Iniciando a aplicação

Para conseguir os parametros a seguir:
https://homolog.meiosdepagamentobradesco.com.br/manual/Manual_BoletoBancario.pdf. 

Caso não tenha acesso, entre em contato com o seu gerente bancário.

```php
    private $params = array(
        'email' => 'email@exemple.com.br',
        'merchant_id' => '999999999',
        'chave_seguranca' => 'kPme=$jY#UaC;P*BT#YXwK2RnO-mYulOKsoDEoDo956'
    );

    $this->load->library('bradesco/MeioPagamento', $this->params);
```
## Step 2 - Criando um pedido

```php
    $data_service_pedido = array(
        "merchant_id" => $this->params['merchant_id'],
        "meio_pagamento" => "300",
        "pedido_numero" => "0-9_A-Z_.MACH99",
        "pedido_valor" => "15000",
        "pedido_descricao" => "Descritivo do pedido",
        "comprador_nome" => "nome do comprador",
        "comprador_documento" => "99999999999",
        "comprador_cep" => "99999999",
        "comprador_logradouro" => "R. Amizade Amizade",
        "comprador_numero" => "7",
        "comprador_complemento" => "",
        "comprador_bairro" => "Amizade",
        "comprador_cidade" => "Suzano",
        "comprador_uf" => "SP",
        "service_beneficiario" => "NOME DA EMPRESA",
        "service_carteira" => "26", // pode-se usar 25 ou 26
        "service_nosso_numero" => "99999999999",
        "service_data_emissao" => "2018-04-18",
        "service_data_vencimento" => "2018-04-19",
        "service_valor_titulo" => '2',
        "service_url_logotipo" => "url_da_imagem", // Formato: 80x120px
        "service_mensagem_cabecalho" => "mensagem de cabecalho",
        "service_tipo_renderizacao" => 2 // 0=HTML; 1=Tela com link PDF; 2=PDF; 
    );
    
    $comprador = $this->meiopagamento->createPedido($data_service_pedido);
```

## Gerando Token de autenticação

É obrigatório que você gere um Token de autenticação antes consumir um pedido.

```php
    private function gerarToken() {
      $result = json_decode($this->meiopagamento->getAuthLogista($this->params['merchant_id']));      
      return $result->token->token;
    }
```
## Consultando pedidos

Para retornar apenas um pedido, é necessário que você envie o numero do pedido.

```php
    $numPedido = $this->input->get('numPedido');
    $result = $this->meiopagamento->getPedido($this->gerarToken(), $numPedido);
```

Para retornar uma lista de pedidos é necessário informar um array de argumentos.
```php
    $data = array(
        'token' => $this->gerarToken(),
        'data_inicial' => date('Y/m/d'),
        'data_final' => date('Y/m/d', strtotime("+1 days")),
        'status' => 0, // 0 (Todos os pedidos) ou 1 (Pedidos pagos).
        'offset' => 1, // número do registro para início da consulta(Recomendado utilizar 1).
        'limit' => 1500 // número máximos de registros retornados nesta consulta.
    );
    
    $boletos = $this->pagamento->getListPedidos($data);
```
## Observações
- Todos os retornos da library são **JSON**.
- Caso não tenha acesso ao ambiente de homologação, entre em contato com o seu **gerente bancário**.
