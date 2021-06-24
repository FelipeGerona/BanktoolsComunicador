<?php 
/**
 * BY FELIPE - 15/06/2021
 * VERSAO: 1.3
 */

class SQLSERVER {
    
    public static function setConexao(){
        echo "Informe o HOST do SQLSERVER [".$GLOBALS['AMBIENTE']."]: ".PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $HOST = trim($line);
        echo "Informe o UID do SQLSERVER [".$GLOBALS['AMBIENTE']."]: ".PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $UID = trim($line);
        echo "Informe o PASSWORD do UID [".$GLOBALS['AMBIENTE']."]: ".PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $PASSWD = trim($line);
        echo "Informe o DATABASE a conectar [".$GLOBALS['AMBIENTE']."]: ".PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        $DATABASE = trim($line);
        echo "Os dados acima estão corretos? Digite 'sim' para confirmar".PHP_EOL;
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        if(trim($line) == 'sim'){
            echo "Gravando dados em AES-256-ECB com chave privada no arquivo conexao.json".PHP_EOL;
            self::saveConexao($HOST, $UID, $PASSWD, $DATABASE);
            echo "Testando a conexão criada.... ".PHP_EOL;
            if(!self::hasConexao()){
                echo "Conexão não realizada, deseja informar os dados de conexão novamente?  Digite 'sim' para refazer.".PHP_EOL;
                $handle = fopen ("php://stdin","r");
                $line = fgets($handle);
                if(trim($line) == 'sim'){
                    self::setConexao();
                }
            }
            
        }else{
            echo "Informado $line".PHP_EOL;
            echo "Processo cancelado, favor reiniciar o processo.".PHP_EOL;
            exit(1);
        }
    }
    
    public static function hasConexao(){
        $data = json_decode(file_get_contents('conexao.json'));
        if($GLOBALS['TYPE'] === 'SQLEXPRESS'){
            $connectionInfo = array( "Database"=> openssl_decrypt($data->database,"AES-256-ECB",$GLOBALS['KEY']) );
            $cn = sqlsrv_connect(openssl_decrypt($data->host,"AES-256-ECB",$GLOBALS['KEY'])."\\SQLEXPRESS", $connectionInfo);
            if($cn === false){
                echo "Erro na conexão SQLEXPRESS com banco de dados [".$GLOBALS['AMBIENTE']."].".PHP_EOL;
                echo "host: ".openssl_decrypt($data->host,"AES-256-ECB",$GLOBALS['KEY']).PHP_EOL;
                echo "database: ".openssl_decrypt($data->database,"AES-256-ECB",$GLOBALS['KEY']).PHP_EOL;
                $trace = "";
                foreach(sqlsrv_errors() as $erros){
                    $trace.= $erros['message'].PHP_EOL;
                }
                LOG::setLOG('error', "ERRO DE CONEXÃO SQLEXPRESS [".$GLOBALS['AMBIENTE']."]".PHP_EOL.$trace, __FILE__, __METHOD__, __LINE__);
                echo "Os seguintes erros ocorrem: ".PHP_EOL.$trace.PHP_EOL;
                echo "Processo cancelado, favor reiniciar o processo.".PHP_EOL;
                return false;
            }else{
                return $cn;
            }
        }else{
            $connectionOptions = array(
                "Database" => openssl_decrypt($data->database,"AES-256-ECB",$GLOBALS['KEY']),
                "Uid" => openssl_decrypt($data->uid,"AES-256-ECB",$GLOBALS['KEY']),
                "PWD" => openssl_decrypt($data->passwd,"AES-256-ECB",$GLOBALS['KEY'])
            );
            $cn = sqlsrv_connect( openssl_decrypt($data->host,"AES-256-ECB",$GLOBALS['KEY']), $connectionOptions );
            if($cn === false){
                echo "Erro na conexão com banco de dados [".$GLOBALS['AMBIENTE']."].".PHP_EOL;
                $trace = "";
                foreach(sqlsrv_errors() as $erros){
                    $trace.= $erros['message'].PHP_EOL;
                }
                LOG::setLOG('error', "ERRO DE CONEXÃO SQLSERVER [".$GLOBALS['AMBIENTE']."]".PHP_EOL.$trace, __FILE__, __METHOD__, __LINE__);
                echo "Os seguintes erros ocorrem: ".PHP_EOL.$trace.PHP_EOL;
                echo "Processo cancelado, favor reiniciar o processo.".PHP_EOL;
                return false;
            }else{
                return $cn;
            }
        }
    }
    
    private static function saveConexao($HOST,$UID,$PASSWD,$DATABASE){
        $data = [
            'dhConfig' => date("Y-m-d H:i:s"),
            'versao' => $GLOBALS['VERSAO'],
            'host' => openssl_encrypt($HOST,"AES-256-ECB",$GLOBALS['KEY']), 
            'uid' => openssl_encrypt($UID,"AES-256-ECB",$GLOBALS['KEY']),
            'passwd' => openssl_encrypt($PASSWD,"AES-256-ECB",$GLOBALS['KEY']),
            'database' => openssl_encrypt($DATABASE,"AES-256-ECB",$GLOBALS['KEY'])
        ];
        file_put_contents('conexao.json', json_encode($data,JSON_PRETTY_PRINT));
    }
    
    public static function updMovimento($Movimento, $fields){
        $query = "UPDATE dbo.PROPOSTAS_BANKTOOLS SET ";
        $query.= implode(', ', array_map(
            function ($v, $k) { return sprintf("%s='%s'", $k, $v); },
            $fields,
            array_keys($fields)
        ));
        $query.= " WHERE CODIGO = '$Movimento'";
        $data = sqlsrv_query($GLOBALS['Conexao'], $query);
        if($data == false){
            echo $query.PHP_EOL;
            echo sqlsrv_errors()[0][2];
            LOG::setLOG('error', "QUERY: $query".PHP_EOL.sqlsrv_errors()[0][2] , __FILE__, __METHOD__, __LINE__);
            return false;
        }else{
            LOG::setLOG('info', "QUERY: $query", __FILE__, __METHOD__, __LINE__);
            return true;
        }
    }
    
    public static function procExec($movimento, $acao){
        $query = "EXEC dbo.DYC_SP_ESTEIRA_BANKTOOLS @IDBANKTOOLS = $movimento, @ACAO = '$acao'";
        $data = sqlsrv_query($GLOBALS['Conexao'], $query);
        if($data === false){
            echo $query;
            echo sqlsrv_errors()[0][2];
            LOG::setLOG('error', "QUERY: $query".PHP_EOL.sqlsrv_errors()[0][2] , __FILE__, __METHOD__, __LINE__);
            return false;
        }else{
            return true;
        }
    }
    
    public static function getListaMovimentosProcessando(){
        $query = "SELECT * FROM dbo.PROPOSTAS_BANKTOOLS WITH (NOLOCK) WHERE STATUS = 'P'";
        $rows = sqlsrv_query($GLOBALS['Conexao'], $query);
        if($rows == false){
            $trace = "";
            foreach(sqlsrv_errors() as $erros){
                $trace.= $erros['message'].PHP_EOL;
            }
            LOG::setLOG('error', "QUERY: $query".PHP_EOL.$trace, __FILE__, __METHOD__, __LINE__);
        }
        return $rows;
    }
    
    public static function getListaMovimentosIntegrar(){
        $query = "SELECT * FROM dbo.PROPOSTAS_BANKTOOLS WITH (NOLOCK) WHERE STATUS = 'I'";
        $rows = sqlsrv_query($GLOBALS['Conexao'], $query);
        if($rows == false){
            $trace = "";
            foreach(sqlsrv_errors() as $erros){
                $trace.= $erros['message'].PHP_EOL;
            }
            LOG::setLOG('error', "QUERY: $query".PHP_EOL.$trace, __FILE__, __METHOD__, __LINE__);
        }
        return $rows;
    }
    
}

?>