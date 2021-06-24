# BanktoolsComunicador
Comunicador com Tabela FUNCAO
Para instalação do comunicador deve-se proceder com a descompactação do banktools_integrador.zip no diretório raiz da máquina client.
Configurando o comunicador.
Após a preparação do ambiente e a instalação do comunicador será solicitada a configuração da conexão, para o caso da primeira instalação.
Nesse novo modelo foi substituído a utilização de informações em base64 para o padrão RSA 256 com chave privada.

No prompt de comando do Windows deve-se seguir as seguintes etapas:

Acessar o diretório aonde foi descompactado o comunicador
 
Executar o PHP do diretório da instalação dele para o arquivo index.php * 
 
Após confirmar a linha de comando será solicitada informações da conexão com o banco de dados.

 
Informar o HOST de acesso e utilizar <ENTER> para confirmar

 
Informe o usuário (UID) para acesso ao banco de dados <ENTER>

 
Informe a Senha do usuário <ENTER>
 
Informe o nome do banco de dados para conexão e utilize <ENTER> para confirmar.

 
Após preenchimento dos dados será questionado se os dados disponibilizados estão corretos, digite sim e pressione <ENTER> para confirmar, caso os dados estejam incorretos basta dar enter para que o sistema solicite novamente os dados de conexão.
 
Após confirmação o sistema irá gerar o arquivo conexao.json no próprio diretório e irá tentar realizar a conexão com o banco de dados conforme imagem acima.

Em caso de problema de conexão será apresentada a mensagem de erro da conexão e será questionado se deseja informar os dados de conexão novamente, digite sim <ENTER> para corrigir os dados informados.

Em caso de sucesso na conexão o sistema irá iniciar o monitoramento da tabela PROPOSTA_BANKTOOLS do banco de dados informado.
 

Para a configuração atual está prevista interações (Envio de dados e Retorno de dados) a cada 60 segundos.  A validade do token do oAuth2.0 é de 60 minutos, após esse período o comunicador irá solicitar novo token de autenticação para continuar o monitoramento das operações.
