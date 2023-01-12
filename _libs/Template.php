<?php
# fonte: raelcunha.com/template (template do site e não do github (contem outras melhorias, mas nunca parei pra ver), esta versão é a mais antiga)
# fiz diversas melhorias e acidionei diversos recursos
# Thiago Condé deste 2011..

class Template {
	private $vars = array();
	private $values = array();
	private $properties = array();
	private $instances = array();
	private $blocks = array();
	private $parents = array();
	private $accurate;
	private $filename;
	private $erros_template= array();
	private static $REG_NAME = "([[:alnum:]]|_)+";

	public function __construct($filename, $string = null ,$accurate = false){

		$this->accurate = $accurate;
		$this->filename = $filename;

		if ($filename == "texto")
			$this->ler_texto($string);
		else{
			$this->loadfile(".", $filename);
		}
	}

	public function addFile($varname="", $filename=""){

		if($filename==""){
			if (strpos($varname, "/") !== false){
				$filename=$varname;
				$varname=" ";
			}
		}

		if(!$this->existe_var($varname)){
			$this->erros_template[$varname] = "***<u>addFile</u>: Não existe <font COLOR='#f00'>{".$varname."}</font> para adicionar o arquivo: <font COLOR='#f00'>$filename</font><br />";
			$GLOBALS['filename'] = $filename;
		}

		if (!file_exists($filename))
			$this->erros_template[$filename] = "***<u>AVISO</u>: addFile: O Arquivo não existe no caminho <font COLOR='#f00'>$filename</font> não existe<br />";
		$this->loadfile($varname, $filename);
	}


	public function __set($varname, $value){

		if (isset($this->filename))
			if(!$this->existe_var($varname))
				$this -> erros_template[$varname] = "*<u>AVISO</u>: A variavel <font COLOR='#f00'>{".$varname."}</font> não existe no arquivo: <i><font COLOR='#f00'>". basename($this->filename) ."</font></i>" . "<br />" ;
		$stringValue = $value;
		if(is_object($value)){
			$this->instances[$varname] = $value;
			if(!array_key_existe_var($varname, $this->properties)) $this->properties[$varname] = array();
			if(method_existe_var($value, "__toString")) $stringValue = $value->__toString();
			else $stringValue = "Object";
		} 

		$this->setValue($varname, $stringValue);
		return $value;
	}

	public function __get($varname){
		if (isset($this->values["{".$varname."}"]))
			return $this->values["{".$varname."}"];
		else
			$this->erros_template[$varname]="AVISO: A variavel $varname não existe!!";
	}

	public function existe_var($varname){
		return in_array($varname, $this->vars);
	}
	public function existe_bloco($blockname){
		return in_array($blockname, $this->blocks);
	}

	public function exibirBlocos(){
		foreach ($this->vars as $key => $val) {

			if (@$teste == "")
				$teste ="<u>*Todos os Blocos:</u> {".$val."}"; 
			else
				$teste = @$teste . ", {" . $val  ."}";
		}
		$this->erros_template .= " <br>" .  $teste . "<br><br>";        
	}

	private function loadfile($varname, $filename) {
		if (!file_exists($filename)){
			$this->erros_template[$filename] = "***<u>AVISO</u>: O Arquivo <font COLOR='#f00'><i>".basename($filename)."</i></font> não existe!!<br />";
		}else{
			#$str = preg_replace("/<!---.*?--->/smi", "", file_get_contents($filename));
			$str = preg_replace("/<!---.*?--->/smi", "", file_get_contents($filename));
			$blocks = $this->recognize($str, $varname);
			if (empty($str))
				$this->erros_template .= "<font COLOR='#f00'><u>*AVISO</u>: O Arquivo <i>$filename</i> está vazio!!</font><br />";
			$this->setValue($varname, $str);
			$this->createBlocks($blocks, $filename);}
	}

	#condtec 17-9-2013
	public function ler_texto($texto){
		// Reading file and hiding comments
		$str = preg_replace("/<!---.*?--->/smi", "", $texto);
		$blocks = $this->recognize($str, ".");
		if (empty($str))
			$this->erros_template .= "<font COLOR='#f00'><u>*AVISO</u>: O Texto está vazio!!</font><br />";
		$this->setValue(".", $str);
		$this->createBlocks($blocks, "texto");
	}

	#condtec 11-9-2013
	public function add_string($varname, $string){
		if(!$this->existe_var($varname)) throw new InvalidArgumentException("add_string: A var $varname não existe");
		$this->abrir_string($varname, $string);
	}
	#condtec 11-9-2013	
	private function abrir_string($varname, $string) {
		if ($string == ""){
			$this->erros_template .= "<font COLOR='#f00'><u>*AVISO</u>: A string esta vazia!!</font><br />";
			//exit;
		}else{
			// Reading file and hiding comments
			$str = preg_replace("/<!---.*?--->/smi", "", $string);
			$blocks = $this->recognize($str, $varname);
			if (empty($str))
				$this->erros_template .= "<font COLOR='#f00'><u>*AVISO</u>: O Arquivo <i>$string</i> está vazio!!</font><br />";
			$this->setValue($varname, $str);
			$this->createBlocks($blocks, $string);}
	}


	function organiza($tudo ,$html=0, $css=0,$java=0){  
		return $this->limpa_html($tudo ) ;
	}
	function limpa_css($css){  
		$css = str_replace('; ',';',str_replace(' }','}',str_replace('{ ','{',str_replace(array("\r\n","\r","\n","\t",'  ','    ','    '),"",preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$css))))); 
		return $css;
	}

	function limpa_html($html){
		$pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\s\/\/.*))/';
		$html = preg_replace($pattern, '', $html); //apaga comentarios em js

		preg_match_all('!(&lt;(?:code|pre).*&gt;[^&lt;]+&lt;/(?:code|pre)&gt;)!',$html,$pre);#exclude pre or code tags<br />
		$html = preg_replace('!&lt;(?:code|pre).*&gt;[^&lt;]+&lt;/(?:code|pre)&gt;!', '#pre#', $html);#removing all pre or code tags<br />
		$html = preg_replace('/<!--(.|\s)*?-->/', '', $html);#removing HTML comments<br />
		$html = preg_replace('/[\r\n\t]+/', ' ', $html);#remove new lines, spaces, tabs<br />
		$html = preg_replace('/&gt;[\s]+&lt;/', '&gt;&lt;', $html);#remove new lines, spaces, tabs<br />
		$html = preg_replace('/[\s]+/', ' ', $html);#remove new lines, spaces, tabs<br />
		if(!empty($pre[0]))
			foreach($pre[0] as $tag)
				$html = preg_replace('!#pre#!', $tag, $html,1);#putting back pre|code tags<br />
		return $html;
	}

	private function recognize(&$content, $varname){
		$blocks = array();
		$queued_blocks = array();
		foreach (explode("<!--", $content) as $line ) {
			$line ="<!--".$line;
			if (strpos($line, "{")!==false) $this->identifyVars($line);
			if (strpos($line, "<!--")!==false) $this->identifyBlocks($line, $varname, $queued_blocks, $blocks);
		}
		return $blocks;
	}

	private function identifyBlocks(&$line, $varname, &$queued_blocks, &$blocks){
		$reg = "/<!--\s*INI\s+(".self::$REG_NAME.")\s*-->/sm";
		preg_match($reg, $line, $m);
		if (1==preg_match($reg, $line, $m)){
			if (0==sizeof($queued_blocks)) $parent = $varname;
			else $parent = end($queued_blocks);
			if (!isset($blocks[$parent])){
				$blocks[$parent] = array();
			}
			$blocks[$parent][] = $m[1];
			$queued_blocks[] = $m[1];
		}
		$reg = "/<!--\s*FIM\s+(".self::$REG_NAME.")\s*-->/sm";
		if (1==preg_match($reg, $line)) array_pop($queued_blocks);
	}

	private function identifyVars(&$line){
		$r = preg_match_all("/{(".self::$REG_NAME.")((\-\>(".self::$REG_NAME."))*)?}/", $line, $m);
		if ($r){
			for($i=0; $i<$r; $i++){
				// Object var detected
				if($m[3][$i] && (!array_key_existe_var($m[1][$i], $this->properties) || !in_array($m[3][$i], $this->properties[$m[1][$i]]))){
					$this->properties[$m[1][$i]][] = $m[3][$i];
				}
				if(!in_array($m[1][$i], $this->vars)) $this->vars[] = $m[1][$i];
			}
		}
	}

	private function createBlocks(&$blocks, $filename) {
		$this->parents = array_merge($this->parents, $blocks);
		foreach($blocks as $parent => $block){
			foreach($block as $chield){
				$this->blocks[] = $chield;
				$this->setBlock($parent, $chield,$filename);
			}
		}
	}


	private function setBlock($parent, $block,$filename) {
		$name = "B_".$block;
		$str = $this->getVar($parent);
		if($this->accurate){
			$str = str_replace("\r\n", "\n", $str);
			$reg = "/\t*<!--\s*INI\s+$block\s+-->\n*(\s*.*?\n?)\t*<!--\s+FIM\s+$block\s*-->\n?/sm";
		} 
		else $reg = "/<!--\s*INI\s+$block\s+-->\s*(\s*.*?\s*)<!--\s+FIM\s+$block\s*-->\s*/sm";
		if(1!==preg_match($reg, $str, $m)) {
			$this->erros_template[$block]= "***<u>setBlock</u>: O Bloco <font COLOR='#f00'>&lt;!-- INI $block --&gt;</font> esta mal formatado em <i><font COLOR='#f00'>". basename($filename) ."</font></i>" . "<br />";
		}
		$this->setValue($name, '');
		@$this->setValue($block, $m[1]);
		$this->setValue($parent, preg_replace($reg, "{".$name."}", $str));
	}

	private function setValue($varname, $value) {			
		$this->values["{".$varname."}"] = $value;
	}
	private function getVar($varname) {

		return $this->values['{'.$varname.'}'];
	}

	public function clear($varname) {
		$this->setValue($varname, "");
	}

	function subst($varname) {
		$s = $this->getVar($varname);
		// Common variables replacement
		$s = str_replace(array_keys($this->values), $this->values, $s);
		// Object variables replacement
		foreach($this->instances as $var => $instance){
			foreach($this->properties[$var] as $properties){
				if(false!==strpos($s, "{".$var.$properties."}")){
					$pointer = $instance;
					$property = explode("->", $properties);
					for($i = 1; $i < sizeof($property); $i++){
						$obj = str_replace('_', '', $property[$i]);
						// Non boolean accessor
						if(method_existe_var($pointer, "get$obj")){
							$pointer = $pointer->{"get$obj"}();
						}
						// Boolean accessor
						elseif(method_existe_var($pointer, "is$obj")){
							$pointer = $pointer->{"is$obj"}();
						}
						// Magic __get accessor
						elseif(method_existe_var($pointer, "__get")){
							$pointer = $pointer->__get($property[$i]);
						}
						// Accessor dot not existe_var: throw Exception
						else {
							$className = $property[$i-1] ? $property[$i-1] : get_class($instance);
							$class = is_null($pointer) ? "NULL" : get_class($pointer);
							throw new BadMethodCallException("não existe método na classe ".$class." para acessar ".$className."->".$property[$i]);
						}
					}
					// Checking if final value is an object
					if(is_object($pointer)){
						if(method_existe_var($pointer, "__toString")){
							$pointer = $pointer->__toString();
						} else {
							$pointer = "Object";
						}
					}
					// Replace
					$s = str_replace("{".$var.$properties."}", $pointer, $s);
				}
			}
		}
		return $s;
	}

	private function clearBlocks($block) {
		if (isset($this->parents[$block])){
			$chields = $this->parents[$block];
			foreach($chields as $chield){
				$this->clear("B_".$chield);
			}
		}
	}

	public function block($block, $append = true) {
		if(!in_array($block, $this->blocks)){
			if (isset($filename))
				$this->erros_template[$block]= "**<u>AVISO</u>: O Bloco $block não existe no arquivo: <i>". $GLOBALS['filename'] ."</i>" . "<br />" . @$this->erros_template;
		}else
			//("AVISO: O Bloco $block não existe!!");
			if ($append) $this->setValue("B_".$block, $this->getVar("B_".$block) . $this->subst($block));
			else 
				$this->setValue("B_".$block, $this->subst($block));

		$this->clearBlocks($block);
	}
	public function parse($mostrar_blocos=false,$mostrar_erros=false) {

		#condtec 31-01 mostra os blocos	
		// echo $mostrar_erros;
		if ($mostrar_erros == 1)
			if ($this->erros_template != "")
				if ($this->existe_var("ERROS_TEMPLATE")){
					foreach($this->erros_template AS $value) {
						 $this-> ERROS_TEMPLATE .= $value  ;
					}

				}else
					foreach($this->erros_template AS $value) {
						echo "<center>$value</center>";
					}
		if(@$mostrar_blocos == 0)
			return preg_replace("/{(".self::$REG_NAME.")((\-\>(".self::$REG_NAME."))*)?}/", "", $this->subst("."));
		else
			return $this->subst(".");
	}

	public function exibir($var) {
		$this->erros_template .= @$var;
	}
	public function show($mostrar_blocos=0,$comprimir=0,$mostrar_erros=0) {
		$show = $this->parse($mostrar_blocos,$mostrar_erros);
		if ($comprimir == true)
			$show = $this->limpa_html($show);

		echo $show;
	}
}
?>
