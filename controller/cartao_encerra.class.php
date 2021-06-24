<?php 

class Cartao_Encerra{
    
    
    public static function sendCartaoEncerraMovimento($convenio, $movimento){
        $url = "cartao/encerra/{$convenio->CODIGO_BANKTOOLS}";
        $header = array(
            "accept: application/json", 
            "authorization: {$GLOBALS['API_TOKEN']}", 
            "Content-Type: application/json"
        );
        //Dados a Serem enviados
        $data = array();
        $data['cpf'] = $movimento->CPF;
        $data['matricula'] = $movimento->MATRICULA;
        $data['contrato'] = $movimento->CONTRATO;
        $data['dataoperacao'] = $movimento->DH_OPERACAO->format("Y-m-d");
        if($movimento->PROPOSTA) $data['proposta'] = $movimento->PROPOSTA;
        $data['valorparcelaoperacao'] = $movimento->VALORPARCELA;
        $data['prazooperacao'] = $movimento->PRAZO;
        if($movimento->BANCO) $data['banco'] = $movimento->BANCO;
        if($movimento->AGENCIA) $data['agencia'] = $movimento->AGENCIA;
        if($movimento->CONTACORRENTE) $data['contacorrente'] = $movimento->CONTACORRENTE;
        if($movimento->DTNASCIMENTO) $data['dtnascimento'] = $movimento->DTNASCIMENTO->format("Y-m-d");
        if($movimento->IDENTIFICADOR) $data['senhaservidor'] = $movimento->IDENTIFICADOR;
        if($movimento->CODIGOUNICO) $data['codigounicooperacao'] = $movimento->CODIGOUNICO;
        if($movimento->USUARIO) $data['usuarioservidor'] = $movimento->USUARIO;
        if($movimento->TAXAJUROS) $data['taxacet'] = $movimento->TAXAJUROS;
        if($movimento->VALORIOF) $data['valoriof'] = $movimento->VALORIOF;
        
        $post = json_encode($data);
        //Enviado Comando
        $response = json_decode(Comunicacao::sendDataPost($url, $header, $post));
        if(isset($response->denied)){
            $fields = array(
                'BANKTOOLS_CONVENIO' => $convenio->CODIGO_BANKTOOLS,
                'BANKTOOLS_CODIGO' => 0,
                'BANKTOOLS_OPERACAO' => $convenio->TIPO,
                'MENSAGEM' => $response->denied,
                'STATUS' => 'E'
            );
            LOG::setLOG('error', "Convenio: ".$convenio->CODIGO_BANKTOOLS.PHP_EOL."Movimento: ".$movimento->CODIGO.PHP_EOL."Mensagem: ".$response->denied, __FILE__, __METHOD__, __LINE__);
        }elseif(isset($response->success) && !$response->success){
            if($response->data->code == 2){
                //Atualiza Movimento já enviado para a API.
                $fields = array(
                    'BANKTOOLS_CONVENIO' => $convenio->CODIGO_BANKTOOLS,
                    'BANKTOOLS_CODIGO' => $response->data->movimento,
                    'BANKTOOLS_OPERACAO' => $convenio->TIPO,
                    'MENSAGEM' => $response->data->message,
                    'STATUS' => 'P'
                );
            }else{
                $fields = array(
                    'BANKTOOLS_CONVENIO' => $convenio->CODIGO_BANKTOOLS,
                    'BANKTOOLS_CODIGO' => 0,
                    'BANKTOOLS_OPERACAO' => $convenio->TIPO,
                    'MENSAGEM' => $response->data->message,
                    'STATUS' => 'E'
                );
            }
            
        }elseif(isset($response->success) && $response->success){
            //Recebido e armazenado
            $fields = array(
                'BANKTOOLS_CONVENIO' => $convenio->CODIGO_BANKTOOLS,
                'BANKTOOLS_CODIGO' => $response->data->movimento,
                'BANKTOOLS_OPERACAO' => $convenio->TIPO,
                'MENSAGEM' => $response->data->message,
                'STATUS' => 'P'
            );
        }
        //Depois de tratar, atualiza o movimento
        SQLSERVER::updMovimento($movimento->CODIGO, $fields);
    }
    
    
    public static function getCartaoEncerraMovimento($convenio, $movimento){
        $url = "cartao/encerra/{$convenio->CODIGO_BANKTOOLS}/{$movimento->BANKTOOLS_CODIGO}";
        $header = array(
            "accept: application/json",
            "authorization: {$GLOBALS['API_TOKEN']}",
            "Content-Type: application/json"
        );
        //Enviado Comando
        $response = json_decode(Comunicacao::sendDataGET($url, $header));
        if(isset($response->denied)){
            $fields = array(
                'BANKTOOLS_CONVENIO' => $convenio->CODIGO_BANKTOOLS,
                'BANKTOOLS_CODIGO' => 0,
                'BANKTOOLS_OPERACAO' => $convenio->TIPO,
                'MENSAGEM' => $response->denied
            );
        }elseif(isset($response->success) && !$response->success){
            //Recebido porém invalido para operação.
            $fields = array(
                'MENSAGEM' => $response->data->mensagem,
                'STATUS' => 'E'
            );
        }elseif(isset($response->success) && $response->success){
            $acaoPROC = null;
            //Tratamento para verificar se continua processando ou não;
            $fields = array(
                'MENSAGEM' => trim($response->data->mensagem),
                'STATUS' => Comunicacao::checkStatusRetorno($response->data->status),
                'DH_ATUALIZACAO' => date("Y-m-d\TH:i:s"),
                'BANCO' => str_pad(intval($response->data->banco),3,'0',STR_PAD_LEFT),
                'AGENCIA' => substr(intval($response->data->agencia),8),
                'CONTACORRENTE' => substr(intval($response->data->contacorrente),15),
            );
            if($response->data->status == 'Negado'){
                $acaoPROC = 'REP'; 
            }elseif($response->data->status == 'Invalido' || $response->data->status == 'Cancelado' || $response->data->status == 'Encerrado' || $response->data->status == 'Suspenso'){
                $acaoPROC = 'PEN'; 
            }elseif($response->data->status == 'Averbado'){
                $acaoPROC = 'APR';
                $fields = array_merge($fields,
                    array(
                        'VALORPARCELARESERVADA' => $response->data->valorparcelareserva,
                        'PRAZORESERVA' => 0,
                        'VALORPARCELAAVERBADA' => $response->data->valorparcelaaverbado,
                        'PRAZOAVERBADA' => $response->data->prazoaverbado,
                        'VALORAVERBADO' => $response->data->valortotalaverbado,
                        'VALORREPASSEAVERBADO' => $response->data->valorrepasseaverbado,
                        'DH_CONFIRMACAO' => date("Y-m-d\TH:i:s",strtotime($response->data->dhaverbacao)),
                        'MOTIVO' => $response->data->codigofuncao,
                        'ADE' => trim($response->data->ade),
                        'COMPROVANTE' => trim($response->data->comprovante)
                    )  
                );
            }
        }
        //Depois de tratar, atualiza o movimento
        if(SQLSERVER::updMovimento($movimento->CODIGO, $fields)){
            die(var_dump($response->data));
            if($acaoPROC) SQLSERVER::procExec($movimento->CODIGO, $acaoPROC);
        }
    }
    
}

?>