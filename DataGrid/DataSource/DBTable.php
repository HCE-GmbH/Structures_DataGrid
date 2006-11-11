<?php
/**
 * PEAR::DB_Table DataSource Driver
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
 * |          Mark Wiesemann <wiesemann@php.net>                          |
 * +----------------------------------------------------------------------+
 * </pre>
 *
 * CSV file id: $Id$
 * 
 * @version  $Revision$
 * @package  Structures_DataGrid_DataSource_DBTable
 * @category Structures
 */

require_once 'Structures/DataGrid/DataSource.php';

/**
 * PEAR::DB_Table Data Source Driver
 *
 * This class is a data source driver for the PEAR::DB_Table object
 *
 * SUPPORTED OPTIONS:
 * 
 * - view:   (string)  The view from $sql array in your DB_Table object. This
 *                     option is required.
 * - where:  (string)  A where clause for the SQL query.
 *                     (default: null)
 * - params: (array)   Placeholder parameters for prepare/execute
 *                     (default: array())
 * 
 * GENERAL NOTES:
 *
 * If you use aliases in the select part of your view, the count() method from
 * DB_Table and, therefore, $datagrid->getRecordCount() might return a wrong
 * result. To avoid this, DB_Table uses a special query for counting if it is
 * given via a view that needs to be named as '__count_' followed by the name
 * of the view that this counting view belongs to. (For example: if you have a
 * view named 'all', the counting view needs to be named as '__count_all'.)
 * 
 * To use update() and delete() methods, it is required that the indexes are
 * properly defined in the $idx array in your DB_Table subclass. If you have,
 * for example, created your database table yourself and did not setup the $idx
 * array, you can use the 'primary_key' option to define the primary key field.
 *
 * @version  $Revision$
 * @author   Andrew S. Nagy <asnagy@php.net>
 * @author   Mark Wiesemann <wiesemann@php.net>
 * @access   public
 * @package  Structures_DataGrid_DataSource_DBTable
 * @category Structures
 */
class Structures_DataGrid_DataSource_DBTable
    extends Structures_DataGrid_DataSource
{   
    /**
     * Reference to the DB_Table object
     *
     * @var object DB_Table
     * @access private
     */
    var $_object;

    /**
     * Fields/directions to sort the data by
     *
     * @var array Structure: array(fieldName => direction, ....)
     * @access private
     */
    var $_sortSpec = array();

    /**
     * Total number of rows 
     * 
     * This property caches the result of count() to avoid running the same
     * database query multiple times.
     *
     * @var int
     * @access private
     */
    var $_rowNum = null;

    /**
     * Fetchmode for getting the rows from the result object
     * 
     * @var int
     * @access private
     */
    var $_fetchMode = null;    

   /**
     * Constructor
     *
     * @access public
     */
    function Structures_DataGrid_DataSource_DBTable()
    {
        parent::Structures_DataGrid_DataSource();
        $this->_addDefaultOptions(array('view'   => null,
                                        'where'  => null,
                                        'params' => array()));
        $this->_setFeatures(array('multiSort' => true,
                                  'streaming' => true,
                                  'writeMode' => true));
    }
  
    /**
     * Bind
     *
     * @param   object DB_Table     $object     The object (subclass of
     *                                          DB_Table) to bind
     * @param   mixed               $options    Associative array of options.
     * @access  public
     * @return  mixed               True on success, PEAR_Error on failure
     */
    function bind(&$object, $options = array())
    {
        if (is_object($object) && is_subclass_of($object, 'db_table')) {
            $this->_object =& $object;
        } else {
            return PEAR::raiseError(
                'The provided source must be a subclass of DB_Table');
        }

        if (array_key_exists('view', $options) &&
            array_key_exists($options['view'], $object->sql)) {
            $this->setOptions($options);
            return true;
        } else {
            return PEAR::raiseError('Invalid or no "view" specified ' . 
                '[must be a key in $sql array of DB_Table subclass]');
        }
    }

    /**
     * Fetch
     *
     * @param   integer $offset     Offset (starting from 0)
     * @param   integer $limit      Limit
     * @param   boolean $streaming  Whether the data should be streamed or not
     * @access  public
     * @return  mixed               If streaming is enabled, the 2D array of the
     *                              records, otherwise, the query result object
     */
    function &fetch($offset = 0, $limit = null, $streaming = false)
    {
        if (!empty($this->_sortSpec)) {
            foreach ($this->_sortSpec as $field => $direction) {
                $sortArray[] = "$field $direction";
            }
            $sortString = join(', ', $sortArray);
        } else {
            $sortString = null;
        }

        $result = $this->_object->selectResult(
                            $this->_options['view'],
                            $this->_options['where'], 
                            $sortString, 
                            $offset, $limit,
                            $this->_options['params']);

        if (PEAR::isError($result)) {
            return $result;
        }

        if (is_a($result, 'db_result')) {
            $this->_fetchMode = DB_FETCHMODE_ASSOC;
        } else {
            $this->_fetchMode = MDB2_FETCHMODE_ASSOC;
        }

        // if the data should be streamed, return only the result object, but
        // don't fetch the records
        if ($streaming) {
            return $result;
        }

        $recordSet = array();

        // Fetch the Data
        if ($numRows = $result->numRows()) {
            while ($record = $this->fetchRow($result)) {
                $recordSet[] = $record;
            }
        }

        return $recordSet;
    }
    
    function fetchRow(&$result)
    {
        // try to fetch a row from the result
        $record = $result->fetchRow($this->_fetchMode);

        // if there is no row, return 
        if ($record === false) {
            return false;
        }

        // if needed, determine the fields to render
        if (!$this->_options['fields']) {
            $this->setOptions(array('fields' => array_keys($record)));
        }

        return $record;
    }

    /**
     * Count
     *
     * @access  public
     * @return  int         The number or records
     */
    function count()
    {
        // do we already have the cached number of records? (if yes, return it)
        if (!is_null($this->_rowNum)) {
            return $this->_rowNum;
        }
        // try to fetch the number of records
        $count = $this->_object->selectCount($this->_options['view'],
                                             $this->_options['where'],
                                             null, null, null,
                                             $this->_options['params']);
        // if we've got a number of records, save it to avoid running the same
        // query multiple times
        if (!PEAR::isError($count)) {
            $this->_rowNum = $count;
        }
        return $count;
    }

    /**
     * This can only be called prior to the fetch method.
     *
     * @access  public
     * @param   mixed   $sortSpec   A single field (string) to sort by, or a 
     *                              sort specification array of the form:
     *                              array(field => direction, ...)
     * @param   string  $sortDir    Sort direction: 'ASC' or 'DESC'
     *                              This is ignored if $sortDesc is an array
     */
    function sort($sortSpec, $sortDir = 'ASC')
    {
        if (is_array($sortSpec)) {
            $this->_sortSpec = $sortSpec;
        } else {
            $this->_sortSpec[$sortSpec] = $sortDir;
        }
    }

    /**
     * Return the primary key field name or numerical index
     *
     * @return  mixed    on success: Field name(s) of primary/unique fields
     *                   on error: PEAR_Error with message 'No primary key found'
     * @access  protected
     */
    function getPrimaryKey()
    {
        if (!is_null($this->_options['primary_key'])) {
            return $this->_options['primary_key'];
        }
        include_once 'DB/Table/Manager.php';
        // try to find a primary key or unique index (for a single field)
        foreach ($this->_object->idx as $idxname => $val) {
            list($type, $cols) = DB_Table_Manager::_getIndexTypeAndColumns($val,
                                                                      $idxname);
            if ($type == 'primary' || $type == 'unique') {
                return (array)$cols;
            }
        }
        return PEAR::raiseError('No primary key found');
    }

    /**
     * Record insertion method
     *
     * @param   array   $data   Associative array of the form: 
     *                          array(field => value, ...)
     * @return  mixed           Boolean true on success, PEAR_Error otherwise
     * @access  public                          
     */
    function insert($data)
    {
        $result = $this->_object->insert($data);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Record updating method
     *
     * @param   string  $key    Unique record identifier
     * @param   array   $data   Associative array of the form: 
     *                          array(field => value, ...)
     * @return  mixed           Boolean true on success, PEAR_Error otherwise
     * @access  public                          
     */
    function update($key, $data)
    {
        $primary_key = $this->getPrimaryKey();
        if (PEAR::isError($primary_key)) {
            return $primary_key;
        }
        $where = array();
        foreach ($primary_key as $single_key) {
            $where[] = $single_key . '=' . $this->_object->quote($key[$single_key]);
        }
        $where_str = join(' AND ', $where);
        $result = $this->_object->update($data, $where_str);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Record deletion method
     *
     * @param   string  $key    Unique record identifier
     * @return  mixed           Boolean true on success, PEAR_Error otherwise
     * @access  public                          
     */
    function delete($key)
    {
        $primary_key = $this->getPrimaryKey();
        if (PEAR::isError($primary_key)) {
            return $primary_key;
        }
        $where = array();
        foreach ($primary_key as $single_key) {
            $where[] = $single_key . '=' . $this->_object->quote($key[$single_key]);
        }
        $where_str = join(' AND ', $where);
        $result = $this->_object->delete($where_str);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;
    }

}

/* vim: set expandtab tabstop=4 shiftwidth=4: */
?>
