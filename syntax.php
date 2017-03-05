<?php
/**
 * Plugin Button : Add button with image support syntax for links
 * 
 * To be run with Dokuwiki only

 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Rémi Peyronnet  <remi+xslt@via.ecp.fr>
 
 Full Syntax :
     [[{namespace:image|extra css}wiki page|Title of the link]]

All fields optional, minimal syntax:
    [[{}Simple button]]
 
 Configuration :
    [[{conf.styles}style|css]]
    [[{conf.target}style|target]]
 
 19/05/2013 : Initial release
 20/04/2014 : Added target support (feature request from Andrew St Hilaire)
 07/06/2014 : Added dokuwiki formatting support in title section (not working in wiki page section) (feature request from Willi Lethert)
 30/08/2014 : Added toolbar button (contribution from Xavier Decuyper) and fixed local anchor (bug reported by Andreas Kuzma)
 06/09/2014 : Refactored to add backlinks support (feature request from Schümmer Hans-Jürgen)
 28/04/2015 : Refactored global config handling, add internal media link support, add escaping of userinput (contribution from Peter Stumm   https://github.com/lisps/plugin-button)
 05/08/2015 : Merged lisps default style option and added french translation
 12/09/2015 : Fixed PHP error
 
 @author ThisNameIsNotAllowed
 17/11/2016 : Added generation of metadata
 18/11/2016 : Added default target for external links
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_button extends DokuWiki_Syntax_Plugin {

    function getType() { return 'substition'; }
    function getPType() { return 'normal'; }
    function getSort() { return 25; }  // Internal link is 300
//    function connectTo($mode) { $this->Lexer->addSpecialPattern('\[\[{[^}]*}[^\]]*\]\]',$mode,'plugin_button'); }
    function connectTo($mode) { 
        $this->Lexer->addSpecialPattern('\[\[{conf[^}]*}[^\]]*\]\]',$mode,'plugin_button'); 
        $this->Lexer->addEntryPattern('\[\[{[^}]*}[^\]\|]*\|?',$mode,'plugin_button');
        $this->Lexer->addExitPattern(']]','plugin_button'); 
    }
    function postConnect() { }
    function getAllowedTypes() { return array('formatting','substition'); }

    
    
    protected $confStyles;
    protected $styles = array();
    protected $targets = array();
    protected function setStyle($name,$value) {
        global $ID;
        $this->styles[$ID][$name] = $value;
    }
    protected function getStyle($name) {
        global $ID;
        return isset($this->styles[$ID][$name]) ? $this->styles[$ID][$name] : $this->getConfStyles($name);
    }
    protected function hasStyle($name) {
        global $ID;
        return (is_array($this->styles[$ID]) && array_key_exists($name,$this->styles[$ID])) 
                    || $this->getConfStyles($name) ? true : false;
    }
    protected function getConfStyles($name = null) {
        if($this->confStyles === null) {
            $this->confStyles = array();
            
            $styles = $this->getConf('styles');
            if(!$styles) return;
            
            $styles = explode("\n", $styles);
            if(!is_array($styles)) return;
            
            foreach ($styles as $style) {
                $style = trim($style);
                if(!$style) continue;
                
                $style = explode('|', $style,2);
                if(!is_array($style) || !$style[0] || !$style[1]) continue;
                
                $this->confStyles[trim($style[0])] = trim($style[1]);
            }
            //dbg($this->confStyles);
        
        }

        if($name) {
            if(!isset($this->confStyles[$name])) return false;
            
            return $this->confStyles[$name];
        }
        return $this->confStyles;
        
    }
    
    
    protected function setTarget($name,$value) {
        global $ID;
        $this->targets[$ID][$name] = $value;
    }
    protected function getTarget($name) {
        global $ID;
        return $this->targets[$ID][$name];
    }
    protected function hasTarget($name) {
        global $ID;
        return (is_array($this->targets[$ID]) && array_key_exists($name,$this->targets[$ID])) ? true : false;
    }
    
    function handle($match, $state, $pos, Doku_Handler $handler)
    { 
        global $plugin_button_styles;
        global $plugin_button_target;
        
        switch ($state) {
          case DOKU_LEXER_SPECIAL :
          case DOKU_LEXER_ENTER :
                $data = '';
                // Button
                if (preg_match('/\[\[{(?<image>[^}\|]*)\|?(?<css>[^}]*)}(?<link>[^\]\|]*)\|?(?<title>[^\]]*)/', $match, $matches))
                {
                    $data = $matches;
                }
                if (is_array($data))
                {
                    if ($data['image'] == 'conf.styles')
                    {
                        $this->setStyle($data['link'],$data['title']);
                    }
                    else if ($data['image'] == 'conf.target')
                    {
                        $this->setTarget($data['link'],$data['title']);
                    }
                    else
                    {
                        $data['target'] = "";
                        if ($this->hasTarget($data['css']))
                        {
                            $data['target'] =  $this->getTarget($data['css']);
                        } 
                        else if ($this->hasTarget('default'))
                        {
                            $data['target'] = $this->getTarget('default');
                        }

                        
                        if ($data['css'] != "" && $this->hasStyle($data['css']))
                        {
                            $data['css'] = $this->getStyle($data['css']);
                        }

                        if ($this->hasStyle('default') && ($data['css'] != 'default'))
                        {
                            $data['css'] = $this->getStyle('default') .' ; '. $data['css'];
                        }
                    }
                }

                return array($state, $data);
 
          case DOKU_LEXER_UNMATCHED :  return array($state, $match); 
          case DOKU_LEXER_ENTRY :          return array($state, '');
          case DOKU_LEXER_EXIT :            return array($state, '');
        }
        return array();
    }
    
    function render($mode, Doku_Renderer $renderer, $data) 
    {
        global $plugin_button_styles;
        global $plugin_button_target;
        global $ID;
        global $conf;

        if($mode == 'xhtml'){
            list($state, $match) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL:
              case DOKU_LEXER_ENTER:
                if (is_array($match))
                {
                    $image = $match['image'];
                    if (($image != "conf.target") && ($image != "conf.styles"))
                    {
                        // Test if internal or external link (from handler.php / internallink)
                        if (preg_match('#^([a-z0-9\-\.+]+?)://#i',$match['link']))
                        {
                            // External
                            $link['url'] = $match['link'];
                            $link['name'] = $match['title'];
                            if ($link['name'] == "") $link['name'] = $match['link'];
                            $link['class'] = 'urlextern';
                            if(strlen($match['target']) == 0){
                                $match['target'] = $conf['target']['extern'];
                            }
                        }
                        else
                        {
                            // Internal
                            $link = $this->dokuwiki_get_link($renderer, $match['link'], $match['title']);
                        }
                        $target = $match['target'];
                        if($target) $target = " target ='" .hsc($target). "' ";
                        
                        $link['name'] = str_replace('\\\\','<br />', $link['name']); //textbreak support
                        if ($image != '')
                        {
                            $image = Doku_Handler_Parse_Media("{{".$image."}}");
                            $image = $this->internalmedia($renderer,$image['src'],null,null,$image['width'],$image['height']);
                            $image =  "<span class='plugin_button_image'>". $image['name'] ."</span>";
                        }
                        $text = "<a ".$target." href='".$link['url']."'><span class='plugin_button' style='".hsc($match['css'])."'>$image<span class='plugin_button_text ${link['class']}'>";
                        if (substr($match[0],-1) != "|") $text .= $link['name'];
                        $renderer->doc .= $text; 
						// Update meta data for move
                       p_set_metadata($ID, array(
                          'relation'=>array(
                              'references'=>array(
                                  $match['link']=>true,
                              ),
                              'media'=>array(
                                  $match['image']=>true,
                              ),
                          ),
                          'plugin_move'=>array(
                              'pages'=>array(
                                  $match['link'],
                              ),
                              'medias'=>array(
                                  $match['image'],
                              ),
                          ),
                        ));						
                    }
                }
                break;
 
              case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $renderer->_xmlEntities($match); break;
              case DOKU_LEXER_EXIT :       $renderer->doc .= "</span></span></a>"; break;
            }
            return true;
        }
        elseif ($mode=='metadata')
        {
            list($state, $match) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL:
              case DOKU_LEXER_ENTER:
                if (is_array($match))
                {
                    /** @var Doku_Renderer_metadata $renderer */
                    $renderer->internallink($match['link']);
                    // I am assuming that when processing in handle(), you have stored
                    // the link destination in $data[0]
                    return true;
                }
                break;
              case DOKU_LEXER_UNMATCHED :  break;
              case DOKU_LEXER_EXIT :  break;
            }
            return true;
        }
        return false;
    }
    
    function dokuwiki_get_link(&$xhtml, $id, $name = NULL) {
        global $ID;
        $resolveid = $id;    // To prevent resolve_pageid to change $id value
        resolve_pageid(getNS($ID),$resolveid,$exists); //page file?
        if($exists) {
            return $this->internallink($xhtml,$id,$name);
        } 
        $resolveid = $id;   
        resolve_mediaid(getNS($ID),$resolveid,$exists); //media file?
        if($exists) {
            return $this->internalmedia($xhtml,$id,$name);
        } else {
            return $this->internallink($xhtml,$id,$name);
        }
    }
    
    // Copied and adapted from inc/parser/xhtml.php, function internallink (see RPHACK)
    // Should use wl instead (from commons), but this won't do the trick for the name
    function internallink(&$xhtml, $id, $name = NULL, $search=NULL,$returnonly=false,$linktype='content')
    {
        global $conf;
        global $ID;
        global $INFO;
    
    
        $params = '';
        $parts = explode('?', $id, 2);
        if (count($parts) === 2) {
            $id = $parts[0];
            $params = $parts[1];
        }
    
        // For empty $id we need to know the current $ID
        // We need this check because _simpleTitle needs
        // correct $id and resolve_pageid() use cleanID($id)
        // (some things could be lost)
        if ($id === '') {
            $id = $ID;
        }
    
        // RPHACK for get_link to work with local links '#id'
        if (substr($id, 0, 1) === '#') {
            $id = $ID . $id;
        }
        // -------
    
        // default name is based on $id as given
        $default = $xhtml->_simpleTitle($id);
    
        // now first resolve and clean up the $id
        resolve_pageid(getNS($ID),$id,$exists);
    
        $name = $xhtml->_getLinkTitle($name, $default, $isImage, $id, $linktype);
        if ( !$isImage ) {
            if ( $exists ) {
                $class='wikilink1';
            } else {
                $class='wikilink2';
                $link['rel']='nofollow';
            }
        } else {
            $class='media';
        }
    
        //keep hash anchor
        list($id,$hash) = explode('#',$id,2);
        if(!empty($hash)) $hash = $xhtml->_headerToLink($hash);
    
        //prepare for formating
        $link['target'] = $conf['target']['wiki'];
        $link['style']  = '';
        $link['pre']    = '';
        $link['suf']    = '';
        // highlight link to current page
        if ($id == $INFO['id']) {
            $link['pre']    = '<span class="curid">';
            $link['suf']    = '</span>';
        }
        $link['more']   = '';
        $link['class']  = $class;
        $link['url']    = wl($id, $params);
        $link['name']   = $name;
        $link['title']  = $id;
        //add search string
        if($search){
            ($conf['userewrite']) ? $link['url'].='?' : $link['url'].='&amp;';
            if(is_array($search)){
                $search = array_map('rawurlencode',$search);
                $link['url'] .= 's[]='.join('&amp;s[]=',$search);
            }else{
                $link['url'] .= 's='.rawurlencode($search);
            }
        }
    
        //keep hash
        if($hash) $link['url'].='#'.$hash;
    
        return $link;
        //output formatted
        //if($returnonly){
        //    return $this->_formatLink($link);
        //}else{
        //    $this->doc .= $this->_formatLink($link);
        //}
    }
    
    
    function internalmedia (&$xhtml, $src, $title=NULL, $align=NULL, $width=NULL,
            $height=NULL, $cache=NULL, $linking=NULL) {
        global $ID;
        list($src,$hash) = explode('#',$src,2);
        resolve_mediaid(getNS($ID),$src, $exists);
    
        $noLink = false;
        $render = ($linking == 'linkonly') ? false : true;
        $link = $xhtml->_getMediaLinkConf($src, $title, $align, $width, $height, $cache, $render);
    
        list($ext,$mime,$dl) = mimetype($src,false);
        if(substr($mime,0,5) == 'image' && $render){
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),($linking=='direct'));
        }elseif($mime == 'application/x-shockwave-flash' && $render){
            // don't link flash movies
            $noLink = true;
        }else{
            // add file icons
            $class = preg_replace('/[^_\-a-z0-9]+/i','_',$ext);
            $link['class'] .= ' mediafile mf_'.$class;
            $link['url'] = ml($src,array('id'=>$ID,'cache'=>$cache),true);
            if ($exists) $link['title'] .= ' (' . filesize_h(filesize(mediaFN($src))).')';
        }
    
        if($hash) $link['url'] .= '#'.$hash;
    
        //markup non existing files
        if (!$exists) {
            $link['class'] .= ' wikilink2';
        }
    
        return $link;
        //output formatted
//         if ($linking == 'nolink' || $noLink) $this->doc .= $link['name'];
//         else $this->doc .= $this->_formatLink($link);
    }
}

?>
