<?php
/*
 ____        _ _    ___  ____
/ ___|  __ _| | |_ / _ \/ ___|
\___ \ / _` | | __| | | \___ \
 ___) | (_| | | |_| |_| |___) |
|____/ \__,_|_|\__|\___/|____/

SaltOS: Framework to develop Rich Internet Applications
Copyright (C) 2012 by Josep Sanz Campderrós
More information in http://www.saltos.net or info@saltos.net

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
if(getParam("action")=="themeroller") {
	// GET PARAMETERS
	$theme=getParam("theme",getParam("amp;theme"));
	$theme=strtok($theme,"?");
	$palette=xml2array("xml/themeroller.xml");
	$cssbase="lib/jquery/jquery-ui-1.9.2.css";
	$imgbase="lib/jquery/jquery-ui-1.9.2.images/";
	$allbase=array_merge(array($cssbase),glob($imgbase."*.png"));
	$mask=getParam("mask",getParam("amp;mask"));
	$mask=strtok($mask,"?");
	$over=getParam("over",getParam("amp;over"));
	$over=strtok($over,"?");
	// URL OF THE JQUERY-UI THEMEROLLER APPLICATION
	// I USE THE REDMOND THEME WITH THE FOLLOW MODIFICATIONS:
	// - FCACTIVE AND ICONCOLORACTIVE COPIED AS THE HOVER COLORS
	// - CORNERRADIUS SET TO 4PX INSTEAD OF 5PX
	$themeroller="http://jqueryui.com/themeroller/#ffDefault=Lucida%20Grande%2CLucida%20Sans%2CArial%2Csans-serif&fwDefault=bold&fsDefault=1.1em&cornerRadius=4px&bgColorHeader=5c9ccc&bgTextureHeader=12_gloss_wave.png&bgImgOpacityHeader=55&borderColorHeader=4297d7&fcHeader=ffffff&iconColorHeader=d8e7f3&bgColorContent=fcfdfd&bgTextureContent=06_inset_hard.png&bgImgOpacityContent=100&borderColorContent=a6c9e2&fcContent=222222&iconColorContent=469bdd&bgColorDefault=dfeffc&bgTextureDefault=02_glass.png&bgImgOpacityDefault=85&borderColorDefault=c5dbec&fcDefault=2e6e9e&iconColorDefault=6da8d5&bgColorHover=d0e5f5&bgTextureHover=02_glass.png&bgImgOpacityHover=75&borderColorHover=79b7e7&fcHover=1d5987&iconColorHover=217bc0&bgColorActive=f5f8f9&bgTextureActive=06_inset_hard.png&bgImgOpacityActive=100&borderColorActive=79b7e7&fcActive=1d5987&iconColorActive=217bc0&bgColorHighlight=fbec88&bgTextureHighlight=01_flat.png&bgImgOpacityHighlight=55&borderColorHighlight=fad42e&fcHighlight=363636&iconColorHighlight=2e83ff&bgColorError=fef1ec&bgTextureError=02_glass.png&bgImgOpacityError=95&borderColorError=cd0a0a&fcError=cd0a0a&iconColorError=cd0a0a&bgColorOverlay=aaaaaa&bgTextureOverlay=01_flat.png&bgImgOpacityOverlay=0&opacityOverlay=30&bgColorShadow=aaaaaa&bgTextureShadow=01_flat.png&bgImgOpacityShadow=0&opacityShadow=30&thicknessShadow=8px&offsetTopShadow=-8px&offsetLeftShadow=-8px&cornerRadiusShadow=8px";
	// PREPARE CACHE FILENAME
	$temp=get_directory("dirs/cachedir");
	$hash=md5(serialize(array($theme,$palette,$cssbase,$imgbase,$allbase,$mask,$over,$themeroller,getDefault("cache/usecssminify"),getDefault("cache/useimginline"))));
	if($theme) $format="css";
	if($mask) $format="png";
	if($over) $format="png";
	if(!isset($format)) action_denied();
	if($theme && $mask) action_denied();
	if($mask && $over) action_denied();
	if($over && $theme) action_denied();
	$cache="$temp$hash.$format";
	// FOR DEBUG PURPOSES
	//if(file_exists($cache)) unlink($cache);
	// CREATE IF NOT EXISTS
	if(!cache_exists($cache,$allbase)) {
		if($mask) {
			$mask=explode("|",$mask);
			if(count($mask)!=2) action_denied();
			if(!file_exists($imgbase.$mask[0])) show_php_error(array("phperror"=>"Mask '${mask[0]}' not found"));
			if(strlen($mask[1])!=6) action_denied();
			$im=imagecreatefrompng($imgbase.$mask[0]);
			$sx=imagesx($im);
			$sy=imagesy($im);
			$im2=imagecreatetruecolor($sx,$sy);
			imagefilledrectangle($im2,0,0,$sx,$sy,imagecolorallocate($im2,255,255,255));
			$r=hexdec(substr($mask[1],0,2));
			$g=hexdec(substr($mask[1],2,2));
			$b=hexdec(substr($mask[1],4,2));
			for($x=0;$x<$sx;$x++) {
				for($y=0;$y<$sy;$y++) {
					$z=imagecolorsforindex($im,imagecolorat($im,$x,$y));
					unset($z["alpha"]);
					$z=127-max(0,min(127,array_sum($z)/6));
					imagesetpixel($im2,$x,$y,imagecolorallocatealpha($im2,$r,$g,$b,$z));
				}
			}
			imagedestroy($im);
			imagepng($im2,$cache);
			imagedestroy($im2);
			chmod_protected($cache,0666);
			// DUMP THE DATA
			if(defined("__CANCEL_DIE__")) readfile($cache);
			if(!defined("__CANCEL_DIE__")) output_file($cache);
		}
		if($over) {
			$over=explode("|",$over);
			if(count($over)!=3) action_denied();
			if(!file_exists($imgbase.$over[0])) show_php_error(array("phperror"=>"Over '${over[0]}' not found"));
			if(strlen($over[1])!=6) action_denied();
			$im=imagecreatefrompng($imgbase.$over[0]);
			$sx=imagesx($im);
			$sy=imagesy($im);
			$im2=imagecreatetruecolor($sx,$sy);
			imagefilledrectangle($im2,0,0,$sx,$sy,imagecolorallocate($im2,255,255,255));
			$r=hexdec(substr($over[1],0,2));
			$g=hexdec(substr($over[1],2,2));
			$b=hexdec(substr($over[1],4,2));
			for($x=0;$x<$sx;$x++) {
				for($y=0;$y<$sy;$y++) {
					$z=imagecolorsforindex($im,imagecolorat($im,$x,$y));
					$z=max(0,min(127,(127-$z["alpha"])*$over[2]/100));
					imagesetpixel($im2,$x,$y,imagecolorallocatealpha($im2,$r,$g,$b,$z));
				}
			}
			imagedestroy($im);
			imagepng($im2,$cache);
			imagedestroy($im2);
			chmod_protected($cache,0666);
			// DUMP THE DATA
			if(defined("__CANCEL_DIE__")) readfile($cache);
			if(!defined("__CANCEL_DIE__")) output_file($cache);
		}
		if($theme) {
			if(!isset($palette[$theme])) $theme=key($palette);
			// FUNCTIONS
			function __themeroller_color($color,$rgb) {
				$array=array("r"=>substr($color,0,2),"g"=>substr($color,2,2),"b"=>substr($color,4,2));
				$result="";
				for($i=0;$i<3;$i++) $result.=$array[$rgb[$i]];
				return $result;
			}
			// QUERY STRING TO GENERATE REDMOND THEME
			$buffer=file_get_contents($cssbase);
			$pos=strpos($themeroller,"#");
			$querystring=substr($themeroller,$pos+1);
			$array=querystring2array($querystring);
			// MODIFY COLORS TO APPLY THEME
			foreach(array("bgColor","borderColor","fc","iconColor") as $val) {
				foreach(array("Header","Content","Default","Hover","Active") as $val2) {
					$array[$val.$val2]=__themeroller_color($array[$val.$val2],$palette[$theme]);
				}
			}
			// FIX SOME STRING THINGS
			foreach(array("iconColor") as $val) {
				$len=strlen($val);
				foreach($array as $key2=>$val2) {
					if(substr($key2,0,$len)==$val) {
						if(eval_bool(getDefault("cache/useimginline"))) {
							if(!defined("__CANCEL_DIE__")) define("__CANCEL_DIE__",1);
							require_once("php/listsim.php");
							saltos_context($page,$action);
							setParam("mask","icons.png|${val2}");
							ob_start();
							$oldcache=$cache;
							include(__FILE__);
							$cache=$oldcache;
							$data=base64_encode(ob_get_clean());
							saltos_context();
							$data="data:image/png;base64,${data}";
							$array["icons".substr($key2,$len)]="url(${data})";
						} else {
							$array["icons".substr($key2,$len)]="url(xml.php?action=themeroller&mask=icons.png|${val2})";
						}

					}
				}
			}
			foreach(array("bgTexture") as $val) {
				$len=strlen($val);
				foreach($array as $key2=>$val2) {
					if(substr($key2,0,$len)==$val) {
						$bgcolor=$array["bgColor".substr($key2,$len)];
						$bgimgopacity=$array["bgImgOpacity".substr($key2,$len)];
						if(eval_bool(getDefault("cache/useimginline"))) {
							if(!defined("__CANCEL_DIE__")) define("__CANCEL_DIE__",1);
							require_once("php/listsim.php");
							saltos_context($page,$action);
							setParam("over","${val2}|${bgcolor}|${bgimgopacity}");
							ob_start();
							$oldcache=$cache;
							include(__FILE__);
							$cache=$oldcache;
							$data=base64_encode(ob_get_clean());
							saltos_context();
							$data="data:image/png;base64,${data}";
							$array["bgImgUrl".substr($key2,$len)]="url(${data})";
						} else {
							$array["bgImgUrl".substr($key2,$len)]="url(xml.php?action=themeroller&over=${val2}|${bgcolor}|${bgimgopacity})";
						}
						$array["bg".substr($key2,$len)."XPos"]="50%";
						$array["bg".substr($key2,$len)."YPos"]="50%";
						$array["bg".substr($key2,$len)."Repeat"]="repeat-x";
					}
				}
			}
			foreach(array("bgColor","borderColor","fc") as $val) {
				$len=strlen($val);
				foreach($array as $key2=>$val2) {
					if(substr($key2,0,$len)==$val) $array[$key2]="#".$val2;
				}
			}
			foreach(array("opacity") as $val) {
				$len=strlen($val);
				foreach($array as $key2=>$val2) {
					if(substr($key2,0,$len)==$val) $array[$key2]=($val2/100).";filter:Alpha(Opacity=".$val2.")";
				}
			}
			// APPLY THE CHANGES TO THE BASE CSS
			$pos=strpos($buffer,"/*{");
			while($pos!==false) {
				$pos2=strpos($buffer,"}*/");
				if($pos2===false) break;
				$pos3=$pos;
				while($pos3>0 && $buffer[$pos3]!=" ") $pos3--;
				$pos3++;
				$key=substr($buffer,$pos+3,$pos2-$pos-3);
				$val=isset($array[$key])?$array[$key]:"/*NOT FOUND*/";
				$buffer=substr_replace($buffer,$val,$pos3,$pos2-$pos3+3);
				$pos=strpos($buffer,"/*{");
			}
			// SAVE CACHE
			if(eval_bool(getDefault("cache/usecssminify"))) $buffer=minify_css($buffer);
			file_put_contents($cache,$buffer);
			chmod_protected($cache,0666);
			// DUMP THE DATA
			output_file($cache);
		}
	} else {
		if(defined("__CANCEL_DIE__")) readfile($cache);
		if(!defined("__CANCEL_DIE__")) output_file($cache);
	}
}
?>