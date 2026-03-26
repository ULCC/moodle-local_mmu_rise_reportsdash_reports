<?php

namespace local_mmu_rise_reportsdash_reports\forms;

use local_mmu_rise_reportsdash_reports;
use core_tag_tag;

require_once("$CFG->libdir/formslib.php");

class mmu_rise_engagement_report_filter_form extends \moodleform {


    function definition() {
        global $DB, $CFG, $PAGE;


        $imports = (object)$this->_customdata;

        $mform =& $this->_form;

        $buttonarray = array();

/*

        // Category filter
        $categorysql = "SELECT id, name, path
                        FROM {course_categories}
                        WHERE path LIKE CONCAT('/',(SELECT id FROM {course_categories} where name = 'University of Exeter'),'/%')";

        $categories = $DB->get_records_sql($categorysql);
        $categoryoptions = array('0'=>get_string('selectcategory', 'local_mmu_rise_reportsdash_reports'));

        foreach ($categories as $category){
            $pathname = dc_get_cat_pathnames($category->path);
            $categoryoptions[$category->id] = $pathname;
        }

        $mform->addElement('autocomplete', 'categoryfilter', get_string('categoryfilter','local_mmu_rise_reportsdash_reports'), $categoryoptions);

        // Course fullname
        $mform->addElement('text', 'coursefullnamefilter', get_string('coursefullnamefilter','local_mmu_rise_reportsdash_reports'));
        $mform->setType('coursefullnamefilter',PARAM_TEXT);

*/

        // enable submission date
        $mform->addElement('checkbox', 'enablesubdate', get_string('enabletimedateperiod','local_mmu_rise_reportsdash_reports'));

        // submission date from
        $mform->addElement('date_selector','timedatefromfilter',get_string('timedatefrom','local_mmu_rise_reportsdash_reports'));

        // submission date to
        $mform->addElement('date_selector','timedatetofilter',get_string('timedateto','local_mmu_rise_reportsdash_reports'));
        $mform->disabledIf('timedatefromfilter','enablesubdate', 'notchecked');
        $mform->disabledIf('timedatetofilter','enablesubdate','notchecked');



        $mform->addElement('hidden', 'rptname', $imports->rptname);
        $mform->setType('rptname',PARAM_TEXT);

        // BUTTONS
        $buttonarray[] = & $mform->createElement('submit', 'submitbutton', get_string('filter', 'block_reportsdash'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }


    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /*
        if (!isset($data['categoryfilter']) ){
            $errors['categoryfilter'] = get_string('categoryerror', 'local_mmu_rise_reportsdash_reports');
        }
        */

        if (isset($data['timedatefromfilter']) && isset($data['timedatetofilter']) && $data['timedatefromfilter'] > $data['timedatetofilter']){
            $errors['timedatetofilter'] = get_string('daterangeerror', 'block_reportsdash');
        }
        return $errors;
    }

}
