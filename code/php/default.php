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
require_once("php/libaction.php");

if(!getDefault("$page/$action")) {
	$_LANG["default"]="denied,menu,common";
	$_CONFIG["denied"]=eval_attr(xml2array("xml/denied.xml"));
	$_RESULT["form"]=getDefault("denied/form");
	add_css_js_page($_RESULT["form"],"denied");
	set_array($_ERROR,"error","Unknown action '$action'");
	$action="";
}

switch($action) {
	case "insert":
	case "update":
	case "delete":
		eval_files();
		$config=getDefault("$page/$action");
		if(eval_bool(getDefault("debug/actiondebug"))) {
			debug_dump(false);
			$config=eval_attr($config);
			debug_dump();
		}
		$commit=1;
		if($action=="insert") $go=-2;
		if($action=="update") $go=-2;
		if($action=="delete") $go=-1;
		if(eval_bool(intval(getParam("returnhere"))?"true":"false")) $go=-1;
		if(eval_bool(intval(getParam("returnback"))?"true":"false")) $go=-2;
		$semaphore=array($page,$action);
		if(!semaphore_acquire($semaphore)) show_php_error(array("phperror"=>"Could not acquire the semaphore"));
		foreach($config as $query) {
			$inline=eval_attr($query);
			foreach($inline as $query) {
				$query=trim($query);
				if($query=="") continue;
				$is_select=strtoupper(substr($query,0,6))=="SELECT";
				if($is_select) {
					$result=db_query($query);
					$count=0;
					while($row=db_fetch_row($result)) {
						$row["__ROW_NUMBER__"]=++$count;
						if(isset($row["action_delete"])) {
							$delete=$row["action_delete"];
							if(substr($delete,0,1)!="/") $delete=get_directory("dirs/filesdir").$delete;
							if(file_exists($delete) && is_file($delete)) unlink_protected($delete);
						}
						if(isset($row["action_error"])) {
							$error=$row["action_error"];
							session_error($error);
						}
						if(isset($row["action_alert"])) {
							$alert=$row["action_alert"];
							session_alert($alert);
						}
						if(isset($row["action_commit"])) {
							$commit=$row["action_commit"];
						}
						if(isset($row["action_go"])) {
							$go=$row["action_go"];
						}
						if(isset($row["action_include"])) {
							$include=$row["action_include"];
							$include=explode(",",$include);
							foreach($include as $file) {
								if(!file_exists($file)) show_php_error(array("xmlerror"=>"Include '$file' not found"));
								include($file);
							}
						}
						if(!$commit) break;
					}
					db_free($result);
				} else {
					db_query($query);
				}
				if(!$commit) break;
			}
			if(!$commit) break;
		}
		semaphore_release($semaphore);
		if(is_numeric($go)) {
			javascript_history($go);
		} else {
			javascript_history("update");
			javascript_location_page($go);
		}
		die();
	case "list":
		require_once("php/listsim.php");
		$config=getDefault("$page/$action");
		if(eval_bool(getDefault("debug/actiondebug"))) debug_dump(false);
		$config=eval_attr($config);
		if(eval_bool(getDefault("debug/actiondebug"))) debug_dump();
		$_RESULT[$action]=$config;
		add_css_js_page($_RESULT[$action],$page);
		// GET AND REMOVE THE NEEDED XML NODES
		foreach(array("query","order","limit","offset") as $node) {
			if(!isset($config[$node])) show_php_error(array("xmlerror"=>"&lt;$node&gt; not found for &lt;$action&gt;"));
			unset($_RESULT[$action][$node]);
		}
		$query0=$config["query"];
		$limit=$config["limit"];
		$offset=$config["offset"];
		// CHECK ORDER
		list($order,$array)=list_check_order($config["order"],$config["fields"]);
		// MARK THE SELECTED ORDER FIELD
		foreach($_RESULT[$action]["fields"] as $key=>$val) {
			$selected=0;
			if(isset($val["name"]) && $val["name"]==$array[0][0]) $selected=1;
			if(isset($val["order"]) && $val["order"]==$array[0][0]) $selected=1;
			if(isset($val["order".$array[0][1]]) && $val["order".$array[0][1]]==$array[0][0]) $selected=1;
			if($selected) $_RESULT[$action]["fields"][$key]["selected"]=$array[0][1];
		}
		// EXECUTE THE QUERY TO GET THE ROWS WITH LIMIT AND OFFSET
		$query="$query0 ORDER BY $order LIMIT $offset,$limit";
		$result=db_query($query);
		$count=0;
		while($row=db_fetch_row($result)) {
			$row["__ROW_NUMBER__"]=++$count;
			set_array($_RESULT[$action]["rows"],"row",$row);
		}
		db_free($result);
		// CONTINUE WITH NORMAL OPERATION
		$_RESULT[$action]=__default_eval_querytag($_RESULT[$action]);
		break;
	case "form":
		$config=getDefault("$page/$action");
		if(eval_bool(getDefault("debug/actiondebug"))) debug_dump(false);
		$config=eval_attr($config);
		if(eval_bool(getDefault("debug/actiondebug"))) debug_dump();
		$_RESULT[$action]=$config;
		add_css_js_page($_RESULT[$action],$page);
		unset($_RESULT[$action]["views"]);
		if($id==0) {
			if(isset($config["views"]["insert"]["title"])) $_RESULT[$action]["title"]=$config["views"]["insert"]["title"];
			if(isset($config["views"]["insert"]["query"])) $query=$config["views"]["insert"]["query"];
		} else {
			if($id>0) {
				if(isset($config["views"]["update"]["title"])) $_RESULT[$action]["title"]=$config["views"]["update"]["title"];
				if(isset($config["views"]["update"]["query"])) $query=$config["views"]["update"]["query"];
			} else {
				if(isset($config["views"]["view"]["title"])) $_RESULT[$action]["title"]=$config["views"]["view"]["title"];
				if(isset($config["views"]["view"]["query"])) $query=$config["views"]["view"]["query"];
			}
		}
		if(isset($query)) {
			$fixquery=is_array($query)?0:1;
			$go=0;
			$commit=1;
			if($fixquery) $query=array("default"=>$query);
			$rows=__default_process_querytag($query,$go,$commit);
			if($fixquery) $rows=$rows["default"];
			set_array($_RESULT[$action],"rows",$rows);
			if($go) {
				if(is_numeric($go)) {
					javascript_history($go);
				} else {
					javascript_history("update");
					javascript_location_page($go);
				}
				die();
			}
		} else {
			$_LANG["default"]="denied,menu,common";
			$_CONFIG["denied"]=eval_attr(xml2array("xml/denied.xml"));
			$_RESULT["form"]=getDefault("denied/form");
			add_css_js_page($_RESULT["form"],"denied");
			set_array($_ERROR,"error","Unknown action '$action'");
		}
		$_RESULT[$action]=__default_eval_querytag($_RESULT[$action]);
		break;
	default:
		if(!$action) break;
		$_LANG["default"]="denied,menu,common";
		$_CONFIG["denied"]=eval_attr(xml2array("xml/denied.xml"));
		$_RESULT["form"]=getDefault("denied/form");
		add_css_js_page($_RESULT["form"],"denied");
		set_array($_ERROR,"error","Unknown action '$action'");
		break;
}
?>