<?php
/**
 * XML Rendering Driver
 * 
 * <pre>
 * +----------------------------------------------------------------------+
 * | PHP version 4                                                        |
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 The PHP Group                                |
 * +----------------------------------------------------------------------+
 * | This source file is subject to version 2.0 of the PHP license,       |
 * | that is bundled with this package in the file LICENSE, and is        |
 * | available through the world-wide-web at                              |
 * | http://www.php.net/license/2_02.txt.                                 |
 * | If you did not receive a copy of the PHP license and are unable to   |
 * | obtain it through the world-wide-web, please send a note to          |
 * | license@php.net so we can mail you a copy immediately.               |
 * +----------------------------------------------------------------------+
 * | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
 * |          Olivier Guilyardi <olivier@samalyse.com>                    |
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @category Structures
 * @package  Structures_DataGrid_Renderer_XML
 */

require_once 'Structures/DataGrid/Renderer.php';
require_once 'XML/Util.php';

/**
 * XML Rendering Driver
 *
 * SUPPORTED OPTIONS:
 *
 * - useXMLDecl:    (bool)   Whether the XML declaration string should be added
 *                           to the output. The encoding attribute value will 
 *                           get set from the common "encoding" option. If you 
 *                           need to further customize the XML declaration 
 *                           (version, etc..), then please set "useXMLDecl" to
 *                           false, and add your own declaration string.
 * - outerTag:      (string) The name of the tag for the datagrid, without 
 *                           brackets
 * - rowTag:        (string) The name of the tag for each row, without brackets
 * - fieldTag:      (string) The name of the tag for each field inside a row, 
 *                           without brackets. The special value '{field}' is 
 *                           replaced by the field name.
 * - fieldAttribute:(string) The name of the attribute for the field name.
 *                           null stands for no attribute 
 * - labelAttribute:(string) The name of the attribute for the column label.
 *                           null stands for no attribute 
 *
 * SUPPORTED OPERATION MODES:
 *
 * - Container Support: no
 * - Output Buffering:  yes
 * - Direct Rendering:  no
 * - Streaming:         no
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @category Structures
 * @package  Structures_DataGrid_Renderer_XML
 */
class Structures_DataGrid_Renderer_XML extends Structures_DataGrid_Renderer
{

    /**
     * XML output
     * @var string
     * @access private
     */
    var $_xml;

    /**
     * Constructor
     *
     * Build default values
     *
     * @access public
     */
    function Structures_DataGrid_Renderer_XML()
    {
        parent::Structures_DataGrid_Renderer();
        $this->_addDefaultOptions(
            array(
                'useXMLDecl'        => true,
                'outerTag'          => 'DataGrid',
                'rowTag'            => 'Row',
                'fieldTag'          => '{field}',
                'fieldAttribute'    => null,
                'labelAttribute'    => null,
            )
        );
    }

    /**
     * Initialize a string for the XML code if it is not already existing
     * 
     * @access protected
     */
    function init()
    {
        $this->_xml = '';
    }

    /**
     * Generates the XML for the DataGrid
     *
     * @access  public
     * @return  string      The XML of the DataGrid
     */
    function toXML()
    {
        return $this->getOutput();
    }

    /**
     * Handles building the body of the DataGrid
     *
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
        if ($this->_options['useXMLDecl']) {
            $this->_xml .= XML_Util::getXMLDeclaration("1.0", 
                                $this->_options['encoding']) . "\n";
        }

        $this->_xml .= "<{$this->_options['outerTag']}>\n";
        for ($row = 0; $row < $this->_recordsNum; $row++) {
            $this->buildRow($row, $this->_records[$row]);
        }
        $this->_xml .= "</{$this->_options['outerTag']}>\n";
    }

    /**
     * Build a body row
     *
     * @param   int   $index Row index (zero-based)
     * @param   array $data  Record data. 
     * @access  protected
     * @return  void
     */
    function buildRow($index, $data)
    {
        $this->_xml .= "  <{$this->_options['rowTag']}>\n";
        foreach ($data as $col => $value) {
            $field = ($this->_options['fieldTag'] == '{field}') 
                   ? $this->_columns[$col]['field']
                   : $this->_options['fieldTag'];

            $attributes = array();
            if (!is_null($this->_options['fieldAttribute'])) {
                $attributes[$this->_options['fieldAttribute']] 
                    = $this->_columns[$col]['field'];
            }
            if (!is_null($this->_options['labelAttribute'])) {
                $attributes[$this->_options['labelAttribute']] 
                    = $this->_columns[$col]['label'];
            }

            $this->_xml .= '    ' . XML_Util::createTag($field, $attributes, $value) . "\n";
        }
        $this->_xml .= "  </{$this->_options['rowTag']}>\n";
    }

    /**
     * Retrieve output from the container object 
     *
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
        return $this->_xml;
    }


    /**
     * Render to the standard output
     *
     * @access  public
     */
    function render()
    {
        header('Content-type: text/xml');
        parent::render();
    }
}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
