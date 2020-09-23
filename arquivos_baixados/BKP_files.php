<?php 
//------------------------------------------------------------------------
/**
  * - @Versão 2.0
  |
  * - @package BKP files And SQL
  |
  * - @author Josué Queiroz <josuestz5@gmail.com>
  |
  * - @Codigo abaixo faz bkp do SQL e do FTP
  |
  * - @Para automatizar ainda mais, coloque esse codigo em um rotina (Cron/Job)
  |
  * - @Email de contato: josuestz5@gmail.com (SEO) / si@sertaozinhoindustrial.com.br
  |
  * - @license https://4ind.org/ AND https://sertaozinhoindustrial.com.br
  |
  * - @link https://4ind.org/ AND https://sertaozinhoindustrial.com.br
*/


// -------------------------- PROGRAMAÇÃO E RECOMENDAÇÕES --------------------------
// 1) zip sql's
// 2) zip files's
// 3) zip -> sql.files.zip
// 4) send -> All.Zip 
// 5) Recomendo definir como a cada 15 dias, sendo um dia 15 e o proximo dia 28 as 01:00 para não sobrecarregar o servidor
// ---------------------------------------------------------------------------------


// Definindo tempo maximo que o documento pode rodar sem interrupsão do servidor
 // verifique o peso dos arquivos em media o servidor faz 250mb em 15 segundos
 // Forma de calcular (60*60*( 5 hora)) = 18000 ->> Defenir mediante a necessidade e capacidade do servidor
ini_set('max_execution_time', 18000);



//********** Referencia do dia ************/
$hoje = date("Y-m-d");




//--------------------------------------------------------___________------------------------------------------------------------
//-------------------------------------------------------- BKP DO SQL -----------------------------------------------------------
//----------------------------------------------------- SETANDO O BANCO ---------------------------------------------------------
// Esse codigo a baixo funciona a orientação a objetos, para salvar mais de um banco basta replicar esse codigo a baixo! >>>>>>>>

$mysqlUserName      = "seu_usuario";
$mysqlPassword      = "Sua_senha";
$mysqlHostName      = "Caminho_do_banco"; //caso seja local o banco pode deixar vazio!
$DbName             = "nome_do_seu_banco";
$bkp_name           = "nome_que_deseja_quando_salvar.sql";// nome que vai quando zipar
$backup_name        = "mybackup.sql"; //Não mexer
$tables             = array("asset_branch", "asset_category", "asset_products", "asset_user", "login", "message", "replace_app");

Export_Database($mysqlHostName,$mysqlUserName,$mysqlPassword,$DbName,$bkp_name,  $tables=false, $backup_name=false );

//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> FIM <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

//---------------------------------------------------------------------------------------------------------------------------------
//---------------------------------------------[ Fazendo a conexao e separando o tabela ]------------------------------------------
//---------------------------------------------------------------------------------------------------------------------------------
function Export_Database($host,$user,$pass,$name,$bkp_name,  $tables=false, $backup_name=false ){
  $mysqli = new mysqli($host,$user,$pass,$name);
  $mysqli->select_db($name);
  $mysqli->query("SET NAMES 'utf8'");

  $queryTables    = $mysqli->query('SHOW TABLES');
  while($row = $queryTables->fetch_row()){
    $target_tables[] = $row[0];
  }
  if($tables !== false)
  {
    $target_tables = array_intersect( $target_tables, $tables);
  }
  foreach($target_tables as $table){
    $result         =   $mysqli->query('SELECT * FROM '.$table);
    $fields_amount  =   $result->field_count;
    $rows_num=$mysqli->affected_rows;
    $res            =   $mysqli->query('SHOW CREATE TABLE '.$table);
    $TableMLine     =   $res->fetch_row();
    $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

    for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0)
    {
      while($row = $result->fetch_row()){ //regra de 3 par a para definir que o tatal é igual a 100% e inicio o ciclo do while
        if ($st_counter%100 == 0 || $st_counter == 0 )
        {
          $content .= "\nINSERT INTO ".$table." VALUES";
        }
        $content .= "\n(";
        for($j=0; $j<$fields_amount; $j++)
        {
          $row[$j] = str_replace('"', "'", str_replace("\n","\\n", addslashes($row[$j]) ));
          if (isset($row[$j]))
          {
            $content .= '"'.$row[$j].'"' ;
          }
          else
          {
            $content .= '""';
          }
          if ($j<($fields_amount-1))
          {
            $content.= ',';
          }
        }
        $content .=")";
        if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num)
        {
          $content .= ";";
        }
        else
        {
          $content .= ",";
        }
        $st_counter=$st_counter+1;
      }
    } $content .="\n\n\n";
  }
  $hoje = date("Y-m-d");
  $backup_name = $backup_name ? $backup_name : $name."_(".$hoje.")_.sql";

//------------------ Esse codigo abaixo salva o arquivo no FTP com a extenssão .sql, atualmente Até o momento do Zip! -------------------
// header("Pragma: no-cache");
// Não expira
  header("Expires: 0");
// E aqui geramos o arquivo com os dados mencionados acima!
  $fp = fopen("$backup_name", "a"); 
// seta os dados e cabecalhos
  $escreve = fwrite($fp, $header."\n".$content); 
// Fecha o arquivo  
  $bd_zip = fclose($fp);
//--------------------------------------------------------------------------------------------------------------------------------------!
//----------------------------- Esse codigo abaixo salva o arquivo no FTP com a extenssão SQL.ZIP --------------------------------------!

// Inicia a instância ZipArchive
  $zip = new ZipArchive;
// Cria um novo arquivo .zip chamado minhas_fotos.zip
  $zip->open($name."_".$hoje.".zip", ZipArchive::CREATE);
// Adiciona um arquivo à pasta
  $zip->addFile("$backup_name","$backup_name_$bkp_name");
// Fecha a pasta e salva o arquivo
  $zip->close();
}


//-------------------------------------------------------
//Guardando nome do sql para excluir os residos do FTP
//Gardar o nome para excluir depois o arquivo gerado .sql
$nome_sql1 = "nome_do_seu_banco".$hoje.".zip";
$arquivio_sql1 = "nome_do_seu_banco(".$hoje.")_.sql";


//-------------------------------------------------------------------------------------------------------------------------------
//-------------------------------------------------------- BKP DO FTP------------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------------------


// Apaga o backup anterior para que ele não seja compactado junto com o atual.
if (file_exists($arquivo)) unlink(realpath($arquivo)); 

// diretório que será compactado


///----- Para salvar mais pastas do mesmo servidor basta replicar esse codigo >>>
// nome do zip
$NOME_SEU_ZIP = 'BKP_FILES_SUA_PASTA'.$hoje;
$arquivo = $NOME_SEU_ZIP.'.zip';
$diretorio = "../pasta_bkp_teste/";  
$Pasta     = $arquivo;
CriarZip($diretorio, $Pasta, $arquivo);
//>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> FIM <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

function CriarZip($diretorio, $Pasta, $arquivo){

  $rootPath = realpath($diretorio);
// Inicia o Módulo ZipArchive do PHP
  $zip = new ZipArchive();
  $zip->open($arquivo, ZipArchive::CREATE | ZipArchive::OVERWRITE);
// Compactação de subpastas
  $files = new RecursiveIteratorIterator(
   new RecursiveDirectoryIterator($rootPath),
   RecursiveIteratorIterator::LEAVES_ONLY
 );

// Varre todos os arquivos da pasta
  foreach ($files as $name => $file)
  {
   if (!$file->isDir())
   {
    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen($rootPath) + 1);
// Adiciona os arquivos no pacote Zip.
    $zip->addFile($filePath, $relativePath);
  }

}

// Encerra a criação do pacote .Zip
$zip->close();

   $Pasta = $Pasta.'.zip'; // define o nome do pacote Zip gerado na 9
   if(isset($Pasta) && file_exists($Pasta)){ // faz o teste se a variavel não esta vazia e se o arquivo realmente existe
      switch(strtolower(substr(strrchr(basename($Pasta),"."),1))){ // verifica a extensão do arquivo para pegar o tipo
      	case "pdf": $tipo="application/pdf"; break;
      	case "exe": $tipo="application/octet-stream"; break;
      	case "zip": $tipo="application/zip"; break;
      	case "doc": $tipo="application/msword"; break;
      	case "xls": $tipo="application/vnd.ms-excel"; break;
      	case "ppt": $tipo="application/vnd.ms-powerpoint"; break;
      	case "gif": $tipo="image/gif"; break;
      	case "png": $tipo="image/png"; break;
      	case "jpg": $tipo="image/jpg"; break;
      	case "mp3": $tipo="audio/mpeg"; break;
         case "php": // deixar vazio por seurança
         case "htm": // deixar vazio por seurança
         case "html": // deixar vazio por seurança
       }
     }
   }

//-------------------------------- Função para baixar o arquivo gerado por navegador --------------------------------------------

// Baixa somente o arquivo do ftp não o banco de dados SQL

      // header("Content-Type: ".$tipo); // informa o tipo do arquivo ao navegador
      // header("Content-Length: ".filesize($arquivo)); // informa o tamanho do arquivo ao navegador
      // header("Content-Disposition: attachment; filename=".basename($arquivo)); // informa ao navegador que é tipo anexo e faz abrir a janela de download, tambem informa o nome do arquivo
      // readfile($arquivo); // lê o arquivo
      // exit; // aborta pós-ações
//-------------------------------------------------------------------------------------------------------------------------------






//-------------------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------- NOTIFICAÇÃO DE BKP --------------------------------------------------------
//-------------------------------------------------------------------------------------------------------------------------------





   //Seu Email
   $to = "Eamil_de_quem_vai_ser_notificado";

   //Titulo do email com data do BKP
   $dia_referente = date('d/m/Y');
   $subject = "Backup $dia_referente";

   //corpo do email
   //IMG excemplo anexado
   $body = "
   <img height='380' alt='Residentes' align='center' style='border-radius:6px;margin:0 auto' src='https://sertaozinhoindustrial.com.br/loja/images/vazio.gif'>
   ";


   //cabeçalhos
   $headers .= "Content-type: text/html; charset=UTF8 \r\n";
   $headers .= "From: Backup<bkp@seu_dominio.com/>\r\n";
   $headers .= "Reply-To: $to\r\n";
   $headers .= "Return-path: bkp@seu_dominio.com/";


   // Send email | Dispara email  
   mail($to, $subject, $body, $headers);



   $hoje = date('Y-m-d');

//--------------------------------------------------------------------------------------------------------------------------------
//------------------------- Esse codigo abaixo salva todos as informações geradas acima em UM unico zip! ------------------------- 
//--------------------------------------------------------------------------------------------------------------------------------
   
// Inicia a instância ZipArchive
   $zip = new ZipArchive;

// Cria um novo arquivo .zip chamado minhas_fotos.zip
   $zip->open("ALL_SQL_AND_FILES_".$hoje.".zip", ZipArchive::CREATE);

// Adiciona um arquivo à pasta
   $zip->addFile("$nome_sql1", "$nome_sql1");
   $zip->addFile("$NOME_SEU_ZIP.zip", "$NOME_SEU_ZIP.zip");

// Fecha a pasta e salva o arquivo
   $zip->close();



//------------ Excluir arquivo gerados anteriores ao zip Total ---------

   $diretorio = "./";
   unlink ($diretorio."$NOME_SEU_ZIP.zip");
   unlink ($diretorio.$nome_sql1);
   unlink ($diretorio.$arquivio_sql1);

//------------------------------------------------------------------
   ?>