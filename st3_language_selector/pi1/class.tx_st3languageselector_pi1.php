<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 ST3elkartea <>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Lang selector' for the 'st3_lang' extension.
 *
 * @author	ST3elkartea <>
 * @package	TYPO3
 * @subpackage	tx_st3lang
 */
class tx_st3languageselector_pi1 extends tslib_pibase {
    public $prefixId      = 'tx_st3languageselector_pi1';        // Same as class name
    public $scriptRelPath = 'pi1/class.tx_st3languageselector_pi1.php';    // Path to this script relative to the extension dir.
    public $extKey        = 'st3_language_selector';    // The extension key.
    public $pi_checkCHash = true;
    
    private $tipolink_conf;
    private $local_cObj;
    
    /**
     * The main method of the PlugIn
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     * @return    The content that is displayed on the website
     */
    public function main($content, $conf){
        $this->conf = $conf;
        $this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
        $this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
        
        $defaultLanguageISOCode = trim($this->conf['defaultLanguageISOCode']) ?  strtoupper(trim($this->conf['defaultLanguageISOCode'])) : 'EN';

        $this->templateCode = $this->cObj->fileResource($this->conf['templateFile']);
        $template['total'] = $this->cObj->getSubpart($this->templateCode, $this->getLayout());
        $template['langs'] = $this->cObj->getSubpart($template['total'], '###LANGS###');
        $subPartContent  = '';
        $linkConf = $this->conf['typolink.'];
        $linkConf['parameter.']['current'] = 1;
        
        $flagsDir = 'typo3/gfx/flags/';
        $displayMode = $this->conf['displayMode'];
        $displayLocalLangName = (int) $this->conf['displayLocalLangName'];
        
        
        $languages = $this->getLanguages();
        $langParam = (int) $_GET['L'];
        foreach($languages as $lang){
            $linkConf['additionalParams'] .= '&L=' . $lang['uid'];
            $linkConf['title'] = $lang['title'];
            
            $linkConf['ATagParams'] = '';
            if($langParam == $lang['uid']){
                $linkConf['ATagParams'] .= ' class="current-lang"';
            }elseif(empty($langParam) and $GLOBALS['TSFE']->sys_language_uid == 0 and $lang['lg_iso_2'] == $defaultLanguageISOCode){
                $linkConf['ATagParams'] .= ' class="current-lang"';
            }
            
            if(trim($this->conf['target'])){
                $linkConf['ATagParams'] .= ' target="' . trim($this->conf['target']) . '"';
            }
            
            $flag = '<img src="' . $flagsDir . $lang['flag'] . '" alt="Flag" />';
            $title = $lang['title'];
            if($displayLocalLangName){
                $title = $lang['lg_name_local'];
            }
            
            if($displayMode == 'flags'){
                $markerArray['###LINK###'] = $this->local_cObj->typoLink($flag, $linkConf);
            }elseif($displayMode == 'text'){
                $markerArray['###LINK###'] = $this->local_cObj->typoLink($title, $linkConf);
            }else{
                $markerArray['###LINK###'] = $this->local_cObj->typoLink($flag . $title, $linkConf);
            }
            
            $markerArray['###FLAG###'] =  $flag;
            
            $subPartContent .= $this->cObj->substituteMarkerArray($template['langs'], $markerArray);
        }
        
        return $this->cObj->substituteSubpart($template['total'], '###LANGS###', $subPartContent);
    }
    
    
    private function getLanguages(){
        $languagesOrder = explode(',', str_replace(' ', '', $this->conf['languagesOrder']));
        $languageNames = explode(',', str_replace(' ', '', $this->conf['languageNames']));
        
        $tableA = 'sys_language';
        $tableB = 'static_languages';
        $selectFields = $tableA . '.uid, ' . $tableA . '.title, ' . $tableA . '.flag, ' . $tableB . '.lg_iso_2, ' . $tableB . '.lg_name_en, ' . $tableB . '.lg_country_iso_2, ' . $tableB . '.lg_name_local';
        $table = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
        
        $languagesUidsList = trim($this->conf['languagesUidsList']);
        if (!empty($languagesUidsList)) {
            $whereClause = $tableA . '.uid IN (' . $languagesUidsList . ') ';
        } else {
            $whereClause = '1=1 ';
        }
        $whereClause .= $this->cObj->enableFields($tableA);
        $whereClause .= $this->cObj->enableFields($tableB);
        
        $languages = array();
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($selectFields, $table, $whereClause);
        
        
        if(empty($languagesOrder)){
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
                $languages[] = $row;
            }
        }else{
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
                $position = array_keys($languagesOrder, $row['uid']);
                if(!empty($position)){
                    if(!empty($languageNames[$position[0]])){
                        $row['title'] = $languageNames[$position[0]];
                    }
                    $languages[$position[0]] = $row;
                }else{
                    $languages[] = $row;
                }
            }            
            ksort($languages);
        }
        return $languages;
    }
    
    
    private function getLayout(){
        $layout = $this->conf['layout'];
        if(empty($layout)){
            return '###DEFAULT###';
        }else{
            return '###' . strtoupper($layout) . '###';
        }
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/st3_language_selector/pi1/class.tx_st3languageselector_pi1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/st3_language_selector/pi1/class.tx_st3languageselector_pi1.php']);
}

?>