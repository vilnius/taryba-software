<?php
class Template {

  var $classname = "Template";

  /* if set, echo assignments */
  var $debug     = false;

  /* $file[handle] = "filename"; */
  var $file  = array();

  /* relative filenames are relative to this pathname */
  var $root   = "";

  /* $varkeys[key] = "key"; $varvals[key] = "value"; */
  var $varkeys = array();
  var $varvals = array();

  /* "remove"  => remove undefined variables
   * "comment" => replace undefined variables with comments
   * "keep"    => keep undefined variables
   */
  var $unknowns = "keep";

  /* "yes" => halt, "report" => report error, continue, "no" => ignore error quietly */
  var $halt_on_error  = "report";

  /* last error message is retained here */
  var $last_error     = "";

  /***************************************************************************/
  /* public: Constructor.
   * root:     template directory.
   * unknowns: how to handle unknown variables.
   */
  function Template($root = ".", $unknowns = "remove") {
    $this->set_root($root);
    $this->set_unknowns($unknowns);
  }

  /* public: setroot(pathname $root)
   * root:   new template directory.
   */
  function set_root($root) {
  	//Stepas 2006.12.22
  	//FIX'as, kad IIS'as tam tikrais atvejais (bent neveike su IIS + Win2003) negrazintu,
  	//kad kelias iki katalogo, kurio gale yra /, "nera katalogas".

    if ((stristr($_SERVER['SERVER_SOFTWARE'], "Microsoft-IIS/6"))){
    	if(!is_dir( (substr($root, -1) == "/" ? substr($root, 0, strlen($root-1)) : $root) )) {
	      $this->halt("set_root: $root is not a directory.");
	      return false;
	    }
    }
	else{ //kitu atveju gali kilti analogiskos problemos, jei bus panaudotas fix'as
		if(!is_dir($root)){
	    	$this->halt("set_root: $root is not a directory.");
	    	return false;
		}
	}
   	$this->root = $root;

    return true;
  }

  /* public: set_unknowns(enum $unknowns)
   * unknowns: "remove", "comment", "keep"
   *
   */
  function set_unknowns($unknowns = "keep") {
    $this->unknowns = $unknowns;
  }

    /* public: set_file(array $filelist)
     * filelist: array of handle, filename pairs.
     *
     * public: set_file(string $handle, string $filename)
     * handle: handle for a filename,
     * filename: name of template file
     */
    function set_file($handle, $filename = "", $advanced = "") {
        if (!is_array($handle)) {
            if ($this->root.$filename == "") {
                $this->halt("set_file: For handle $handle filename is empty.");
                return false;
            }

            if( !file_exists($this->root.$filename) && is_file($filename) ){
                $this->file[$handle] = $this->filename($filename);
            }else{
                $this->file[$handle] = $this->filename($this->root.$filename);
            }
        } else {
            reset($handle);
            $key = array_keys($handle);
            $size = sizeOf($key);
            for ($i=0; $i<$size; $i++) {
                $this->file[$key[$i]] = $this->filename($this->root.$handle[$key[$i]]);
            }
        }
    }

  /* public: set_block(string $parent, string $handle, string $name = "")
   * extract the template $handle from $parent,
   * place variable {$name} instead.
   */
  function set_block( $parent, $handle, $name = null ) {
    if (!$this->loadfile($parent)) {
      $this->halt("subst: unable to load $parent.");
      return false;
    }
  	//echo $parent.' | '.$handle.' || '.$name.'<br/>';

    if ( $name === NULL ) {
    	$name = "block_{$handle}";
    }elseif ( $name == "" ) {
        vdebug( "Warning: empty block name supplied handle=$handle" );
        $name = $handle;
    }

    $str = $this->get_var($parent);

    $reg = "/<!--\s+BEGIN $handle\s+-->(.*)\n*\s*<!--\s+END $handle\s+-->/sm";
//    if (!function_exists("preg_match_all")) echo "nera funkcijos<br>";
//    if (!function_exists("preg_replace")) echo "nera funkcijos2<br>";
    preg_match_all($reg, $str, $m);
    $str = preg_replace($reg, "{" . "$name}", $str);
    //ldebug($handle, $m[1][0], 7);
    @$this->set_var($handle, $m[1][0]);
    //ldebug($parent, $str, 7);
    $this->set_var($parent, $str);
  }

  /* public: set_var(array $values)
   * values: array of variable name, value pairs.
   *
   * public: set_var(string $varname, string $value)
   * varname: name of a variable that is to be defined
   * value:   value of that variable
   */
  function set_var($varname, $value = "") {
//echo"<pre>";var_dump($varname);echo"</pre>";
    if (!is_array($varname)) {
      if (!empty($varname))
        if ($this->debug) vdebug("scalar: set *$varname* to *$value*"); //print "scalar: set *$varname* to *$value*<br>\n";
        $this->varkeys[$varname] = $this->varname($varname);
        $this->varvals[$varname] = $value;
    } else {
        reset($varname);
        $key = array_keys($varname);
        $size = sizeOf($key);
        for ($i=0; $i<$size; $i++) {
            if (!empty($key[$i])) if ($this->debug) vdebug("array: set *".$key[$i]."* to *".$varname[$key[$i]]."*"); //print "array: set *".$key[$i]."* to *".$varname[$key[$i]]."*<br>\n";

            if( is_array($varname[$key[$i]]) ){
            	foreach( $varname[$key[$i]] As $kk => $vv ){
            		if( is_array($vv) ){
            			foreach($vv as $kkk => $vvv){
							$this->varkeys["{$key[$i]}[{$kk}][{$kkk}]"] = $this->varname("{$key[$i]}[{$kk}][{$kkk}]");
							$this->varvals["{$key[$i]}[{$kk}][{$kkk}]"] = $vvv;
            			}
            		}else{
			            $this->varkeys["{$key[$i]}[{$kk}]"] = $this->varname("{$key[$i]}[{$kk}]");
			            $this->varvals["{$key[$i]}[{$kk}]"] = $vv;
            		}
            	}
            }else{
	            $this->varkeys[$key[$i]] = $this->varname($key[$i]);
	            $this->varvals[$key[$i]] = $varname[$key[$i]];
            }
        }
    }

//  echo "pavadinimas: ".$varname." reiksme: $value<br>";
  }

  /* public: subst(string $handle)
   * handle: handle of template where variables are to be substituted.
   */
  function subst($handle) {
    if (!$this->loadfile($handle)) {
      $this->halt("subst: unable to load $handle.");
      return false;
    }

//    $str = $this->get_var($handle);
//    $str = str_replace($this->varkeys, $this->varvals, $str); //kodel sitas netiko ?//kostas 2005-05-17 pakeista kad greiciau veiktu ?
 //$a = str_replace($this->varkeys, $this->varvals, $this->get_var($handle));

//if($_SERVER['REMOTE_ADDR'] == '213.197.173.18') {
//        $a = str_replace($this->varkeys, $this->varvals, $this->get_var($handle));
//    echo " a: ".htmlentities($a)."<br>";
//}
    //return str_replace($this->varkeys, $this->varvals, $this->get_var($handle));
    //return $a;

// Rolandas: [4/21/2005] neaisq kodel daroma kelis kartus letesniu budu...
//if($_SERVER['REMOTE_ADDR'] == '192.168.0.26') {
    //Kostas ir Rimas 2005-08-16 optimizuota mazinant masyva

    /*
        Rolandas [8/25/2005]
        Zinomacia viskas cia labai grazu kol templeite nera 2 kartus naudojamas taspats kintamasis...
        reikia nebent tikrinti kada baigiasi vienas templeitas ir prasideda kitas ir trinti varus pasibaigusio templeito...
    **
		*//*
        $str = $this->get_var($handle);
        reset($this->varkeys);

        $key = array_keys($this->varkeys);
        $size = sizeOf($key);
        for ($i=0; $i<$size; $i++) {
            $key_id = $key[$i];
            $tmp_a = $this->varkeys[$key_id];
            $tmp_b =  $this->varvals[$key_id];
            $new_str = str_replace($tmp_a, $tmp_b, $str);

            if($new_str != $str) {
                unset($this->varkeys[$key_id]);
                unset($this->varvals[$key_id]);
                $str = $new_str;
            }

        }
        return $str;*/

//}
 else {
    return str_replace($this->varkeys, $this->varvals, $this->get_var($handle));
}
  }


  function clean($target) {
    //ldebug($this->varkeys[$target], $this->varvals[$target], 7);
     $this->varkeys[$target] = '';
     $this->varvals[$target] = '';
  }

  function get_block($target) {
    return "target $target:<br>varkeys: ".$this->varkeys[$target]."<br>varvals: ".$this->varvals[$target];
  }


  function parseBlock( $handle, $append = false ){
	return $this->parse($handle, null, $append);
  }

  /* public: psubst(string $handle)
   * handle: handle of template where variables are to be substituted.
   */
  /* public: parse(string $target, string $handle, boolean append)
   * public: parse(string $target, array  $handle, boolean append)
   * target: handle of variable to generate
   * handle: handle of template to substitute
   * append: append to target handle
   */
  function parse($target, $handle = null, $append = false) {
  	if( $target && $handle === null ){
  		$handle = $target;
  		$target = "block_{$handle}";
  	}

	if ( !is_array($handle) ) {
		$str = $this->subst($handle);
		if ($append) {
	        $this->set_var($target, $this->get_var($target) . $str);
	      } else {
	        $this->set_var($target, $str);
	      }
	    } else {
	      reset($handle);
	      foreach($handle as $h) {
	          $str = $this->subst($h);
	          $this->set_var($target, $str);
	      }
    }

    return $str;
  }

  function pparse($target, $handle, $append = false) {
    print $this->parse($target, $handle, $append);
    return false;
  }

  /* public: get_vars()
   */
  function get_vars() {
    reset($this->varkeys);

    $key = array_keys($this->varkeys);
    $size = sizeOf($key);
    for ($i=0; $i<$size; $i++)
      $result[$key[$i]] = $this->varvals[$key[$i]];
    return $result;
  }

  /* public: get_var(string varname)
   * varname: name of variable.
   *
   * public: get_var(array varname)
   * varname: array of variable names
   */
  function get_var($varname) {

    if (!is_array($varname)) {
        if(isset($this->varvals[$varname]))
            return $this->varvals[$varname];
        else
            return "";
    } else {
      reset($varname);
      $key = array_keys($varname);
      $size = sizeOf($key);
      for ($i=0; $i<$size; $i++)
        $result[$key[$i]] = $this->varvals[$key[$i]];
      return $result;
    }
  }
/*	Gražina informaciją iš $file */
	function get_file($i = null) {
		end($this->file);
		switch($i) {
			case "handle":
				$ret = key($this->file);
				break;
			case "file":
				$ret = current($this->file);
				break;
			case "filename":
				$file = current($this->file);
				$res = preg_match('/([A-Za-z0-9_]+)(\.local)*\.tpl/', $file, $m);
				if($res === 1) $ret = $m[1];
				 else $ret = false;
				break;
			default:
				$ret = $this->file;
		}
		reset($this->file);

		return $ret;
	}
/*	Kintamųjų paieška */
	function find_vars($handle, $string, $regex = false) {
		if(isset($this->varvals[$handle])) {
			if(FALSE === $regex) {
				$pattern = "/{([A-Za-z0-9_-]*{$string}[A-Za-z0-9_-]*)+(::[A-Za-z0-9\s\-\/\\\\[\]\(\)\*\^,.<>?;:'\"|`~!@#$%\^&_=+]*)*}/";
				preg_match_all($pattern, $this->get_var($handle), $m);
			} else {
				$pattern = $string;
				preg_match_all($pattern, $this->get_var($handle), $m);
			}

/*if($handle=='news_block') {
	echo"<pre>";var_dump($this->varvals);die;
}*/
			if(FALSE === empty($m[0])) {
				for($i = 0; $i < count($m[0]); $i++) {
					if(FALSE === empty($m[0][$i]))
						$m[0][$i] = str_replace(array('{','}'), '', $m[0][$i]);
				}
			}
			if(FALSE === empty($m[2])) {
				for($i = 0; $i < count($m[2]); $i++) {
					if(FALSE === empty($m[2][$i]))
						$m[2][$i] = substr($m[2][$i], 2);
				}
			}
			//if(1 === count($m[0])) {
			if(1 === count($m[0]) && FALSE === empty($m[0])) {
				$m[0] = $m[0][0];
				$m[1] = $m[1][0];
				$m[2] = $m[2][0];
			} else {
				$m = FALSE;

// TODO: paiešką ieškoti vidiniuose blokuose.
// Bloke randa tik blokų handler'ius, kuriuos į varvals įdeda tik po parsinimo.
// Iki tol turime tik blokų kintamuosius su turiniu.
// Tai reikia sąryšio tarp bloko handlerio ir kintamojo
				/*
				// Ieško vidinių blokų
				$pattern = '/{([A-Za-z0-9_-]+)}/';
				preg_match_all($pattern, $this->get_var($handle), $matches);
				foreach($matches[1] as $key => $var) {
					var_dump($var,$this->get_var($var), $this->varkeys);
				}
				 */
			}
			return $m;
		}
	}

  /*
   * @auth Rolandas Razma
   * Pushess all variables to template
   */
  function set_vars($varname, $msg){
    $varname = str_replace("*", ".*", $varname);

    if(is_array($msg))
    foreach ($msg as $key => $value) {
		if(preg_match("/(^$varname)/", $key)) {
            $this->varkeys[$key] = $this->varname($key);
            $this->varvals[$key] = $value;
        }
	}
  }

  /* public: get_undefined($handle)
   * handle: handle of a template.
   */
  function get_undefined($handle) {
    if (!$this->loadfile($handle)) {
      $this->halt("get_undefined: unable to load $handle.");
      return false;
    }

    preg_match_all("/\{([^}]+)\}/", $this->get_var($handle), $m);
    $m = $m[1];
    if (!is_array($m))
      return false;

    reset($m);

    $key = array_keys($m);
    $size = sizeOf($key);
      for ($i=0; $i<$size; $i++)
        if (!isset($this->varkeys[$m[$key[$i]]]))
            $result[$m[$key[$i]]] = $m[$key[$i]];
    if (count($result))
      return $result;
    else
      return false;
  }

  /* public: finish(string $str)
   * str: string to finish.
   */
  function finish($str) {
    switch ($this->unknowns) {
      case "keep":
      break;
      case "remove":
        $str = preg_replace('/{[^ \t\r\n}]+}/', "", $str);
//        $str = preg_replace('/<!--.+?-->/s', "", $str);
      break;
      case "comment":
        $str = preg_replace('/{([^ \t\r\n}]+)}/', "<!-- Template $handle: Variable \\1 undefined -->", $str);
      break;
    }
    return $str;
  }

  /* public: p(string $varname)
   * varname: name of variable to print.
   */
  function p($varname) {
    print $this->finish($this->get_var($varname));
  }

  function get($varname) {
    return $this->finish($this->get_var($varname));
  }

  /***************************************************************************/
  /* private: filename($filename)
   * filename: name to be completed.
   */
  function filename($filename) {

/*    if (substr($filename, 0, 1) != "/") {
	    $filename = $this->$filename;
    }*/ // kazkodel su situo neveikia
// echo "$filename";
    if (!file_exists($filename))
      $this->halt("filename: file $filename does not exist.");

    return $filename;
  }

  /* private: varname($varname)
   * varname: name of a replacement variable to be protected.
   */
  function varname($varname) {
    return "{".$varname."}";
  }

  /* private: loadfile(string $handle)
   * handle:  load file defined by handle, if it is not loaded yet.
   */
  function loadfile($handle) {
//    if (isset($this->varkeys[$handle]) and !empty($this->varvals[$handle]))
    if (isset($this->varkeys[$handle]))
      return true;

    if (!isset($this->file[$handle])) {
      $this->halt("loadfile: $handle is not a valid handle.");
      return false;
    }
    $filename = $this->file[$handle];

//    echo "$filename<br>$handle<br>";
    $str = implode("", @file($filename));
    if (empty($str)) {
      $this->halt("loadfile: While loading $handle, $filename does not exist or is empty.");
      return false;
    }

    $this->set_var($handle, $str);
    return true;
  }

  /***************************************************************************/
  /* public: halt(string $msg)
   * msg:    error message to show.
   */
  function halt($msg) {
    $this->last_error = $msg;

    if ($this->halt_on_error != "no")
      $this->haltmsg($msg);

    if ($this->halt_on_error == "yes")
      die("<b>Halted.</b>");

    return false;
  }

  /* public, override: haltmsg($msg)
   * msg: error message to show.
   */
  function haltmsg($msg) {
        $msg = str_replace("\n", " ", $msg);
        $msg = str_replace("\r", " ", $msg);
        $error_message = "Template Error: $msg\n";
        $var_dump = debug_backtrace();
        $ERRORMSG = $this->_link->errno.' '.$this->_link->error."\n".$query."\n";
        $debug_count = sizeof($var_dump);
        for($dc=0;$dc<$debug_count;$dc++)
            $ERRORMSG .= 'File: '.$var_dump[$dc]['file'].'; '.
                'Line: '.$var_dump[$dc]['line'].'; '.
                'Function: '.$var_dump[$dc]['function']."\n";
        //report_error("TMPL",$error_message);
       	trigger_error($error_message.' '.$ERRORMSG,E_USER_ERROR);
  }
}