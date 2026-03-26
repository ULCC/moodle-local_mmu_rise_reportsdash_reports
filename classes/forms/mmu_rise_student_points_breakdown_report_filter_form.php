<?php

namespace local_mmu_rise_reportsdash_reports\forms;

use local_mmu_rise_reportsdash_reports;
use core_tag_tag;

require_once("$CFG->libdir/formslib.php");

class mmu_rise_student_points_breakdown_report_filter_form extends \moodleform {


    function definition() {
        global $DB, $CFG, $PAGE;


        $imports = (object)$this->_customdata;

        $mform =& $this->_form;

        $buttonarray = array();




        $mform->addElement('hidden', 'rptname', $imports->rptname);
        $mform->setType('rptname',PARAM_TEXT);


    }


    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['timecreatedfromfilter']) && isset($data['timecreatedtofilter']) && $data['timecreatedfromfilter'] > $data['timecreatedtofilter']){
            $errors['timecreatedtofilter'] = get_string('daterangeerror', 'local_mmu_rise_reportsdash_reports');
        }
        return $errors;
    }

}