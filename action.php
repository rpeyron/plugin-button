<?php
/**
 * Action Component for the Button Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Xavier Decuyper <xavier.decuyper@gmail.com>
 * 
 * 
 * @author     ThisNameIsNotAllowed
 * 17/11/2016 : Extended for usage with the move plugin (Added eventhandler and callback)
 *
 * @author 	Remi Peyronnet
 * 19/11/2016 : rewrote move plugin handler to work with all button syntaxes
 */

/*
// Thanks to #28 : autoloaded & now deprecated (https://www.dokuwiki.org/devel:releases:refactor2020)

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
*/

class action_plugin_button extends DokuWiki_Action_Plugin {

    function register(Doku_Event_Handler $controller){
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array ());
        $controller->register_hook('PLUGIN_MOVE_HANDLERS_REGISTER', 'BEFORE', $this, 'handleBeforePageMove', array());
    }

    function handle_toolbar(&$event, $param) {
        $syntaxDiv = $this->getConf('syntaxDiv');
        $syntaxSpan = $this->getConf('syntaxSpan');

        $event->data[] = array (
            'type' => 'format',
            'title' => 'Insert button',
            'icon' => '../../plugins/button/images/add-button.png',
            'open' => '[[{}',
            'close' => ']]',
            'sample' => 'Wiki link|Button title'
        );
    }
    
    public function handleBeforePageMove(Doku_Event $event, $param){
        $event->data['handlers']['button'] = array($this, 'rewrite_button');
    }

    function move_newid($handler, $page, $type)
    {
        if (method_exists($handler, 'adaptRelativeId')) { // move plugin before version 2015-05-16
            $newpage = $handler->adaptRelativeId($page);
        } else {
            $newpage = $handler->resolveMoves($page, $type);
            $newpage = $handler->relativeLink($page, $newpage, $type);
        }
        return $newpage;
    }
  
    public function rewrite_button($match, $state, $pos, $plugin, helper_plugin_move_handler $handler)
    {
        $returnValue = $match;
    
        if($state !== DOKU_LEXER_ENTER) return $returnValue;
		
		// If same identifier do nothing  (do not work, have not found how to detect if it is a real move or only a new display...)
		//if (($handler->id == $handler->origID) && ($handler->ns == $handler->origNS)) return $returnValue;
		
        if (preg_match('/\[\[{(?<image>[^}\|]*)\|?(?<css>[^}]*)}(?<link>[^\]\|]*)\|?(?<title>[^\]]*)/', $match, $data))
        {
            // Skip syntaxes that should not be rewritten
            if (($data['image'] != 'conf.styles') && ($data['image'] != 'conf.target') && $data['image']) {
                $data['image'] = $this->move_newid($handler, $data['image'], 'media');
            }
            if($data['link']) { // Adapt link
				$link_items=explode("#",$data['link']);
                $link_items[0] = $this->move_newid($handler, $link_items[0], 'page');
				$data['link'] = implode("#",$link_items);
            }
                // Rebuild button syntax
                $returnValue="[[{" . $data['image'];
                if ($data['css'])  $returnValue .= "|" . $data['css'];
                $returnValue.="}";
                $returnValue.=$data['link'];
                if (substr($match,-1) == "|")  $returnValue.="|";
                if ($data['title'])  $returnValue .= "|" . $data['title'];
            }
        //dbglog("REWRITE  : " . $match . "  ---->   " . $returnValue);
        return $returnValue;
    }
}

