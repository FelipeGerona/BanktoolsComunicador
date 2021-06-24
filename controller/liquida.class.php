<?php 

class Liquida{
    
    
    public static function sendLiquidaMovimento($convenio, $movimento){
        $url = "emprestimo/liquida/{$convenio->CODIGO_BANKTOOLS}";
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
        if($movimento->ADE) $data['ade'] = $movimento->ADE;
        $post = json_encode($data);
        //Enviado Comando
        $response = json_decode(Comunicacao::sendDataPost($url, $header, $post));
        if(isset($response->denied)){
            $fields = array(
                'BANKTOOLS_CONVENIO' => $convenio->CODIGO_BANKTOOLS,
                'BANKTOOLS_CODIGO' => 0,
                'BANKTOOLS_OPERACAO' => $convenio->TIPO,
                'MENSAGEM' => $response->denied
            );
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
                //Recebido porém invalido para operação.
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
    
    
    public static function getLiquidaMovimento($convenio, $movimento){
        $url = "emprestimo/liquida/{$convenio->CODIGO_BANKTOOLS}/{$movimento->BANKTOOLS_CODIGO}";
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
                        'VALORPARCELARESERVADA' => 0,
                        'PRAZORESERVA' => 0,
                        'VALORPARCELAAVERBADA' => 0,
                        'PRAZOAVERBADA' => 0,
                        'VALORAVERBADO' => 0,
                        'VALORREPASSEAVERBADO' => 0,
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
            if($acaoPROC) SQLSERVER::procExec($movimento->CODIGO, $acaoPROC);
        }
    }
    
}

?>