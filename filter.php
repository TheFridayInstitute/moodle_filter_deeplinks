<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter converting URLs in the text to HTML links
 *
 * @package    filter
 * @subpackage deeplinks
 * @copyright  2015 Mark Samberg <mjsamber@ncsu.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class filter_deeplinks extends moodle_text_filter {

    /**
     * Apply the filter to the text
     *
     * @see filter_manager::apply_filter_chain()
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     */
    public function filter($text, array $options = array()) {

        // TODO: Remove any script and other tags which we do not wish to filter. It
        // is unlikely that we'll find any suitable links within these areas so for
        // now this part has been left unfinished.
        $search = "(\[\[(deeplink).*?\]\])";
        $text = preg_replace_callback($search, array($this, 'callback'), $text);

        return $text;
    }

    ////////////////////////////////////////////////////////////////////////////
    // internal implementation starts here
    ////////////////////////////////////////////////////////////////////////////
    
    private function parameterize($text){
	    $text = trim($text,"[[");
	    $text = trim($text,"]]");
	    
	    //Stolen from WordPress with love and gratitude.
		$atts = array();
		$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
		$text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
		if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) && strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]))
					$atts[] = stripcslashes($m[8]);
			}
		} else {
			$atts = ltrim($text);
		}
		return $atts;
    }
    
    private function parse_activity($param){
	    global $COURSE;
	    try{

            $modinfo = get_fast_modinfo($COURSE->id);
            $sections = array();
			if(isset($param['section']))$section[] = $modinfo->sections[$param['section']];
			else $section = $modinfo->sections;
			foreach($section as $s){
	            foreach($s as $key=>$m){
	                $mi = $modinfo->get_cm($m);
	                if (!$mi->uservisible && empty($mi->availableinfo)){
	                    continue;
	                }
	                if(empty($mi->url))continue;
	                if((isset($param['name'])&&$param['name']==$mi->name)||(isset($param['id'])&&$param['id']==$mi->id)){
		                if(isset($param['title']))$title = $param['title'];
		                else $title = $mi->name;
		                $attrs = array();
		                if(isset($param['target']))$attrs['target'] = $param['target'];
		                if(isset($param['class']))$attrs['class'] = $param['class'];
		                if(isset($param['role']))$attrs['role'] = $param['role'];
		                return html_writer::link($mi->url, $title, $attrs);
		            }

	            }
	        }
            
            return "ERROR: ACTIVITY NOT FOUND";
            
        }
        catch(Exception $e){
	        print_r($e);
            return "EXCEPTION";
        }
    }
    
    
    private function callback(array $matches) {
       $text = $matches[0];
       $param = $this->parameterize($text);
       if(!isset($param['type']))return $text;
	   switch(strtolower($param['type'])){
		   case 'activity':
		   		return $this->parse_activity($param);
		   default:
		   		return $text;
		   
	   }
        return $embed;
    }
}
