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

class database_pdo_mysql {
	private $link=null;

	function __construct($args) {
		if(!class_exists("PDO")) { show_php_error(array("phperror"=>"Class PDO not found","details"=>"Try to install php-pdo package")); return; }
		try {
			$this->link=new PDO("mysql:host=".$args["host"].";port=".$args["port"].";dbname=".$args["name"],$args["user"],$args["pass"]);
		} catch(PDOException $e) {
			show_php_error(array("dberror"=>$e->getMessage()));
		}
		if($this->link) {
			$this->link->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
			$this->db_query("SET NAMES 'utf8mb4'");
			$this->db_query("SET FOREIGN_KEY_CHECKS=0");
			$this->db_query("SET GROUP_CONCAT_MAX_LEN:=@@MAX_ALLOWED_PACKET");
		}
	}

	function db_query($query,$fetch="query") {
		$query=parse_query($query,"MYSQL");
		$result=array("total"=>0,"header"=>array(),"rows"=>array());
		if(!strlen(trim($query))) return $result;
		// DO QUERY
		try {
			$stmt=$this->link->query($query);
		} catch(PDOException $e) {
			show_php_error(array("dberror"=>$e->getMessage(),"query"=>$query));
		}
		unset($query); // TRICK TO RELEASE MEMORY
		// DUMP RESULT TO MATRIX
		if(isset($stmt) && $stmt && $stmt->columnCount()>0) {
			if($fetch=="auto") {
				$fetch=$stmt->columnCount()>1?"query":"column";
			}
			if($fetch=="query") {
				$result["rows"]=$stmt->fetchAll(PDO::FETCH_ASSOC);
				$result["total"]=count($result["rows"]);
				if($result["total"]>0) $result["header"]=array_keys($result["rows"][0]);
			}
			if($fetch=="column") {
				$result["rows"]=$stmt->fetchAll(PDO::FETCH_COLUMN);
				$result["total"]=count($result["rows"]);
				$result["header"]=array("__a__");
			}
		}
		return $result;
	}

	function db_disconnect() {
		$this->link=null;
	}
}
?>