<?php
/*
 ____        _ _    ___  ____
/ ___|  __ _| | |_ / _ \/ ___|
\___ \ / _` | | __| | | \___ \
 ___) | (_| | | |_| |_| |___) |
|____/ \__,_|_|\__|\___/|____/

SaltOS: Framework to develop Rich Internet Applications
Copyright (C) 2007-2018 by Josep Sanz Campderrós
More information in http://www.saltos.org or info@saltos.org

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
function parse_query($query,$type="") {
	if($type=="") $type=__parse_query_type();
	$pos=__parse_query_strpos($query,"/*");
	$len=strlen($type);
	while($pos!==false) {
		$pos2=__parse_query_strpos($query,"*/",$pos+2);
		if($pos2!==false) {
			$pos3=__parse_query_strpos($query,"/*",$pos+2);
			while($pos3!==false && $pos3<$pos2) {
				$pos=$pos3;
				$pos3=__parse_query_strpos($query,"/*",$pos+2);
			}
			if(substr($query,$pos+2,$len)==$type) {
				$query=substr($query,0,$pos).trim(substr($query,$pos+2+$len,$pos2-$pos-2-$len)).substr($query,$pos2+2);
			} else {
				$query=substr($query,0,$pos).substr($query,$pos2+2);
			}
			$pos=__parse_query_strpos($query,"/*",$pos);
		} else {
			$pos=__parse_query_strpos($query,"/*",$pos+2);
		}
	}
	return $query;
}

function __parse_query_type() {
	switch(getDefault("db/type")) {
		case "pdo_sqlite":
		case "sqlite3":
			return "SQLITE";
		case "pdo_mysql":
		case "mysql":
		case "mysqli":
			return "MYSQL";
		default:
			show_php_error(array("phperror"=>"Unknown type '".getDefault("db/type")."'"));
	}
}

function __parse_query_strpos($haystack,$needle,$offset=0) {
	$len=strlen($needle);
	$pos=strpos($haystack,$needle,$offset);
	if($pos!==false) {
		$len2=$pos-$offset;
		if($len2>0) {
			$count1=substr_count($haystack,"'",$offset,$len2)-substr_count($haystack,"\\'",$offset,$len2);
			$count2=substr_count($haystack,'"',$offset,$len2)-substr_count($haystack,'\\"',$offset,$len2);
			while($pos!==false && ($count1%2!=0 || $count2%2!=0)) {
				$offset=$pos+$len;
				$pos=strpos($haystack,$needle,$offset);
				if($pos!==false) {
					$len2=$pos-$offset;
					if($len2>0) {
						$count1+=substr_count($haystack,"'",$offset,$len2)-substr_count($haystack,"\\'",$offset,$len2);
						$count2+=substr_count($haystack,'"',$offset,$len2)-substr_count($haystack,'\\"',$offset,$len2);
					}
				}
			}
		}
	}
	return $pos;
}

function make_select_config($keys) {
	$keys=explode(",",$keys);
	$subquery=array("(SELECT '0' id) id");
	foreach($keys as $key) {
		$key=trim($key);
		$query="SELECT valor '$key' FROM tbl_configuracion WHERE clave='$key'";
		$subquery[]="($query) $key";
		$config=execute_query($query);
		if($config===null) {
			$val=CONFIG($key);
			$query=make_insert_query("tbl_configuracion",array(
				"clave"=>$key,
				"valor"=>$val
			));
			db_query($query);
		}
	}
	$subquery=implode(",",$subquery);
	$query="SELECT $subquery";
	return $query;
}

function preeval_update_config($clave) {
	$query="\"UPDATE tbl_configuracion SET valor='\".addslashes(getParam(\"$clave\")).\"' WHERE clave='$clave'\"";
	return $query;
}

function preeval_insert_query($table) {
	$fields=get_fields_from_dbschema($table);
	$list1=array();
	$list2=array();
	foreach($fields as $field) {
		if($field["name"]=="id") continue;
		$list1[]=$field["name"];
		$type=$field["type"];
		$type2=get_field_type($type);
		if($type2=="int") $list2[]="'\".intval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="float") $list2[]="'\".floatval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="date") $list2[]="'\".dateval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="time") $list2[]="'\".timeval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="datetime") $list2[]="'\".datetimeval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="string") $list2[]="'\".addslashes(getParam(\"".$field["name"]."\")).\"'";
		else show_php_error(array("phperror"=>"Unknown type '${type}' in preeval_insert_query"));
	}
	$list1=implode(",",$list1);
	$list2=implode(",",$list2);
	$query="\"INSERT INTO $table($list1) VALUES($list2)\"";
	return $query;
}

function preeval_update_query($table) {
	$fields=get_fields_from_dbschema($table);
	$list=array();
	foreach($fields as $field) {
		if($field["name"]=="id") continue;
		$type=$field["type"];
		$type2=get_field_type($type);
		if($type2=="int") $list[]=$field["name"]."='\".intval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="float") $list[]=$field["name"]."='\".floatval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="date") $list[]=$field["name"]."='\".dateval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="time") $list[]=$field["name"]."='\".timeval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="datetime") $list[]=$field["name"]."='\".datetimeval(getParam(\"".$field["name"]."\")).\"'";
		elseif($type2=="string") $list[]=$field["name"]."='\".addslashes(getParam(\"".$field["name"]."\")).\"'";
		else show_php_error(array("phperror"=>"Unknown type '${type}' in preeval_update_query"));
	}
	$list=implode(",",$list);
	$query="\"UPDATE $table SET $list WHERE id='\".intval(getParam(\"id\")).\"'\"";
	return $query;
}

function preeval_dependencies_query($table,$label) {
	$dbschema=xml2array("xml/dbschema.xml");
	if(isset($dbschema["tables"])) {
		$deps=array();
		foreach($dbschema["tables"] as $tablespec) {
			foreach($tablespec["fields"] as $field) {
				if(!isset($field["fkey"])) $field["fkey"]="false";
				if(!isset($field["fcheck"])) $field["fcheck"]="true";
				if($field["fkey"]==$table && eval_bool($field["fcheck"])) {
					$deps[]=array("table"=>$tablespec["name"],"field"=>$field["name"]);
				}
			}
		}
		$count=array();
		foreach($deps as $dep) {
			$deptable=$dep["table"];
			$depfield=$dep["field"];
			$count[]="(SELECT COUNT(*) FROM $deptable WHERE $depfield='\".abs(getParam(\"id\")).\"')";
		}
		$count=implode("+",$count);
		if($count=="") $count="0";
		$query="\"SELECT '$label' action_error,'0' action_commit FROM (SELECT 1) a WHERE $count>0\"";
	}
	return $query;
}

function execute_query($query) {
	$result=db_query($query,"auto");
	$numrows=db_num_rows($result);
	$numfields=db_num_fields($result);
	$value=null;
	if($numrows==1 && $numfields==1) {
		$value=db_fetch_row($result);
	} elseif($numrows==1 && $numfields>1) {
		$value=db_fetch_row($result);
	} elseif($numrows>1 && $numfields==1) {
		$value=db_fetch_all($result);
	} elseif($numrows>1 && $numfields>1) {
		$value=db_fetch_all($result);
	}
	db_free($result);
	return $value;
}

function execute_query_array($query) {
	$result=db_query($query,"auto");
	$rows=db_fetch_all($result);
	db_free($result);
	return $rows;
}

function execute_query_extra($query,$extra) {
	$result=db_query($query);
	$rows=array();
	while($row=db_fetch_row($result)) {
		$ok=1;
		foreach($extra as $key=>$val) if($row[$key]!=$val) $ok=0;
		if($ok) $rows[]=$row;
	}
	db_free($result);
	return $rows;
}

function get_fields($table) {
	$query="/*MYSQL SHOW COLUMNS FROM $table *//*SQLITE PRAGMA TABLE_INFO($table) */";
	$result=db_query($query);
	$fields=array();
	while($row=db_fetch_row($result)) {
		if(isset($row["Field"])) $fields[]=array("name"=>$row["Field"],"type"=>strtoupper($row["Type"]));
		if(isset($row["name"])) $fields[]=array("name"=>$row["name"],"type"=>strtoupper($row["type"]));
	}
	db_free($result);
	return $fields;
}

function get_indexes($table) {
	$indexes=array();
	// FOR SQLITE
	$query="/*SQLITE PRAGMA INDEX_LIST(${table}) */";
	$result=db_query($query);
	while($row=db_fetch_row($result)) {
		$index=$row["name"];
		$query2="/*SQLITE PRAGMA INDEX_INFO(${index}) */";
		$result2=db_query($query2);
		$fields=array();
		while($row2=db_fetch_row($result2)) $fields[]=$row2["name"];
		db_free($result2);
		$indexes[$index]=$fields;
	}
	db_free($result);
	// FOR MYSQL
	$query="/*MYSQL SHOW INDEXES FROM ${table} */";
	$result=db_query($query);
	while($row=db_fetch_row($result)) {
		$index=$row["Key_name"];
		$column=$row["Column_name"];
		$where=1;
		if($index=="PRIMARY") $where=0;
		if($where) {
			if(!isset($indexes[$index])) $indexes[$index]=array();
			$indexes[$index][]=$column;
		}
	}
	return $indexes;
}

function get_tables() {
	$query="/*MYSQL SHOW TABLES *//*SQLITE SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' */";
    $result=db_query($query);
	$tables=array();
    while($row=db_fetch_row($result)) {
		$row=array_values($row);
		$tables[]=$row[0];
	}
	db_free($result);
	return $tables;
}

function get_field_type($type) {
	$type=parse_query($type);
	$type=strtok($type,"(");
	$type=strtoupper($type);
	$datatypes=getDefault("db/datatypes");
	foreach($datatypes as $key=>$val) if(in_array($type,explode(",",$val))) return $key;
	show_php_error(array("phperror"=>"Unknown type '${type}' in get_field_type"));
}

function sql_create_table($tablespec) {
	$table=$tablespec["name"];
	$fields=array();
	foreach($tablespec["fields"] as $field) {
		$name=$field["name"];
		$type=$field["type"];
		$type2=get_field_type($type);
		if($type2=="int") $def=intval(0);
		elseif($type2=="float") $def=floatval(0);
		elseif($type2=="date") $def=dateval(0);
		elseif($type2=="time") $def=timeval(0);
		elseif($type2=="datetime") $def=datetimeval(0);
		elseif($type2=="string") $def="";
		else show_php_error(array("phperror"=>"Unknown type '${type}' in sql_create_table"));
		$extra="NOT NULL DEFAULT '${def}'";
		if(isset($field["pkey"]) && eval_bool($field["pkey"])) $extra="PRIMARY KEY /*MYSQL AUTO_INCREMENT *//*SQLITE AUTOINCREMENT */";
		$fields[]="${name} ${type} ${extra}";
	}
	//~ foreach($tablespec["fields"] as $field) {
		//~ if(isset($field["fkey"])) {
			//~ $fkey=$field["fkey"];
			//~ if($fkey!="") {
				//~ $name=$field["name"];
				//~ $fields[]="FOREIGN KEY (${name}) REFERENCES ${fkey} (id)";
			//~ }
		//~ }
	//~ }
	$fields=implode(",",$fields);
	if(__has_fulltext_index($table) && __has_mroonga_engine()) {
		$post="/*MYSQL ENGINE=Mroonga CHARSET=utf8mb4 */";
	} else {
		$post="/*MYSQL ENGINE=MyISAM CHARSET=utf8mb4 */";
	}
	$query="CREATE TABLE ${table} (${fields}) ${post}";
	return $query;
}

function __has_fulltext_index($table) {
	static $fulltext=null;
	if($fulltext===null) {
		$file="xml/dbschema.xml";
		$dbschema=eval_attr(xml2array($file));
		$fulltext=array();
		if(is_array($dbschema) && isset($dbschema["indexes"]) && is_array($dbschema["indexes"])) {
			foreach($dbschema["indexes"] as $indexspec) {
				if(isset($indexspec["fulltext"]) && eval_bool($indexspec["fulltext"])) $fulltext[$indexspec["table"]]=1;
			}
		}
	}
	return isset($fulltext[$table]);
}

function __has_mroonga_engine() {
	static $mroonga=null;
	if($mroonga===null) $mroonga=__has_helper_engine("mroonga");
	return $mroonga;
}

function __has_helper_engine($arg) {
	$helper=false;
	if(getDefault("db/obj")) {
		$query="/*MYSQL SHOW ENGINES */";
		$result=db_query($query);
		while($row=db_fetch_row($result)) {
			$row=array_values($row);
			$engine=$row[0];
			if(strtolower($engine)==$arg) {
				$helper=true;
				break;
			}
		}
		db_free($result);
	}
	return $helper;
}

function get_engine($table) {
    $query="/*MYSQL SHOW TABLE STATUS WHERE Name='${table}' */";
    $result=db_query($query);
    $engine="";
    while($row=db_fetch_row($result)) $engine=$row["Engine"];
	db_free($result);
	return $engine;
}

function sql_alter_table($orig,$dest) {
	$query="ALTER TABLE ${orig} RENAME TO ${dest}";
	return $query;
}

function sql_insert_from_select($dest,$orig) {
	$fdest=get_fields($dest);
	$ldest=array();
	foreach($fdest as $f) $ldest[]=$f["name"];
	$forig=get_fields($orig);
	$lorig=array();
	foreach($forig as $f) $lorig[]=$f["name"];
	$defs=array();
	foreach($fdest as $f) {
		$type=$f["type"];
		$type2=get_field_type($type);
		if($type2=="int") $defs[]=intval(0);
		elseif($type2=="float") $defs[]=floatval(0);
		elseif($type2=="date") $defs[]=dateval(0);
		elseif($type2=="time") $defs[]=timeval(0);
		elseif($type2=="datetime") $defs[]=datetimeval(0);
		elseif($type2=="string") $defs[]="";
		else show_php_error(array("phperror"=>"Unknown type '${type}' in sql_insert_from_select"));
	}
	$keys=array();
	$vals=array();
	foreach($ldest as $key=>$l) {
		$def=$defs[$key];
		$keys[]="${l}";
		$vals[]=in_array($l,$lorig)?"${l}":"'${def}'";
	}
	$keys=implode(",",$keys);
	$vals=implode(",",$vals);
	$query="INSERT INTO ${dest}(${keys}) SELECT ${vals} FROM ${orig}";
	return $query;
}

function sql_drop_table($table) {
	$query="DROP TABLE ${table}";
	return $query;
}

function sql_create_index($indexspec) {
	$name=$indexspec["name"];
	$table=$indexspec["table"];
	$fields=array();
	foreach($indexspec["fields"] as $field) $fields[]=$field["name"];
	$fields=implode(",",$fields);
	if(isset($indexspec["fulltext"]) && eval_bool($indexspec["fulltext"])) {
		$pre="/*MYSQL FULLTEXT */";
	} else {
		$pre="";
	}
	$query="CREATE ${pre} INDEX ${name} ON $table (${fields})";
	return $query;
}

function sql_drop_index($index,$table) {
	$query="/*MYSQL DROP INDEX ${index} ON ${table} *//*SQLITE DROP INDEX ${index} */";
	return $query;
}

function __make_like_query_explode($separator,$str) {
	$result=array();
	$len=strlen($str);
	$ini=0;
	$open=array("'"=>0,'"'=>0);
	for($i=0;$i<$len;$i++) {
		$letter=$str[$i];
		if(isset($open[$letter])) {
			$open[$letter]=($open[$letter]+1)%2;
		}
		if($letter==$separator && array_sum($open)==0) {
			$result[]=substr($str,$ini,$i-$ini);
			$ini=$i+1;
		}
	}
	if($i!=$ini) {
		$result[]=substr($str,$ini,$i-$ini);
	}
	return $result;
}

function make_like_query($keys,$values) {
	$keys=explode(",",$keys);
	foreach($keys as $key=>$val) {
		$val=trim($val);
		if($val=="") unset($keys[$key]);
		if($val!="") $keys[$key]=$val;
	}
	if(!count($keys)) return "1=1";
	$values=__make_like_query_explode(" ",$values);
	foreach($values as $key=>$val) {
		$val=trim($val);
		$val=str_replace(array("'",'"',"@","%","*","_","?"),array("","","@@","@%","%","@_","_"),$val);
		if($val=="") unset($values[$key]);
		if($val!="") $values[$key]=$val;
	}
	if(!count($values)) return "1=1";
	$query=array();
	foreach($values as $value) {
		$type=($value[0]=="-")?"-":"+";
		while(isset($value[0]) && in_array($value[0],array("+","-"))) $value=substr($value,1);
		if($value!="") {
			if($type=="-") {
				$query2=array();
				foreach($keys as $key) $query2[]="$key NOT LIKE '%$value%' ESCAPE '@'";
				$query[]="(".implode(" AND ",$query2).")";
			} else {
				$query2=array();
				foreach($keys as $key) $query2[]="$key LIKE '%$value%' ESCAPE '@'";
				$query[]="(".implode(" OR ",$query2).")";
			}
		}
	}
	if(!count($query)) return "1=1";
	$query="(".implode(" AND ",$query).")";
	return $query;
}

function make_extra_query_with_login($prefix="") {
	$query=make_extra_query($prefix);
	return "REPLACE(CONCAT(${prefix}login,' (',$query,')'),'()','')";
}

function make_extra_query($prefix="") {
	static $stack=array();
	$hash=md5($prefix);
	if(!isset($stack[$hash])) {
		$query="SELECT * FROM tbl_aplicaciones WHERE islink=1";
		$rows=execute_query_array($query);
		if(count($rows)>0) {
			$cases=array("CASE ${prefix}id_aplicacion");
			foreach($rows as $row) {
				$cases[]="WHEN '${row["id"]}' THEN (SELECT ${row["campo"]} FROM ${row["tabla"]} WHERE id=${prefix}id_registro)";
			}
			$cases[]="END";
			$stack[$hash]=implode(" ",$cases);
		} else {
			$stack[$hash]="''";
		}
	}
	return $stack[$hash];
}

function make_extra_query_with_field($field,$prefix="") {
	static $stack=array();
	$hash=md5(json_encode(array($field,$prefix)));
	if(!isset($stack[$hash])) {
		$query="SELECT * FROM tbl_aplicaciones WHERE islink=1";
		$rows=execute_query_array($query);
		if(count($rows)>0) {
			$cases=array("CASE ${prefix}id_aplicacion");
			foreach($rows as $row) {
				$fields=get_fields_from_dbschema($row["tabla"]);
				foreach($fields as $key=>$val) $fields[$key]=$val["name"];
				if(in_array($field,$fields)) $cases[]="WHEN '${row["id"]}' THEN (SELECT ${field} FROM ${row["tabla"]} WHERE id=${prefix}id_registro)";
			}
			$cases[]="END";
			$stack[$hash]=implode(" ",$cases);
		} else {
			$stack[$hash]="''";
		}
	}
	return $stack[$hash];
}

function make_select_appsregs($id=0) {
	$query="SELECT * FROM tbl_aplicaciones WHERE islink=1";
	$rows=execute_query_array($query);
	if(count($rows)>0) {
		$subquery=array();
		foreach($rows as $row) {
			$subquery[]="SELECT CONCAT('${row["id"]}','_','-2') id,'${row["id"]}' id_aplicacion,-2 id_registro,'${row["nombre"]}' aplicacion,'link:appreg_details(this):".LANG_ESCAPE("showdetalles")."' registro,'0' activado,-2 pos FROM (SELECT 1) a WHERE (SELECT COUNT(*) FROM ${row["tabla"]})>0";
			$subquery[]="SELECT CONCAT('${row["id"]}','_','-1') id,'${row["id"]}' id_aplicacion,-1 id_registro,'${row["nombre"]}' aplicacion,'link:appreg_details(this):".LANG_ESCAPE("hidedetalles")."' registro,'0' activado,-1 pos FROM (SELECT 1) a WHERE (SELECT COUNT(*) FROM ${row["tabla"]})>0";
			$subquery[]="SELECT CONCAT('${row["id"]}','_',a.id) id,'${row["id"]}' id_aplicacion,a.id id_registro,'${row["nombre"]}' aplicacion,nombre registro,CASE WHEN ur.id IS NULL THEN 0 ELSE 1 END activado,0 pos FROM ${row["tabla"]} a LEFT JOIN tbl_usuarios_r ur ON ur.id_aplicacion='${row["id"]}' AND ur.id_registro=a.id AND ur.id_usuario='".abs($id)."' WHERE (SELECT COUNT(*) FROM ${row["tabla"]})>0";
		}
		$query=implode(" UNION ",$subquery)." ORDER BY aplicacion,pos,registro";
	} else {
		$query="";
	}
	return $query;
}

function make_extra_query_with_perms($page,$table,$field,$arg1=null,$arg2=null) {
	// CHECKS FOR OPTIONAL ARGUMENTS
	$filter="";
	$haspos=false;
	if($arg1!==null && is_string($arg1)) $filter=$arg1;
	if($arg1!==null && is_bool($arg1)) $haspos=$arg1;
	if($arg2!==null && is_string($arg2)) $filter=$arg2;
	if($arg2!==null && is_bool($arg2)) $haspos=$arg2;
	// REPARE FIELD LIST FOR SUBQUERY
	$temp=explode(",",is_array($field)?$field[1]:$field);
	foreach($temp as $key=>$val) {
		$temp[$key]="a2.".$val." ".$val;
	}
	$temp=implode(",",$temp);
	// NORMAL CODE
	$query="SELECT id value,".(is_array($field)?$field[0]:$field)." label,".($haspos?"pos":"'0' pos")." FROM (
		SELECT a2.id id,".($haspos?"a2.pos pos,":"").$temp.",e.id_usuario id_usuario,d.id_grupo id_grupo
		FROM $table a2
		LEFT JOIN tbl_registros e ON e.id_aplicacion='".page2id($page)."' AND e.id_registro=a2.id AND e.first=1
		LEFT JOIN tbl_usuarios d ON e.id_usuario=d.id
	) a WHERE ".($filter?"id IN ($filter)":"1=1")." AND ".check_sql($page,"list");
	return $query;
}

function get_tables_from_dbschema() {
	return __dbschema_helper(__FUNCTION__,"");
}

function get_fields_from_dbschema($table) {
	return __dbschema_helper(__FUNCTION__,$table);
}

function __dbschema_helper($fn,$table) {
	static $tables=null;
	if($tables===null) {
		$file="xml/dbschema.xml";
		$dbschema=eval_attr(xml2array($file));
		$tables=array();
		if(is_array($dbschema) && isset($dbschema["tables"]) && is_array($dbschema["tables"])) {
			foreach($dbschema["tables"] as $tablespec) {
				$tables[$tablespec["name"]]=array();
				foreach($tablespec["fields"] as $fieldspec) $tables[$tablespec["name"]][]=array("name"=>$fieldspec["name"],"type"=>strtoupper(parse_query($fieldspec["type"])));
			}
		}
	}
	if(stripos($fn,"get_tables")!==false) {
		return array_keys($tables);
	} elseif(stripos($fn,"get_fields")!==false) {
		if(isset($tables[$table])) return $tables[$table];
	}
	return array();
}

function make_insert_query($table,$array,$queries=array()) {
	if(is_string($array)) {
		$query="INSERT INTO ${table}";
		if(count($queries)>0) {
			$queries=implode(",",$queries);
			$query.="(${queries})";
		}
		$query.=" ${array}";
		return $query;
	}
	$list1=array();
	$list2=array();
	foreach($array as $key=>$val) {
		$list1[]=$key;
		$list2[]="'".addslashes($val)."'";
	}
	foreach($queries as $key=>$val) {
		$list1[]=$key;
		$list2[]="(".$val.")";
	}
	$list1=implode(",",$list1);
	$list2=implode(",",$list2);
	$query="INSERT INTO ${table}(${list1}) VALUES(${list2})";
	return $query;
}

function make_update_query($table,$array,$where="",$queries=array()) {
	if(is_string($array)) {
		$query="UPDATE ${table} SET ${array}";
		if($where!="") $query.=" WHERE ${where}";
		return $query;
	}
	$list1=array();
	foreach($array as $key=>$val) $list1[]=$key."='".addslashes($val)."'";
	foreach($queries as $key=>$val) $list1[]=$key."=(".$val.")";
	$list1=implode(",",$list1);
	$query="UPDATE ${table} SET ${list1}";
	if($where!="") $query.=" WHERE ${where}";
	return $query;
}

function make_delete_query($table,$where="") {
	if($where!="") {
		$query="DELETE FROM ${table} WHERE ${where}";
	} else {
		$query="/*MYSQL TRUNCATE *//*SQLITE DELETE FROM */ ${table}";
	}
	return $query;
}

function make_select_query($table,$array="*",$where="",$extra=array()) {
	static $count=1;
	if(is_array($array)) {
		if(is_array_key_val($array)) {
			foreach($array as $key=>$val) $array[$key]="${key} AS ${val}";
		}
		$array=implode(",",$array);
	}
	$query="SELECT ${array}";
	if(substr_count($table," ")>1) {
		$table="(${table}) a${count}";
		$count++;
	}
	if($table=="" && $where!="") {
		$table="(SELECT 1) a${count}";
		$count++;
	}
	if($table!="") $query.=" FROM ${table}";
	if($where!="") $query.=" WHERE ${where}";
	if(isset($extra["groupby"])) {
		$groupby=$extra["groupby"];
		if(is_array($groupby)) $groupby=implode(",",$groupby);
		$query.=" GROUP BY ${groupby}";
	}
	if(isset($extra["having"])) {
		$having=$extra["having"];
		if(is_array($having)) $having=implode(" AND ",$having);
		$query.=" HAVING ${having}";
	}
	if(isset($extra["order"])) {
		$order=$extra["order"];
		if(is_array($order)) {
			if(is_array_key_val($order)) foreach($order as $key=>$val) $order[$key]=$key." ".$val;
			$order=implode(",",$order);
		}
		$query.=" ORDER BY ${order}";
	}
	if(isset($extra["limit"])) {
		if(!isset($extra["offset"])) $extra["offset"]=0;
		$limit=intval($extra["limit"]);
		$offset=intval($extra["offset"]);
		$query.=" LIMIT ${offset},${limit}";
	}
	return $query;
}

function make_where_query($array,$union="AND",$queries=array()) {
	$list1=array();
	foreach($array as $key=>$val) {
		$op="=";
		if(is_array($val)) list($op,$val)=array($val[0],$val[1]);
		$list1[]=$key.$op."'".addslashes($val)."'";
	}
	foreach($queries as $key=>$val) $list1[]="(".$val.")";
	$query="(".implode(" ".$union." ",$list1).")";
	return $query;
}

function make_fulltext_query2($values,$arg="") {
	if(is_array($arg)) {
		$filter=$arg;
		$search="search";
	} elseif(is_numeric($arg)) {
		$filter=array($arg);
		$search="search";
	} elseif($arg!="") {
		$filter=array();
		$search=$arg;
	} else {
		$filter=array();
		$search="search";
	}
	$engine=strtolower(get_engine("tbl_indexing"));
	if($engine=="mroonga") {
		$values=__make_like_query_explode(" ",$values);
		foreach($values as $key=>$val) {
			$val=trim($val);
			$val=str_replace(array("'",'"',"\\","(",")"),"",$val);
			if($val=="") unset($values[$key]);
			if($val!="") $values[$key]=$val;
		}
		if(!count($values)) return "1=1";
		$query=array();
		$likes=array();
		foreach($values as $value) {
			$type=($value[0]=="-")?"-":"+";
			while(isset($value[0]) && in_array($value[0],array("+","-"))) $value=substr($value,1);
			if($value!="") {
				if(mb_strlen($value)==1) $likes[]=$type.$value;
				else $query[]=$type.'"'.$value.'"';
			}
		}
		if(!count($query)) return "1=1";
		if(count($filter)) {
			foreach($filter as $key=>$val) $filter[$key]="id_aplicacion:${val}";
			$filter=implode(" ",$filter);
			$filter="+(${filter})";
			array_unshift($query,$filter);
		}
		$query=implode(" ",$query);
		$query="MATCH(${search}) AGAINST('${query}' IN BOOLEAN MODE)";
		if(count($likes)) {
			$query.=" AND ".make_like_query($search,implode(" ",$likes));
		}
	} else {
		$query=array();
		if(count($filter)) {
			$filter=implode(",",$filter);
			$filter="id_aplicacion IN (${filter})";
			$query[]=$filter;
		}
		$query[]=make_like_query($search,$values);
		$query=implode(" AND ",$query);
	}
	return $query;
}

function make_linktitle_query($prefix="") {
	return __make_helper_query(__FUNCTION__,$prefix);
}

function make_actiontitle_query($prefix="") {
	return __make_helper_query(__FUNCTION__,$prefix);
}

function __make_helper_query($fn,$prefix="") {
	$query="SELECT * FROM tbl_aplicaciones WHERE tabla!='' AND campo!=''";
	$result=db_query($query);
	$cases=array("CASE ${prefix}id_aplicacion");
	while($row=db_fetch_row($result)) {
		if(substr($row["campo"],0,1)=='"' && substr($row["campo"],-1,1)=='"') $row["campo"]=eval_protected($row["campo"]);
		if(stripos($fn,"linktitle")!==false) $cases[]="WHEN '${row["id"]}' THEN (SELECT CONCAT('link:openapp(\'${row["codigo"]}\',',-${prefix}id_registro,'):',${row["campo"]}) FROM ${row["tabla"]} WHERE id=${prefix}id_registro)";
		if(stripos($fn,"actiontitle")!==false) $cases[]="WHEN '${row["id"]}' THEN (SELECT CONCAT(LPAD(id,".intval(CONFIG("zero_padding_digits")).",0),' - ',${row["campo"]}) FROM ${row["tabla"]} WHERE id=${prefix}id_registro)";
	}
	db_free($result);
	$cases[]="END";
	$cases=implode(" ",$cases);
	return $cases;
}
?>
