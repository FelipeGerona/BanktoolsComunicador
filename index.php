<?php
/**
 * SCRIPT DESTINADO A INTEGRAÇÃO DE DADOS COM BANKTOOLS ATRAVÉS DE COMUNICAÇÃO WEBSERVICE.
 * DESTINADO A PARCEIROS QUE UTILIZAM INTEGRAÇÃO ATRAVÉS DE TABELA DE BANCO DE DADOS;
 * BY FELIPE - 15/06/2021
 * VERSÃO 1.0
 */

$GLOBALS['WAIT'] = 60;
//Informar SQLEXPRESS para realizar comunicação local - Desenvolvimento.
$GLOBALS['TYPE'] = 'SQLEXPRESS';
$GLOBALS['AMBIENTE'] = 'HOMOLOGAÇÃO';
$GLOBALS['VERSAO'] = '3.0';
$GLOBALS['KEY'] = 'HomologarBanco';
$GLOBALS['API_URL'] = "https://hom-api.banktools.com.br/";
$GLOBALS['API_USER'] = 'homologa';
$GLOBALS['API_PASSWD'] = 'TczvO4Bf-HIKZtxNoReV';
$GLOBALS['API_TOKEN'] = null;
$GLOBALS['API_TOKEN_EXPIRES'] = null;

//Verificando configurações do ambiente;
if(!is_dir('log')) mkdir('log');
date_default_timezone_set('America/Sao_Paulo');
spl_autoload_register(function ($class_name) {
    $file = 'class/'.strtolower($class_name).'.class.php';
    if(!is_file($file)) $file = 'controller/'.strtolower($class_name).'.class.php';
    include $file;
});

//Validando Arquivo convenio.json
if( !Comunicacao::hasConvenios() ){
    LOG::setLOG('error', 'Adicionar arquivo convenios.json e reiniciar comunicação.', __FILE__, __METHOD__, __LINE__);
    die( "Processo Cancelado, favor providênciar arquivo convenios.json" );
}

//Validando arquivos de integração necessários.
if(!is_file('conexao.json')){
    echo "Bem vindo ao comunicador 2.1 - Banktools [".$GLOBALS['AMBIENTE']."]".PHP_EOL;
    echo "Para começar vamos criar a conexão com o SQLSERVER".PHP_EOL;
    SQLSERVER::setConexao();
    system('cls');
}
//Com base no arquivo Conexão é testado se há comunicação com o servidor
if(!$con = SQLSERVER::hasConexao()){
   echo "Erro na conexão com banco de dados, deseja tentar refazer arquivo de conexão? Digite 'sim' para confirmar.";
   $handle = fopen ("php://stdin","r");
   $line = fgets($handle);
   if(trim($line) == 'sim'){
       SQLSERVER::setConexao();
   }else{
       LOG::setLOG('error', "ERRO DE CONEXÃO SQLSERVER - Usuário não alterou configuração".PHP_EOL, __FILE__, __METHOD__, __LINE__);
   }
   die( "Processo Cancelado, favor verificar conexão com banco de dados." );
}else{
    $GLOBALS['Conexao'] = $con;
}
//Depois da validação Básica é criado o Loop de conexões.
while(1){
    if($GLOBALS['API_TOKEN_EXPIRES'] <= mktime(date("H"), date("i")-5, date("s"), date("m"), date("d"), date("Y")) ) Comunicacao::getToken();
    echo "[".date("d/m/y H:i:s")."] Busca Movimentos a serem Integrados (Status = I). ".PHP_EOL;
    Comunicacao::sendMovimentoIntegrar();
    Comunicacao::sendMovimentoProcessando();
    Comunicacao::countDown();
}
?>