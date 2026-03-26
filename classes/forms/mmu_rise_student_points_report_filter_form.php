<?php

namespace local_mmu_rise_reportsdash_reports\forms;

use local_mmu_rise_reportsdash_reports;
use core_tag_tag;

require_once("$CFG->libdir/formslib.php");

class mmu_rise_student_points_report_filter_form extends \moodleform {


    function definition() {
        global $DB, $CFG, $PAGE;


        $imports = (object)$this->_customdata;

        $mform =& $this->_form;

        $buttonarray = array();


        //FILTERS

        $sql = "SELECT DISTINCT tt.id, t.name AS tag_name, t.rawname as tag_rawname
            FROM mdl_local_mmu_rise_theme_tags tt JOIN
            mdl_tag t ON (tt.tagid = t.id)  
            ORDER BY t.name ASC";

        $tag_records = $DB->get_records_sql($sql);

        $taglist = [];
        foreach ($tag_records as $tag) {
            $taglist[$tag->id] = $tag->tag_rawname;
        }

        $mform->addElement('select', 'themetagfilter', get_string('themetag','local_mmu_rise_reportsdash_reports'), $taglist)->setMultiple(true);
        $mform->setType('themetagfilter',PARAM_RAW);

        // Retrieve tags for 'coursework' and 'quiz'
        $sql = "SELECT DISTINCT lt.id, t.name AS tag_name, t.rawname as tag_rawname
            FROM mdl_local_mmu_rise_level_tags lt JOIN
            mdl_tag t ON (lt.tagid = t.id)  
            ORDER BY t.name ASC";

        $tag_records = $DB->get_records_sql($sql);

        $taglist = [];
        foreach ($tag_records as $tag) {
            $taglist[$tag->id] = $tag->tag_rawname;
        }

        $mform->addElement('select', 'leveltagfilter', get_string('leveltag','local_mmu_rise_reportsdash_reports'), $taglist)->setMultiple(true);
        $mform->setType('leveltagfilter',PARAM_RAW);




        //Student name
        $mform->addElement('text', 'firstnamefilter', get_string('firstnamefilter','local_mmu_rise_reportsdash_reports'));
        $mform->setType('firstnamefilter',PARAM_TEXT);

        $mform->addElement('text', 'lastnamefilter', get_string('lastnamefilter','local_mmu_rise_reportsdash_reports'));
        $mform->setType('lastnamefilter',PARAM_TEXT);

        // duedate

        // enable due date
        $mform->addElement('checkbox', 'enablepointscreated', get_string('enablepointscreated','local_mmu_rise_reportsdash_reports'));

        //from
        $mform->addElement('date_selector','timecreatedfromfilter',get_string('timecreatedfrom','local_mmu_rise_reportsdash_reports'));

        //to
        $mform->addElement('date_selector','timecreatedtofilter',get_string('timecreatedto','local_mmu_rise_reportsdash_reports'));

        $mform->disabledIf('timecreatedfromfilter','enablepointscreated', 'notchecked');
        $mform->disabledIf('timecreatedtofilter','enablepointscreated','notchecked');


        $mform->addElement('hidden', 'rptname', $imports->rptname);
        $mform->setType('rptname',PARAM_TEXT);

        // BUTTONS
        $buttonarray[] = & $mform->createElement('submit', 'submitbutton', get_string('filter', 'local_mmu_rise_reportsdash_reports'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }


    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['timecreatedfromfilter']) && isset($data['timecreatedtofilter']) && $data['timecreatedfromfilter'] > $data['timecreatedtofilter']){
            $errors['timecreatedtofilter'] = get_string('daterangeerror', 'local_mmu_rise_reportsdash_reports');
        }
        return $errors;
    }

}