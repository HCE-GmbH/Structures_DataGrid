<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id$

//require_once 'XML/XUL.php';
require_once 'XML/Util.php';

/**
 * Structures_DataGrid_Renderer_XUL Class
 *
 * This renderer class will render an XUL listbox.
 * For additional information on the XUL Listbox, refer to this url:
 * http://www.xulplanet.com/references/elemref/ref_listbox.html
 *
 * @version     $Revision$
 * @author      Andrew S. Nagy <asnagy@webitecture.org>
 * @access      public
 * @package     Structures_DataGrid
 * @category    Structures
 * @todo        Implement PEAR::XML_XUL upon maturity
 */
class Structures_DataGrid_Renderer_XUL
{
    /**
     * The Datagrid object to render
     * @var object Structures_DataGrid
     */
    var $_dg;

    /**
     * The title of the datagrid
     * @var string
     */
    var $title = 'DataGrid';
    
    /**
     * An array of css url's
     * @var array
     */
    var $css = array('chrome://global/skin/');
    
    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XUL()
    {
    }

    /**
     * Sets the datagrid title
     *
     * @access  public
     * @param   string      $title      The title of the datagrid
     */
    function setTitle($title)
    {
        $this->title = $title;
    }
    
    /**
     * Adds a stylesheet to the list of stylesheets
     *
     * @access  public
     * @param   string      $url        The url of the stylesheet
     */
    function addStyleSheet($url)
    {
        array_push($this->css, $url);
    }
    
    /**
     * Generates the XUL for the DataGrid
     *
     * @access  public
     * @return  string      The XUL of the DataGrid
     */
    function render(&$dg)
    {
        header('Content-type: application/vnd.mozilla.xul+xml');
        
        //$doc &= $this->toXUL($dg);
        //$doc->send();
        
        echo $this->toXUL($dg);
    }
       
    /**
     * Generates the XUL for the DataGrid
     *
     * @access  public
     * @param   object Structures_DataGrid  $dg     The DataGrid to render
     * @return  string      The XUL of the DataGrid
     */
    function toXUL(&$dg)
    {
        $this->_dg = &$dg;
        
        // Define XML
        $xul = XML_Util::getXMLDeclaration() . "\n";
        
        // Define Stylesheets
        foreach ($this->css as $css) {
            $xul .= "<?xml-stylesheet href=\"$css\" type=\"text/css\"?>\n";
        }
        
        // Define Window Element
        $xul .= "<window title=\"$this->title\" " . 
                "xmlns=\"http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul\">\n";

        // Define Listbox Element
        $xul .= "<listbox rows=\"" . $this->_dg->rowLimit . "\">\n";
        
        // Build Grid Header
        $xul .= "  <listhead>\n";
        foreach ($this->_dg->columnSet as $column) {
            $xul .= '    ' . XML_Util::createTag('listheader', 
                    array('label' => $column->columnName,
                    'sortDirection' => 'natural')) . "\n";
        }
        $xul .= "  </listhead>\n";

        // Build Grid Body
        foreach ($this->_dg->recordSet as $row) {
            $xul .= "  <listitem>\n";
            foreach ($this->_dg->columnSet as $column) {
                // Build Content
                if ($column->formatter != null) {
                    $content = $column->formatter($row);
                } elseif ($column->fieldName == null) {
                    if ($column->autoFill != null) {
                        $content = $column->autoFill;
                    } else {
                        $content = $column->columnName;
                    }
                } else {
                    $content = $row[$column->fieldName];
                }

                $xul .= '    ' .
                        XML_Util::createTag('listcell',
                                            array('label' => $content)) . "\n";
            }

            $xul .= "  </listitem>\n";
        }
        $xul .= "</listbox>\n";
        $xul .= "</window>\n";

        return $xul;
    }

}

?>