<?php 
###################################################################################
# Script : index.php
# Versão : 1.0 (/var/www/html/2023/1_Basico/index.php)
# Autor  : Thiago Condé
# Data   : 2023-01-07 10:54:15
# Info   : 
# Requis.:
###################################################################################

#incluo o script do template responsavel por reescrever as variaveis e os blocos e não mistura de php com html
require("_libs/Template.php");
$versao_site = "./_libs/v5.3";

$html = new Template("./site/principal.html");
$html -> HTML_LIBS = "$versao_site";
// $html -> HTML_LIBS2 = "$versao_site";
$html -> VAR_NAO_EXISTE = "esta variavel nao existe";
$html->addFile ("$versao_site/cover.css"); // Simula erro sem variavel
// $html->addFile ("NOME_DA_VARIAVEL","$versao_site/cover.css"); // maneira correta

$html->HTML_CSS="$versao_site/cover.css";
$menu=array("inicio","serviços","contato");
$menu_active="serviços";

foreach ($menu as $nome) {
	$html-> MENU_NOME = $nome;

	if($menu_active == $nome)
		$html-> MENU_ACTIVE = "active";
	else
		$html-> MENU_ACTIVE = "";

	$html->block("B_MENU");
}

$html-> MENU_NOME = "menu 2";
$html->block("B_MENU");

$html->SITE_TITULO= "Basico 1";

$html->block("SITE_CONTEUDO");

# MENU
// $html->addFile("MENU", "./site/principal.html");


$html->show($mostrar_blocos=1,$comprimir=0,$mostrar_erros=1);


?>
