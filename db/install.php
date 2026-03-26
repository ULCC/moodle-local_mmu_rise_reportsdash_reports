<?php



defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../config.php');


function xmldb_local_mmu_rise_reportsdash_reports_install() {
    global $DB,$CFG;

    if (!$DB->record_exists('block_reportsdash_extrep', array('classname' =>'mmu_rise_engagement_report' ))) {

        $externalreport = new     \stdClass();
        $externalreport->namespace = "\local_mmu_rise_reportsdash_reports\\reports";
        $externalreport->classpath = "\local_mmu_rise_reportsdash_reports\\reports\\mmu_rise_engagement_report";
        $externalreport->classname = "mmu_rise_engagement_report";

        $externalreport->dirpath = str_replace("\\", DIRECTORY_SEPARATOR, $CFG->dirroot . "\local\\mmu_rise_reportsdash_reports\\classes\\reports\\");
        $externalreport->langfile = str_replace("\\", DIRECTORY_SEPARATOR, $CFG->dirroot . "\local\\mmu_rise_reportsdash_reports\\mmu_rise_reportsdash_reports_lang.php");

        $DB->insert_record("block_reportsdash_extrep", $externalreport);

    }

    // Coursework Settings Amendments report
    if (!$DB->record_exists('block_reportsdash_extrep', array('classname' =>'mmu_rise_students_points_report' ))) {

        $externalreport = new \stdClass();
        $externalreport->namespace = "\local_mmu_rise_reportsdash_reports\\reports";
        $externalreport->classpath = "\local_mmu_rise_reportsdash_reports\\reports\\mmu_rise_students_points_report";
        $externalreport->classname = "mmu_rise_students_points_report";

        $externalreport->dirpath = str_replace("\\", DIRECTORY_SEPARATOR, $CFG->dirroot . "\local\\mmu_rise_reportsdash_reports\\classes\\reports\\");
        $externalreport->langfile = str_replace("\\", DIRECTORY_SEPARATOR, $CFG->dirroot . "\local\\mmu_rise_reportsdash_reports\\mmu_rise_reportsdash_reports_lang.php");

        $DB->insert_record("block_reportsdash_extrep", $externalreport);

    }



}


