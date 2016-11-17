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
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

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
    
    public function rewrite_button($match, $pos, $state, $plugin, helper_plugin_move_handler $handler){
        $returnValue = '';
        switch(substr($match, 0, 2)){
            case '[[':
                $returnValue = $this->_rewrite_button_link($match, $handler);
                break;
            case ']]':
                $returnValue = $this->_rewrite_button_close($match, $handler);
                break;
            default:
                $returnValue = $this->_rewrite_button_text($match, $handler);
                break;
        }
    
        return $returnValue;
    }
    
    protected function _rewrite_button_link($match, $handler){
        if(strpos($match, '}') !== false){
            $returnValue = substr($match, 0, strpos($match, '}')+1);    //keep old configuration
            $old_id = substr($match, strpos($match, '}')+1, -1);        //get old id from between "}" and "|"
        } else {
            throw new Exception('Button syntax incorrect.<br>Link could not be adjusted');
        }
    
        //retrieve new id from the move handler
        $new_id = $handler->resolveMoves($old_id, 'page');
        $returnValue .= $handler->relativeLink($old_id, $new_id, 'page');
    
        //check for last char being the escape character between link and text
        if(substr($returnValue, -1) !== '|'){
            $returnValue .= '|';
        }
        return $returnValue;
    }
    
    protected function _rewrite_button_text($match, $handler){
        $returnValue = $match;
        return $returnValue;
    }
    
    protected function _rewrite_button_close($match, $handler){
        $returnValue = $match;
        return $returnValue;
    }
}

