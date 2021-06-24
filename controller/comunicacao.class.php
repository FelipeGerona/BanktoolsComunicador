<?php

class Comunicacao{
    
    public static function hasConvenios(){
        echo "Verificando existencia de arquivo de convênios (convenios.json)".PHP_EOL;
        if(!is_file('convenios.json')){
            echo "Arquivo convenios.json não localizado no diretório IntegradorBanktools.".PHP_EOL;
            LOG::setLOG('error', 'Arquivo convenios.json não localizado', __FILE__, __METHOD__, __LINE__);
            return false;
        }
        return true;
    }
    
    public static function getConvenio($convenio){
        $convenios = json_decode(file_get_contents('convenios.json'));
        foreach($convenios->CONVENIOS as $item){
            if($item->CODIGO_BANCO == $convenio){
                return $item;
            }
        }
        return false;
    }
    
    public static function countDown(){
        for($i = $GLOBALS['WAIT']; $i > 0; $i--){
            echo "\rProxima execução em: ".str_pad($i, 2, '0', STR_PAD_LEFT);
            sleep(1);
        }
        echo "\r";
    }
    
    public static function checkStatusRetorno($status){
        if( $status == 'Processando' || $status == 'Consulta' || $status == 'Parcial' || $status == 'Reservado' ){
            return 'P';
        }elseif($status == 'Cancelado' || $status == 'Suspenso' || $status == 'Encerrado' || $status == 'Negado'){
            return 'E';
        }elseif($status == 'Invalido'){
            return 'C';
        }elseif($status == 'Averbado'){
            return 'E';
        }else{
            die("Retorno $status");
        }
    }
    
    public static function sendMovimentoIntegrar(){
        $sqlserver = new SQLSERVER();
        $rows = $sqlserver->getListaMovimentosIntegrar();
        $count = 0;
        while( $movimento = sqlsrv_fetch_object($rows) ){
            $count++;
            $convenio = self::getConvenio($movimento->CONVENIO);
            if(strtolower($convenio->TIPO) == 'emprestimo'){
                Emprestimo::sendEmprestimoMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'refinanciamento'){
                Refinanciamento::sendRefinanciamentoMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'portabilidade'){
                Portabilidade::sendPortabilidadeMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'liquida'){
                Liquida::sendLiquidaMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'cartao_atualiza'){
                Cartao_Atualiza::sendCartaoAtualizaMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'cartao_encerra'){
                Cartao_Encerra::sendCartaoEncerraMovimento($convenio, $movimento);
            }else{
                die("Convenio: ".strtolower($convenio->TIPO).PHP_EOL."Movimento: ".$movimento->CODIGO);
            }
        }
        echo "$count movimentos enviados.".PHP_EOL;
    }
    
    public static function sendMovimentoProcessando(){
        $sqlserver = new SQLSERVER();
        $rows = $sqlserver->getListaMovimentosProcessando();
        $count = 0;
        while( $movimento = sqlsrv_fetch_object($rows) ){
            $count++;
            $convenio = self::getConvenio($movimento->CONVENIO);
            if(!$convenio) continue;
            if(strtolower($convenio->TIPO) == 'emprestimo'){
                Emprestimo::getEmprestimoMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'refinanciamento'){
                Refinanciamento::getRefinanciamentoMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'portabilidade'){
                Portabilidade::getPortabilidadeMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'liquida'){
                Liquida::getLiquidaMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'cartao_atualiza'){
                Cartao_Atualiza::getCartaoAtualizaMovimento($convenio, $movimento);
            }elseif(strtolower($convenio->TIPO) == 'cartao_encerra'){
                Cartao_Encerra::getCartaoEncerraMovimento($convenio, $movimento);
            }
        }
        echo "$count movimentos processados.".PHP_EOL;
    }
    
    
    public static function getToken(){
        echo "Buscando Token para autenticação".PHP_EOL;
        $url = 'autentication';
        $authorization = base64_encode($GLOBALS['API_USER'].":".$GLOBALS['API_PASSWD']);
        $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");
        $post = "grant_type=client_credentials";
        $response = json_decode(self::sendDataPost($url, $header, $post));
        if(isset($response->denied)){
            LOG::setLOG('error', $response->denied, __FILE__, __METHOD__, __LINE__);
            die('Erro na conexão com API'.PHP_EOL.$response->denied);
        }elseif(isset($response->access_token)){
            echo "Token criado com sucesso.".PHP_EOL;
            $GLOBALS['API_TOKEN'] = $response->access_token;
            $GLOBALS['API_TOKEN_EXPIRES'] = $response->expire_in;
        }else{
            LOG::setLOG('error', 'Timeout API', __FILE__, __METHOD__, __LINE__);
            die('Erro na conexão com API - Timeout');
        }
    }
    
    public static function sendDataPost($url, $header, $post){
        echo "Comunicando com Endereço: ".$GLOBALS['API_URL'].$url.PHP_EOL;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $GLOBALS['API_URL'].$url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    public static function sendDataGET($url, $header){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $GLOBALS['API_URL'].$url,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
}

?>