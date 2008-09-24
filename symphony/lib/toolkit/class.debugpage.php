<?php

	require_once(TOOLKIT . '/class.htmlpage.php');

	Class DebugPage extends HTMLPage{
		
		var $_full_utility_list;
		
		function __buildNavigation($page){		
			
			$ul = new XMLElement('ul', NULL, array('id' => 'nav'));
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Edit', URL . '/symphony/blueprints/pages/edit/' . $page['id'] . '/'));
			$ul->appendChild($li);
			
			$ul->appendChild(new XMLElement('li', 'Debug'));

			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Profile', '?profile'));
			$ul->appendChild($li);
			
			return $ul;
		}
		
		function __buildJump($page, $xsl, $utilities=NULL){
			
			$ul = new XMLElement('ul', NULL, array('id' => 'jump'));
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Params',  '#params'));
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('XML',  '#xml')); 
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor(basename($page['filelocation']),  '#' . basename($page['filelocation'])));
			$xUtil = $this->__buildUtilityList($utilities);
			if(is_object($xUtil)) $li->appendChild($xUtil);
			$ul->appendChild($li);
			
			$li = new XMLElement('li');
			$li->appendChild(Widget::Anchor('Result',  '#result')); 
			$ul->appendChild($li);					
			

			return $ul;
			
		}
		
		function __buildUtilityList($utilities, $level=1){
			
			if(!is_array($utilities) || empty($utilities)) return;
			
			$ul = new XMLElement('ul');
			foreach($utilities as $u){
				$item = new XMLElement('li');
				$filename = basename($u);
				$item->appendChild(Widget::Anchor($filename, '#u-' . $filename));
				
				$child_utilities = $this->__findUtilitiesInXSL(@file_get_contents(UTILITIES . '/' . $filename));
				
				if(is_array($child_utilities) && !empty($child_utilities)) $item->appendChild($this->__buildUtilityList($child_utilities, $level+1));
				
				$ul->appendChild($item);
			}
			
			return $ul;
		
		}
		
		function __buildParams($params){
			
			if(!is_array($params) || empty($params)) return;
			
			$dl = new XMLElement('dl', NULL, array('id' => 'params'));
			
			foreach($params as $key => $value){				
				$dl->appendChild(new XMLElement('dt', "\$$key"));
				$dl->appendChild(new XMLElement('dd', "'$value'"));
			}
			
			return $dl;
			
		}
		
		function __findUtilitiesInXSL($xsl){
			if($xsl == '') return;
			
			$utilities = NULL;

			if(preg_match_all('/<xsl:(import|include)\s*href="([^"]*)/i', $xsl, $matches)){
				$utilities = $matches[2];
			}
			
			if(!is_array($this->_full_utility_list)) $this->_full_utility_list = array();

			if(is_array($utilities) && !empty($utilities)) $this->_full_utility_list = array_merge($utilities, $this->_full_utility_list);
			
			return $utilities;
		}
		
		function __buildCodeBlock($code, $id){
			return new XMLElement('pre', '<code>' . str_replace('<', '&lt;', str_replace('&', '&amp;', General::tabsToSpaces($code, 2))) . '</code>', array('id' => $id, 'class' => 'XML'));
		}
		
		function generate($page, $xml, $xsl, $output, $parameters){
			
			$this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
			
			$this->Html->setElementStyle('html');
			$this->Html->setDTD('<!DOCTYPE html>'); //PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"
			$this->Html->setAttribute('lang', 'en');
			$this->addElementToHead(new XMLElement('meta', NULL, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
			$this->addElementToHead(new XMLElement('link', NULL, array('rel' => 'icon', 'href' => URL.'/symphony/assets/images/bookmark.png', 'type' => 'image/png')), 20); 		
			$this->addStylesheetToHead(URL . '/symphony/assets/debug.css', 'screen', 40);
			$this->addElementToHead(new XMLElement('!--[if IE]><link rel="stylesheet" href="'.URL.'/symphony/assets/legacy.css" type="text/css"><![endif]--'), 50);
			$this->addScriptToHead(URL . '/symphony/assets/admin.js', 60);
			
			$this->setTitle('Symphony &ndash; Debug &ndash; ' . $page['title']);
			
			$h1 = new XMLElement('h1');
			$h1->appendChild(Widget::Anchor($page['title'], '.'));
			$this->Body->appendChild($h1);
			
			$this->Body->appendChild($this->__buildNavigation($page));
			
			$utilities = $this->__findUtilitiesInXSL($xsl);

			$this->Body->appendChild($this->__buildJump($page, $xsl, $utilities));
			
			if(is_array($parameters) && !empty($parameters)) $this->Body->appendChild($this->__buildParams($parameters));
			
			$this->Body->appendChild($this->__buildCodeBlock($xml, 'xml'));
			$this->Body->appendChild($this->__buildCodeBlock($xsl, basename($page['filelocation'])));
			$this->Body->appendChild($this->__buildCodeBlock($output, 'result'));
			
			if(is_array($this->_full_utility_list) && !empty($this->_full_utility_list)){
				foreach($this->_full_utility_list as $u){
					$this->Body->appendChild($this->__buildCodeBlock(@file_get_contents(UTILITIES . '/' . basename($u)), 'u-'.basename($u)));	
				}
			}
			
			return parent::generate();
						
		}
		
	}
	
?>