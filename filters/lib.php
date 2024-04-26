<?php 

require_once $CFG->libdir . '/formslib.php';
//require_once $CFG->dirroot . '/course/format/bulkcertification/filters/format_bulkcertification_filter_forms.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/filters/select.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/filters/date.php';
require_once $CFG->dirroot . '/course/format/bulkcertification/filters/text.php';

class format_bulkcertification_add_filter_form extends moodleform {

    function definition() {
        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];

        $mform->addElement('header', 'newfilter', get_string('newfilter','filters'));

        foreach($fields as $ft) {
            $ft->setupForm($mform);
        }

        // in case we wasnt to track some page params
        if ($extraparams) {
            foreach ($extraparams as $key=>$value) {
                $mform->addElement('hidden', $key, $value);
                $mform->setType($key, PARAM_RAW);
            }
        }

        // Add button
        $mform->addElement('submit', 'addfilter', get_string('addfilter','filters'));

    }
}


/**
* The base user filter class. All abstract classes must be implemented.
*/
class format_bulkcertification_filter_type {
    /**
    * The name of this filter instance.
    */
    public $_name;

    /**
    * The label of this filter instance.
    */
    public $_label;

    /**
    * Advanced form element flag
    */
    public $_advanced;

    /**
    * Constructor
    * @param string $name the name of the filter instance
    * @param string $label the label of the filter instance
    * @param boolean $advanced advanced form element flag
    */
    function __construct($name, $label, $advanced) {
        $this->_name     = $name;
        $this->_label    = $label;
        $this->_advanced = $advanced;
    }

    /**
    * Returns the condition to be used with SQL where
    * @param array $data filter settings
    * @return string the filtering condition or null if the filter is disabled
    */
    function get_sql_filter($data) {
        error('Abstract method get_sql_filter() called - must be implemented');
    }

    /**
    * Retrieves data from the form data
    * @param object $formdata data submited with the form
    * @return mixed array filter data or false when filter not set
    */
    function check_data($formdata) {
        error('Abstract method check_data() called - must be implemented');
    }

    /**
    * Adds controls specific to this filter in the form.
    * @param object $mform a MoodleForm object to setup
    */
    function setupForm(&$mform) {
        error('Abstract method setupForm() called - must be implemented');
    }

    /**
    * Returns a human friendly description of the filter used as label.
    * @param array $data filter settings
    * @return string active filter label
    */
    function get_label($data) {
        error('Abstract method get_label() called - must be implemented');
    }
}

/**
 * Bulk filtering wrapper class.
 */
class format_bulkcertification_filtering {
    /** @var array */
    public $_fields;
    /** @var */
    public $_addform;
    /** @var */
    public $_activeform;

    /**
    * Contructor
    */
    function __construct($baseurl = null, $extraparams = null) {
        global $SESSION;

        if (!isset($SESSION->format_bulkcertification_filtering)) {
            $SESSION->format_bulkcertification_filtering = array();
        }

        $fieldnames = array('certificatename' => 0, 'bulktime' => 1, 'issuingid' => 1, 'coursename' => 1, 'code' => 1, 'groupcode' => 1, 'customtime' => 1);

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // fist the new filter form
        $this->_addform = new format_bulkcertification_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));

        if ($adddata = $this->_addform->get_data(false)) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->format_bulkcertification_filtering)) {
                    $SESSION->format_bulkcertification_filtering[$fname] = array();
                }
                $SESSION->format_bulkcertification_filtering[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new format_bulkcertification_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));
        }

        // now the active filters
        $this->_activeform = new format_bulkcertification_bulk_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));
        if ($adddata = $this->_activeform->get_data(false)) {
            if (!empty($adddata->removeall)) {
                $SESSION->format_bulkcertification_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->format_bulkcertification_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->format_bulkcertification_filtering[$fname])) {
                        unset($SESSION->format_bulkcertification_filtering[$fname]);
                    }
                }
            }

            // clear+reload the form
            $_POST = array();
            $this->_activeform = new format_bulkcertification_bulk_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));
        }
        // now the active filters
    }

    /**
    * Creates known bulk filter if present
    * @param string $fieldname
    * @param boolean $advanced
    * @return object filter
    */
    public function get_field($fieldname, $advanced) {
        global $USER, $CFG, $DB, $SITE, $COURSE;

        switch ($fieldname) {
            case 'bulktime':        return new format_bulkcertification_filter_date('bulktime', get_string('bulktime', 'format_bulkcertification'), $advanced, 'bulktime');
            case 'issuingid':
                $sql = "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname FROM {bulkcertification_bulk} AS bb INNER JOIN {user} AS u ON bb.courseid = ? AND u.id = bb.issuingid";
                $users = $DB->get_records_sql_menu($sql, array('courseid' => $COURSE->id));
                return new format_bulkcertification_filter_select('issuingid', get_string('issuing', 'format_bulkcertification'), $advanced, 'issuingid', $users);
            case 'coursename':      return new format_bulkcertification_filter_text('coursename', get_string('objective_name', 'format_bulkcertification'), $advanced, 'coursename');
            case 'code':            return new format_bulkcertification_filter_text('code', get_string('code', 'format_bulkcertification'), $advanced, 'code');
            case 'groupcode':       return new format_bulkcertification_filter_text('groupcode', get_string('externalcode', 'format_bulkcertification'), $advanced, 'groupcode');
            case 'customtime':      return new format_bulkcertification_filter_date('customtime', get_string('objective_date', 'format_bulkcertification'), $advanced, 'customtime');
            case 'certificatename': return new format_bulkcertification_filter_text('certificatename', get_string('template', 'format_bulkcertification'), $advanced, 'certificatename');
            default:                return null;
        }
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array $params named params (recommended prefix ex)
     * @return array sql string and $params
     */
    public function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;

        if (!empty($SESSION->format_bulkcertification_filtering)) {
            foreach ($SESSION->format_bulkcertification_filtering as $fname => $datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // Filter not used.
                }
                $field = $this->_fields[$fname];
                foreach ($datas as $i => $data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return array('', array());
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params);
        }
    }

    /**
    * Print the add filter form.
    */
    function display_add() {
        $this->_addform->display();
    }

    /**
    * Print the active filter form.
    */
    function display_active() {
        $this->_activeform->display();
    }

}


function format_bulkcertification_get_bulk_listing($get = true, $sort='id', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='', array $extraparams=null, $extracontext = null) {

    global $CFG, $USER, $DB;

    $select = '';
    $params = array();

    if ($extraselect) {
        $select = " WHERE $extraselect ";
        $params = (array)$extraparams;
    }

    if (!empty($search)) {
        $search = trim($search);
    }

    if ($sort) {
        $sort = ' ORDER BY '. $sort .' '. $dir;
    }

    if (!$get) {
        $sSelect = "SELECT COUNT(*) ";
    }
    else {
        $sSelect = "SELECT * ";
    }

    $sSelect .= " FROM {bulkcertification_bulk}";
    $sSelect .= " $select $sort";

    if ($get) {
        return $DB->get_records_sql($sSelect, $params, $page, $recordsperpage);
    }
    else {
        return $DB->count_records_sql($sSelect, $params);
    }
}



class format_bulkcertification_bulk_active_filter_form extends moodleform {

    function definition() {
        global $SESSION; // this is very hacky :-(

        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];

        if (!empty($SESSION->format_bulkcertification_filtering)) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->format_bulkcertification_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $fields)) {
                    continue; // filter not used
                }
                $field = $fields[$fname];
                foreach($datas as $i=>$data) {
                    $description = $field->get_label($data);
                    $mform->addElement('checkbox', 'filter['.$fname.']['.$i.']', null, $description);
                }
            }

            if ($extraparams) {
                foreach ($extraparams as $key=>$value) {
                    $mform->addElement('hidden', $key, $value);
                    $mform->setType($key, PARAM_RAW);
                }
            }

            $objs = array();
            $objs[] = &$mform->createElement('submit', 'removeselected', get_string('removeselected','filters'));
            $objs[] = &$mform->createElement('submit', 'removeall', get_string('removeall','filters'));
            $mform->addElement('group', 'actfiltergrp', '', $objs, ' ', false);
        }
    }
}

// ===============================================================================================
// ===============================================================================================

/**
 * Objectives filters
 *
 *
 */

 /**
 * Objectives filtering wrapper class.
 */
class format_bulkcertification_objective_filtering {
    /** @var array */
    public $_fields;
    /** @var */
    public $_addform;
    /** @var */
    public $_activeform;

    /**
    * Contructor
    */
    function __construct($baseurl = null, $extraparams = null) {
        global $SESSION;

        if (!isset($SESSION->format_bulkcertification_objective_filtering)) {
            $SESSION->format_bulkcertification_objective_filtering = array();
        }

        $fieldnames = array('objectivename' => 0, 'code' => 1, 'hours' => 1);

        $this->_fields  = array();

        foreach ($fieldnames as $fieldname=>$advanced) {
            if ($field = $this->get_field($fieldname, $advanced)) {
                $this->_fields[$fieldname] = $field;
            }
        }

        // fist the new filter form
        $this->_addform = new format_bulkcertification_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));

        if ($adddata = $this->_addform->get_data(false)) {
            foreach($this->_fields as $fname=>$field) {
                $data = $field->check_data($adddata);
                if ($data === false) {
                    continue; // nothing new
                }
                if (!array_key_exists($fname, $SESSION->format_bulkcertification_objective_filtering)) {
                    $SESSION->format_bulkcertification_objective_filtering[$fname] = array();
                }
                $SESSION->format_bulkcertification_objective_filtering[$fname][] = $data;
            }
            // clear the form
            $_POST = array();
            $this->_addform = new format_bulkcertification_add_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));
        }

        // now the active filters
        $this->_activeform = new format_bulkcertification_objectives_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));
        if ($adddata = $this->_activeform->get_data(false)) {
            if (!empty($adddata->removeall)) {
                $SESSION->format_bulkcertification_objective_filtering = array();

            } else if (!empty($adddata->removeselected) and !empty($adddata->filter)) {
                foreach($adddata->filter as $fname=>$instances) {
                    foreach ($instances as $i=>$val) {
                        if (empty($val)) {
                            continue;
                        }
                        unset($SESSION->format_bulkcertification_objective_filtering[$fname][$i]);
                    }
                    if (empty($SESSION->format_bulkcertification_objective_filtering[$fname])) {
                        unset($SESSION->format_bulkcertification_objective_filtering[$fname]);
                    }
                }
            }

            // clear+reload the form
            $_POST = array();
            $this->_activeform = new format_bulkcertification_objectives_active_filter_form($baseurl, array('fields'=>$this->_fields, 'extraparams' => $extraparams));
        }
        // now the active filters
    }

    /**
    * Creates known objectives filter if present
    * @param string $fieldname
    * @param boolean $advanced
    * @return object filter
    */
    public function get_field($fieldname, $advanced) {
        global $USER, $CFG, $DB, $SITE, $COURSE;

        switch ($fieldname) {
            case 'objectivename':   return new format_bulkcertification_filter_text('objectivename', get_string('objective_name', 'format_bulkcertification'), $advanced, 'name');
            case 'code':            return new format_bulkcertification_filter_text('code', get_string('code', 'format_bulkcertification'), $advanced, 'code');
            case 'hours':           return new format_bulkcertification_filter_text('hours', get_string('objective_hours', 'format_bulkcertification'), $advanced, 'hours');
            default:                return null;
        }
    }

    /**
     * Returns sql where statement based on active user filters
     * @param string $extra sql
     * @param array $params named params (recommended prefix ex)
     * @return array sql string and $params
     */
    public function get_sql_filter($extra='', array $params=null) {
        global $SESSION;

        $sqls = array();
        if ($extra != '') {
            $sqls[] = $extra;
        }
        $params = (array)$params;

        if (!empty($SESSION->format_bulkcertification_objective_filtering)) {
            foreach ($SESSION->format_bulkcertification_objective_filtering as $fname => $datas) {
                if (!array_key_exists($fname, $this->_fields)) {
                    continue; // Filter not used.
                }
                $field = $this->_fields[$fname];
                foreach ($datas as $i => $data) {
                    list($s, $p) = $field->get_sql_filter($data);
                    $sqls[] = $s;
                    $params = $params + $p;
                }
            }
        }

        if (empty($sqls)) {
            return array('', array());
        } else {
            $sqls = implode(' AND ', $sqls);
            return array($sqls, $params);
        }
    }

    /**
    * Print the add filter form.
    */
    function display_add() {
        $this->_addform->display();
    }

    /**
    * Print the active filter form.
    */
    function display_active() {
        $this->_activeform->display();
    }

}


function format_bulkcertification_get_objectives_listing($get = true, $sort='id', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='', $extraselect='', array $extraparams=null, $extracontext = null) {

    global $CFG, $USER, $DB;

    $select = '';
    $params = array();

    if ($extraselect) {
        $select = " WHERE $extraselect ";
        $params = (array)$extraparams;
    }

    if (!empty($search)) {
        $search = trim($search);
    }

    if ($sort) {
        $sort = ' ORDER BY '. $sort .' '. $dir;
    }

    if (!$get) {
        $sSelect = "SELECT COUNT(*) ";
    }
    else {
        $sSelect = "SELECT * ";
    }

    $sSelect .= " FROM {bulkcertification_objectives}";
    $sSelect .= " $select $sort";

    if ($get) {
        return $DB->get_records_sql($sSelect, $params, $page, $recordsperpage);
    }
    else {
        return $DB->count_records_sql($sSelect, $params);
    }
}



class format_bulkcertification_objectives_active_filter_form extends moodleform {

    function definition() {
        global $SESSION; // this is very hacky :-(

        $mform       =& $this->_form;
        $fields      = $this->_customdata['fields'];
        $extraparams = $this->_customdata['extraparams'];

        if (!empty($SESSION->format_bulkcertification_objective_filtering)) {
            // add controls for each active filter in the active filters group
            $mform->addElement('header', 'actfilterhdr', get_string('actfilterhdr','filters'));

            foreach ($SESSION->format_bulkcertification_objective_filtering as $fname=>$datas) {
                if (!array_key_exists($fname, $fields)) {
                    continue; // filter not used
                }
                $field = $fields[$fname];
                foreach($datas as $i=>$data) {
                    $description = $field->get_label($data);
                    $mform->addElement('checkbox', 'filter['.$fname.']['.$i.']', null, $description);
                }
            }

            if ($extraparams) {
                foreach ($extraparams as $key=>$value) {
                    $mform->addElement('hidden', $key, $value);
                    $mform->setType($key, PARAM_RAW);
                }
            }

            $objs = array();
            $objs[] = &$mform->createElement('submit', 'removeselected', get_string('removeselected','filters'));
            $objs[] = &$mform->createElement('submit', 'removeall', get_string('removeall','filters'));
            $mform->addElement('group', 'actfiltergrp', '', $objs, ' ', false);
        }
    }
}

