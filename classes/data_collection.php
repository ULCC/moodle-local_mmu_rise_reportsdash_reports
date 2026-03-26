<?php

namespace   local_exeter_reportsdash_reports;
use mod_coursework\models\mitigation;
use mod_coursework\models\user;
use block_reportsdash;
use mod_coursework\models\plagiarism_flag;
use local_user_info_ext\user_info_ext_data_retrieve;

require_once($CFG->dirroot.'/tag/classes/tag.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/coursework/lib.php');
require_once($CFG->dirroot . '/local/exeter_reportsdash_reports/locallib.php');

class data_collection {




    /**
     * Create table and/or
     * Insert data to coursework_settings_amendments table
     *
     * @throws \dml_exception
     */
    function insert_coursework_settings_amendments_data($lastcron) {
        global $DB;
        //$lastcron = ($lastcron == 0)? strtotime('midnight', time()) : $lastcron;

        $dbman = $DB->get_manager();

        if (!$dbman->table_exists('exe_cw_settings_amendments')) {
            $sql = "CREATE TABLE IF NOT EXISTS {exe_cw_settings_amendments}
                  (id bigint(10) NOT NULL AUTO_INCREMENT,
                   categoryid bigint(10) NOT NULL DEFAULT '0',
                   catname varchar(255) NOT NULL DEFAULT '',
                   catpath varchar(255) NOT NULL DEFAULT '',
                   catpathname longtext DEFAULT NULL,
                   courseid bigint(10) NOT NULL DEFAULT '0',
                   coursename longtext DEFAULT NULL,
                   assessmenttype varchar(255) DEFAULT NULL,
                   cmid bigint(10) DEFAULT NULL,
                   timecreated bigint(10) DEFAULT NULL,
                   changedby bigint(10) DEFAULT NULL,
                   action varchar(255) DEFAULT NULL,
                   other longtext DEFAULT NULL,
                   PRIMARY KEY (`id`))";
            $DB->execute($sql);
        }

        $sql = "INSERT INTO {exe_cw_settings_amendments}(categoryid,catname,catpath,catpathname,courseid,coursename,assessmenttype,cmid,timecreated,changedby,action,other) ";

        $sql .= "SELECT          
                             cc.id AS categoryid,
                             cc.name AS catname,
                             cc.path AS catpath,
                             '' as catpathname,
                             c.id AS courseid,
                             c.fullname as coursename,
                             'coursework' AS  assessmenttype,
                             contextinstanceid as cmid,
                             lg.timecreated AS timecreated,
                             userid AS changedby,
                             action,
                             other FROM (SELECT * FROM {logstore_standard_log}
                                         WHERE (eventname like '%coursework_settings_updated'
                                         OR (eventname like '%course_module_deleted' AND other LIKE '%coursework%'))
                                        AND timecreated > $lastcron
                                       ) lg
                                         JOIN {course} c ON c.id = lg.courseid
                                         JOIN  {course_categories} cc ON cc.id  = c.category 
                                         AND path LIKE CONCAT('/',(SELECT id FROM {course_categories} 
                                         WHERE name = 'University of Exeter'),'/%')";

        $DB->execute($sql);

        // update catpathname value
        $sql = "SELECT id, catpath
                FROM {exe_cw_settings_amendments}
                WHERE catpathname = ''";

        $categories = $DB->get_records_sql($sql);

        foreach($categories as $cat){
            $cat->catpathname = dc_get_cat_pathnames($cat->catpath);
            $DB->update_record('exe_cw_settings_amendments', $cat);
        }


    }



    /**
     * Create exe_submission_status_data table, this table provides data for the submission status and physical submissions reports
     */
    function    insert_submission_status_data()      {
        global  $DB;
        $field = (class_exists('local_user_info_ext\user_info_ext_data_retrieve'))? new user_info_ext_data_retrieve() : "";
        $this->drop_submission_status_data_table();


        $sql = "CREATE TABLE IF NOT EXISTS {exe_submission_status_data} (
                   id bigint(10) NOT NULL AUTO_INCREMENT,
                   userid bigint(20) NOT NULL DEFAULT '0',
                   username varchar(100) NOT NULL DEFAULT '',
                   firstname varchar(100) NOT NULL DEFAULT '',
                   lastname varchar(100) NOT NULL DEFAULT '',
                   SPR varchar(100) NOT NULL DEFAULT '',
                   categoryid bigint(10) DEFAULT NULL,
                   catname varchar(255) DEFAULT NULL,
                   catpath varchar(255) DEFAULT NULL,
                   catpathname longtext DEFAULT NULL,
                   courseid bigint(20) NOT NULL DEFAULT '0',
                   courseshortname varchar(255) NOT NULL DEFAULT '',
                   coursefullname varchar(254) NOT NULL DEFAULT '',
                   accessrestrictions bigint(20) DEFAULT NULL,
                   coursemoduleid bigint(20) NOT NULL DEFAULT '0',
                   visible tinyint(4) NOT NULL DEFAULT '0',
                   activitytype varchar(20) NOT NULL DEFAULT '',
                   activityid bigint(20) NOT NULL DEFAULT '0',
                   activityname varchar(255) NOT NULL DEFAULT '',
                   submissionstatus varchar(17) NOT NULL DEFAULT '',
                   duedate bigint(20) DEFAULT NULL,
                   assessduedate bigint(20) DEFAULT NULL,
                   timesubmitted bigint(20) DEFAULT NULL,
                   late varchar(21) DEFAULT NULL,
                   mittype varchar(32) DEFAULT NULL,
                   extended_deadline bigint(20) DEFAULT NULL,   
                   selfcert int(1) DEFAULT NULL,    
                   pre_defined_reason varchar(255) DEFAULT NULL,
                   plagiarismstatus varchar(255) DEFAULT NULL, 
                   plagiarismcomment text DEFAULT NULL,
                   group_id bigint(20) NOT NULL DEFAULT '0',
                   group_name varchar(254) NOT NULL DEFAULT '',
                   summativeassessmenttag bigint(20) NOT NULL DEFAULT '0',
                   submissiontypes varchar(254) DEFAULT '',
                   submissionmode varchar(254) DEFAULT '',
                   reminders bigint(20) DEFAULT NULL,
                   grade varchar(100) NULL,
                  PRIMARY KEY (`id`))";

        $DB->execute($sql);


        $summativeassessment = $DB->get_record('tag', array('name'=>'summative assessment'));


        // COURSEWORKS
        // get all courseworks within specified category
        $courseworkssql = " SELECT  cw.id as courseworkid,
                                    c.id AS courseid,
                                    cc.id AS categoryid,
				                    cc.name AS catname,
				                    cc.path AS catpath,                                  
                                    c.shortname AS courseshortname,
                                    c.visible as  coursevisible,
                                    c.fullname AS coursefullname
                            FROM  {course}    c
                            JOIN  {course_categories} cc ON cc.id  = c.category 
                                AND path LIKE CONCAT('/',(SELECT id FROM {course_categories} WHERE name = 'University of Exeter'),'/%')
                            JOIN  {coursework} cw on cw.course = c.id";

        $courseworks = $DB->get_records_sql($courseworkssql);

        //for each coursework get students
        foreach ($courseworks as $cw){

            $coursework = \mod_coursework\models\coursework::find($cw->courseworkid);

            if($coursework) {
                $course_module = $coursework->get_course_module();

                if($course_module) {
                    $modinfo = get_fast_modinfo($course_module->course);

                    $cms = $modinfo->get_cms();

                    if (empty($cms[$course_module->id])) {
                        continue;
                    }
                }

                $allocatables = $coursework->get_allocatables();

                //for each student/group get relevant data
                foreach ($allocatables as $allocatable) {

                    if (!empty($course_module->accessrestrictions)) {
                        $inf = '';

                        $cm = $DB->get_record('course_modules', array('id' => $course_module->accessrestrictions), 'id, course', MUST_EXIST);
                        if ($cm) {
                            $modinfo = get_fast_modinfo($cm->course);

                            $cms = $modinfo->get_cms();

                            if (empty($cms) || empty($cms[$cm->id])) return '';

                            $cminfo = $modinfo->get_cm($cm->id);

                            $availability = new \core_availability\info_module($cminfo);
                            $available = $availability->is_available($inf, false, $allocatable->id);

                            if ($available == false) {
                                continue;
                            }
                        }
                    }

                    // check if a student is suspended from the course
                    $coursecontext  =   \context_course::instance($cw->courseid);
                    if(!$coursework->use_groups && !is_enrolled($coursecontext, $allocatable->id, $withcapability = '', $onlyactive = true)){
                        continue;
                    }

                    $submissiondata = new \stdClass();
                    $submissiondata->userid = $allocatable->id; // user or group?
                    $submissiondata->username = (!$coursework->use_groups) ? $allocatable->username : '';
                    $submissiondata->firstname = (!$coursework->use_groups) ? $allocatable->firstname : '';
                    $submissiondata->lastname = (!$coursework->use_groups) ? $allocatable->lastname : '';
                    $submissiondata->SPR = ($field)? $field->get_user_field_value($cw->courseid, $allocatable->id,'spr'): "";
                    $submissiondata->categoryid = $cw->categoryid;
                    $submissiondata->catname = $cw->catname;
                    $submissiondata->catpath = $cw->catpath;
                    $submissiondata->catpathname = dc_get_cat_pathnames($cw->catpath);
                    $submissiondata->courseid = $cw->courseid;
                    $submissiondata->courseshortname = $cw->courseshortname;
                    $submissiondata->coursefullname = $cw->coursefullname;

                    $submissiondata->accessrestrictions = $coursework->get_coursemodule_id();
                    $submissiondata->coursemoduleid = $coursework->get_coursemodule_id();
                    $submissiondata->visible = ($cw->coursevisible == 0) ? 0 : $coursework->get_course_module()->visible;
                    $submissiondata->activitytype = 'coursework';
                    $submissiondata->activityid = $coursework->id;
                    $submissiondata->activityname = $coursework->name;

                    //submission data
                    $submission = $coursework->get_allocatable_submission($allocatable);
                    $duedate = $coursework->get_allocatable_deadline($allocatable->id);

                    $new_mitigation_params = array(
                        'allocatableid' => $allocatable->id,
                        'allocatabletype' => $allocatable->type(),
                        'courseworkid' => $coursework->id,
                    );
                    $mitigation = mitigation::find_or_build($new_mitigation_params);
                    $mittype = ($mitigation) ? $mitigation->type : 0;

                    $deadline = ($mitigation->type == 'extension') ? $mitigation->extended_deadline : $coursework->get_allocatable_deadline($allocatable->id);
                    $extension = ($mitigation->type == 'extension') ? $mitigation->extended_deadline : 0;
                    $lateseconds = 0;

                    if ($submission) {
                        $submissionstatus = $submission->timesubmitted <= $duedate ? "Submitted on time" : "Submitted late";
                        $lateseconds = ($submission->is_late() && (!$submission->has_mitigation('extension') || !$submission->submitted_within_extension())) ? $submission->timesubmitted - $deadline : 0;

                    } else {
                        $submissionstatus = "Not Submitted";
                    }

                    $submissiondata->submissionstatus = $submissionstatus;
                    $submissiondata->duedate = $deadline;
                    $submissiondata->assessduedate = $coursework->get_deadline();
                    $submissiondata->timesubmitted = $submission ? $submission->timesubmitted : 0;
                    $submissiondata->late = $lateseconds;
                    $submissiondata->mittype = $mittype;
                    $submissiondata->extended_deadline = $extension ? $mitigation->extended_deadline : 0;
                    $submissiondata->selfcert = $mitigation->selfcert;
                    $submissiondata->pre_defined_reason = $extension ? $mitigation->pre_defined_reason : '';

                    $pf = new plagiarism_flag();
                    $plagiarism_flag = $submission ? $pf->get_plagiarism_flag($submission): false;
                    $submissiondata->plagiarismstatus = $plagiarism_flag ? $plagiarism_flag->status : '';
                    $submissiondata->plagiarismcomment = $plagiarism_flag ? $plagiarism_flag->comment : '';

                    $submissiondata->group_id = ($coursework->use_groups) ? $allocatable->id : 0;
                    $submissiondata->group_name = ($coursework->use_groups) ? $allocatable->name() : '';
                    $tags = \core_tag_tag::get_item_tags_array('core', 'course_modules', $submissiondata->coursemoduleid);

                    $submissiondata->summativeassessmenttag = ($tags && in_array($summativeassessment->id, array_keys($tags))) ? 1 : 0;

                    $sql = "SELECT  GROUP_CONCAT(sp.name ORDER BY sp.id SEPARATOR ' ') AS submissiontypes
                       FROM {coursework_sub_plugin_cwk} spc
                       JOIN {coursework_sub_plugin} sp ON sp.id = spc.subpluginid
                       WHERE spc.courseworkid = :courseworkid
                       GROUP BY spc.courseworkid";

                    $subtypes = $DB->get_record_sql($sql, array('courseworkid' => $submissiondata->activityid));

                    $submissiondata->submissiontypes = $subtypes->submissiontypes;
                    $submissiondata->submissionmode = ($coursework->use_groups) ? 'group' : 'individual';


                    if ($coursework->use_groups != 'group') {
                        $reminders = $DB->get_record_sql('SELECT count(id) as no FROM {coursework_reminder} where type = "nonsubmission" AND userid =:userid AND coursework_id = :courseworkid',
                            array('userid' => $submissiondata->userid, 'courseworkid' => $submissiondata->activityid));
                        $reminders = $reminders->no;

                    } else {
                        $reminders = $DB->get_record_sql(' SELECT count(a.id) as no FROM (SELECT cr.id FROM mdl_coursework_reminder cr JOIN mdl_groups_members gm ON cr.userid = gm.userid JOIN mdl_groups g ON g.id = gm.groupid
                                                     WHERE type = "nonsubmission" AND coursework_id = :courseworkid and groupid = :groupid and g.courseid = :courseid GROUP BY remindernumber, groupid)a',
                            array('groupid' => $submissiondata->group_id, 'courseworkid' => $submissiondata->activityid, 'courseid' => $submissiondata->courseid));
                        $reminders = $reminders->no;
                    }

                    $submissiondata->reminders = $reminders;

                    if ($submission && $submission->get_final_feedback() && $submission->get_final_feedback()->finalised == 1) $submissiondata->grade = $submission->get_final_feedback()->grade;


                    if ($coursework->use_groups) {
                        $members = groups_get_members($allocatable->id);
                        foreach ($members as $member) {

                            $submissiondata->userid = $member->id;
                            $submissiondata->username = $member->username;
                            $submissiondata->firstname = $member->firstname;
                            $submissiondata->lastname = $member->lastname;
                            $submissiondata->SPR = ($field)? $field->get_user_field_value($cw->courseid, $member->id,'spr'): "";

                            $DB->insert_record('exe_submission_status_data', $submissiondata);
                        }
                    } else {
                        $DB->insert_record('exe_submission_status_data', $submissiondata);
                    }

                }
            }
        }


        // QUIZ

        $quizsql = " SELECT  q.id as quizid,
                             q.name as name,
                             q.timeclose as duedate,
                             c.id AS courseid,
                             cc.id AS categoryid,
				             cc.name AS catname,
				             cc.path AS catpath,   
                            c.shortname AS courseshortname,
                            c.visible as  coursevisible,
                            c.fullname AS coursefullname
                    FROM    {course}    c
                    JOIN  {course_categories} cc ON cc.id  = c.category 
                        AND path LIKE CONCAT('/',(SELECT id FROM {course_categories} WHERE name = 'University of Exeter'),'/%')
                    JOIN    {quiz} q on q.course = c.id";

        $quizess = $DB->get_records_sql($quizsql);

        //for each quiz get students
        foreach ($quizess as $quiz){

            $course_module = get_coursemodule_from_instance("quiz", $quiz->quizid, $quiz->courseid);
            if($course_module) {
                $context = \context_module::instance($course_module->id);

                $students = get_enrolled_users($context, array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'));

                //for each student get relevant data
                foreach ($students as $student) {

                    if (!empty($course_module->accessrestrictions)) {
                        $inf = '';

                        $cm = $DB->get_record('course_modules', array('id' => $course_module->accessrestrictions), 'id, course');
                        if ($cm) {
                            $modinfo = get_fast_modinfo($cm->course);

                            $cms = $modinfo->get_cms();

                            if (empty($cms) || empty($cms[$cm->id])) return '';

                            $cminfo = $modinfo->get_cm($cm->id);

                            $availability = new \core_availability\info_module($cminfo);
                            $available = $availability->is_available($inf, false, $allocatable->id);

                            if ($available == false) {
                                continue;
                            }
                        }
                    }

                    // check if a student is suspended from the course
                    $coursecontext  =   \context_course::instance($quiz->courseid);
                    if(!is_enrolled($coursecontext, $student->id, $withcapability = '', $onlyactive = true)){
                        continue;
                    }

                    $submissiondata = new \stdClass();
                    $submissiondata->userid = $student->id;
                    $submissiondata->username = $student->username;
                    $submissiondata->firstname = $student->firstname;
                    $submissiondata->lastname = $student->lastname;
                    $submissiondata->SPR = ($field)? $field->get_user_field_value($quiz->courseid, $allocatable->id,'spr'): "";
                    $submissiondata->categoryid = $quiz->categoryid;
                    $submissiondata->catname = $quiz->catname;
                    $submissiondata->catpath = $quiz->catpath;
                    $submissiondata->catpathname = dc_get_cat_pathnames($quiz->catpath);
                    $submissiondata->courseid = $quiz->courseid;
                    $submissiondata->courseshortname = $quiz->courseshortname;
                    $submissiondata->coursefullname = $quiz->coursefullname;

                    $submissiondata->accessrestrictions = $course_module->id;
                    $submissiondata->coursemoduleid = $course_module->id;
                    $submissiondata->visible = ($quiz->coursevisible == 0) ? 0 : $course_module->visible;
                    $submissiondata->activitytype = 'quiz';
                    $submissiondata->activityid = $quiz->quizid;
                    $submissiondata->activityname = $quiz->name;

                    //submission data
                    $sql = "SELECT id  AS submissionid,
                    userid,
                    timefinish AS timesubmitted
                    FROM  {quiz_attempts}
                    WHERE state = 'finished'
                     AND quiz = :quizid and userid = :userid
                     order by id desc limit 1";

                    $submission = $DB->get_record_sql($sql, array('quizid' => $quiz->quizid, 'userid' => $student->id));
                    if ($submission) {
                        $submissionstatus = "Submitted";
                    } else {
                        $submissionstatus = "Not Submitted";
                    }
                    $submissiondata->submissionstatus = $submissionstatus;
                    $submissiondata->duedate = $quiz->duedate;
                    $submissiondata->assessduedate = $quiz->duedate;
                    $submissiondata->timesubmitted = $submission ? $submission->timesubmitted : 0;
                    $submissiondata->late = 0;
                    $submissiondata->mittype = '';

                    $extensionsql = "SELECT ov.id, ov.quiz, ov.timeopen, gm.userid, ov.timeclose
                    FROM    {quiz_overrides}      ov
                    JOIN    {groups_members}  gm  ON  ov.groupid  =   gm.groupid
                    WHERE   ov.groupid  IS NOT NULL AND ov.groupid != 0
                    and ov.quiz = :quizid AND gm.userid = :userid";

                    $override = $DB->get_record_sql($extensionsql, array('quizid' => $quiz->quizid, 'userid' => $student->id));

                    $submissiondata->extended_deadline = $override ? $override->timeclose : 0;
                    $submissiondata->selfcert = '';
                    $submissiondata->pre_defined_reason = '';
                    $submissiondata->plagiarismstatus = '';
                    $submissiondata->plagiarismcomment = '';
                    $submissiondata->group_id = 0;
                    $submissiondata->group_name = '';
                    $tags = \core_tag_tag::get_item_tags_array('core', 'course_modules', $submissiondata->coursemoduleid);

                    $submissiondata->summativeassessmenttag = ($tags && in_array($summativeassessment->id, array_keys($tags))) ? 1 : 0;
                    $submissiondata->submissiontypes = '';
                    $submissiondata->submissionmode = 'individual';

                    $submissiondata->reminders = 0;


                    $quizgradesql = "SELECT quiz, userid, round(grade) AS grade
                                        FROM {quiz_grades}
                                        WHERE quiz =:quizid AND userid =:userid";
                    $quizgrade = $DB->get_record_sql($quizgradesql, array('quizid' => $quiz->quizid, 'userid' => $student->id));

                    if ($quizgrade) $submissiondata->grade = $quizgrade->grade;

                    $DB->insert_record('exe_submission_status_data', $submissiondata);

                }
            }
        }

    }

    /**
     * Drop exe_submission_status_data table
     *
     * @throws \dml_exception
     */
    function drop_submission_status_data_table() {
        global $DB;

        $sql = "DROP TABLE IF EXISTS {exe_submission_status_data}";

        $DB->execute($sql);
    }



    /**
     * Create exe_activity_data table, this table provides data for the tracking data report and the activity deadlines report
     */
    function    insert_activity_data()      {
        global  $DB;

        $this->drop_activity_data_table();


        $sql = "CREATE TABLE IF NOT EXISTS {exe_activity_data} ";

        $sql    .=   "SELECT
                       categoryid,
				       catname,
				       catpath,
				       catpathname,
                        courseid,
                        courseshortname,
                        coursefullname,
                        (
                            CASE
                            WHEN accessrestrictions IS NOT NULL THEN coursemoduleid
                            ELSE NULL
                            END
                        ) AS accessrestrictions,
                        coursemoduleid,
                        visible,
                        activitytype,
                        activityid,
                        activityname,
                        assessduedate as duedate,
                        COUNT(userid) AS allocatables,


                        SUM(IF(timesubmitted <> 0, 1, 0)) AS submissions,
                        SUM(IF(timesubmitted = 0, 1, 0)) AS 'nonsubmissions',
                        SUM(IF(late <> 0 , 1, 0)) AS late,
                        SUM(IF(extended_deadline IS NOT NULL && extended_deadline <> 0, 1, 0)) AS extensions,
                        SUM(IF(mittype = 'temporary' || mittype = 'permanent' , 1, 0)) AS exemptions,
                        SUM(reminders) as reminders,
                        SUM(IF(mittype <> '' && mittype IS NOT NULL, 1, 0)) AS totalmit,
                        (CASE WHEN summativeassessmenttag IS NOT NULL THEN 1 ELSE 0 END) AS summativeassessmenttag,
                        submissiontypes,
                        COUNT(grade) AS gradecount
                        FROM {exe_submission_status_data}
                        where submissionmode = 'individual'
                        group by coursemoduleid

                        UNION

                        SELECT

                        categoryid,
				        catname,
				        catpath,
				        catpathname,
                        courseid,
                        courseshortname,
                        coursefullname,
                        (
                            CASE
                            WHEN accessrestrictions IS NOT NULL THEN coursemoduleid
                            ELSE NULL
                            END
                        ) AS accessrestrictions,
                        coursemoduleid,
                        visible,
                        activitytype,
                        activityid,
                        activityname,
                        duedate,
                        COUNT(distinct group_id) AS allocatables,

                        SUM(IF(submissions <>0, 1, 0)) AS submissions,
                        SUM(IF(nonsubmissions <> 0, 1, 0)) AS 'nonsubmissions',
                        SUM(IF(late <> 0 , 1, 0)) AS late,
                        SUM(IF(extensions IS NOT NULL && extensions <> 0, 1, 0)) AS extensions,
                        SUM(IF(exemptions <>0 , 1, 0)) AS exemptions,
                         Sum(reminders) as reminders,
                        SUM(IF(totalmit <>0, 1, 0)) AS totalmit,
                        (CASE WHEN summativeassessmenttag IS NOT NULL THEN 1 ELSE 0 END) AS summativeassessmenttag,
                        submissiontypes,
                        COUNT(grade) AS gradecount
                        FROM (SELECT  group_id,
                                          categoryid,
				        catname,
				        catpath,
				        catpathname,
                        courseid,
                        courseshortname,
                        coursefullname,
                        (
                            CASE
                            WHEN accessrestrictions IS NOT NULL THEN coursemoduleid
                            ELSE NULL
                            END
                        ) AS accessrestrictions,
                        coursemoduleid,
                        visible,
                        activitytype,
                        activityid,
                        activityname,
                        assessduedate as duedate,
                        COUNT(distinct group_id) AS allocatables,
                        SUM(IF(timesubmitted <> 0, 1, 0)) AS submissions,
                        SUM(IF(timesubmitted = 0, 1, 0)) AS 'nonsubmissions',
                        SUM(IF(late <> 0 , 1, 0)) AS late,
                        SUM(IF(extended_deadline IS NOT NULL && extended_deadline <> 0, 1, 0)) AS extensions,
                        SUM(IF(mittype = 'temporary' || mittype = 'permanent' , 1, 0)) AS exemptions,
                        reminders,
                        SUM(IF(mittype <> '' && mittype IS NOT NULL, 1, 0)) AS totalmit,

                        summativeassessmenttag,
                        submissiontypes,
                        grade,
                        group_name
                        FROM {exe_submission_status_data}
                        where submissionmode = 'group'

                        group by coursemoduleid, group_id
                        ) a

                        group by coursemoduleid";

        $DB->execute($sql);

    }



    /**
     * Drop exe_activity_data table
     *
     * @throws \dml_exception
     */
    function drop_activity_data_table() {
        global $DB;

        $sql = "DROP TABLE IF EXISTS {exe_activity_data}";

        $DB->execute($sql);
    }


    /**
     * Task for collation of grade transfer data
     *
     * @throws \coding_exception
     * @throws \ddl_exception
     * @throws \dml_exception
     */
    function    create_grade_transfer_data()      {
        global $CFG, $DB;

        ini_set('memory_limit', '4096M');

        $mydb = $DB;
        $count = 0;


        $sql = "DROP TABLE IF EXISTS {exe_grade_transfer_data_new}";

        $mydb->execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {exe_grade_transfer_data_new} (
                   id bigint(10) NOT NULL AUTO_INCREMENT,
                   courseid bigint(20) NOT NULL DEFAULT '0',
                   categoryid bigint(10) DEFAULT NULL,
                   catname varchar(255) DEFAULT NULL,
                   catpath varchar(255) DEFAULT NULL,
                   catpathname longtext DEFAULT NULL,
                   courseshortname varchar(255) NOT NULL DEFAULT '',
                   coursefullname varchar(254) NOT NULL DEFAULT '',
                   activitytype varchar(20) NOT NULL DEFAULT '',
                   activityid bigint(20) NOT NULL DEFAULT '0',
                   activityname varchar(255) NOT NULL DEFAULT '',
                   visible tinyint(4) NOT NULL DEFAULT '0',
                   cmid bigint(10) NOT NULL DEFAULT '0',
                   assessmentcode varchar(254) DEFAULT NULL,
                   studentid bigint(20) NOT NULL DEFAULT '0',
                   username varchar(100) NOT NULL DEFAULT '',
                   studentfirstname varchar(100) NOT NULL DEFAULT '',
                   studentlastname varchar(100) NOT NULL DEFAULT '',
                   SPR varchar(100) NOT NULL DEFAULT '',
                   originalscaleid bigint(10) DEFAULT NULL,
                   changedscaleid bigint(10) DEFAULT NULL,
                   originalgrade varchar(100) NULL,
                   gradedon bigint(20) DEFAULT NULL,
                   gradedbyid bigint(20) DEFAULT NULL,
                   gradedbyfirstname varchar(100) DEFAULT NULL,
                   gradedbylastname varchar(100) DEFAULT NULL,
                   changedgrade varchar(100) NULL,
                   changedgradeon  bigint(20) DEFAULT NULL,
                   gradechangedbyid bigint(20) DEFAULT NULL,
                   changedbyfirstname varchar(100) DEFAULT NULL,
                   changedbylastname varchar(100) DEFAULT NULL,
                   feedbackreleasedon  bigint(20) DEFAULT NULL,
                   queuedon bigint(20) DEFAULT NULL,
                   queuedbyid bigint(20) DEFAULT NULL,
                   queuedbyfirstname varchar(100) DEFAULT NULL,
                   queuedbylastname varchar(100) DEFAULT NULL,
                   exportedon bigint(20) DEFAULT NULL,
                   exportedbyid bigint(20)  DEFAULT NULL,
                   exportedbyfirstname varchar(100) DEFAULT NULL,
                   exportedbylastname varchar(100) DEFAULT NULL,
                   duedate bigint(20) DEFAULT NULL,
                   feedbackdate bigint(20) DEFAULT NULL,
                   disableautoindfeedback tinyint(1) DEFAULT NULL,
                   gradingauditenabled tinyint(1) DEFAULT '0',
                   gradingauditcompleted tinyint(1) DEFAULT '0',
                   gradingauditmoderatedgrade varchar(100) DEFAULT NULL,
                    gradingauditmoderateduser varchar(255) DEFAULT NULL ,       
                    gradingauditmoderateddate bigint(10) DEFAULT NULL ,           

                  PRIMARY KEY (id))";

        $mydb->execute($sql);

        $block = 5000;

        // COURSEWORK
        $courseworkssql = "SELECT    cs.id,
                                    cw.id as courseworkid,
                                    cw.deadline,
                                    cw.individualfeedback,
                                    c.id AS courseid,
                                    ad.categoryid,
                                    catname,
                                    catpath,
                                    catpathname,
                                    c.shortname AS courseshortname,
                                    c.fullname AS coursefullname,
                                    cs.id AS submissionid,
                                    cs.allocatableid,
                                    cs.allocatabletype,
                                    IF(c.visible = 0, 0 ,ad.visible) as visible,
                                    cmid,
                                    accessrestrictions,
                                    availability,
                                    gradingaudit
                            FROM  {coursework} cw  
                            JOIN  {local_exe_assessment_data} ad ON cw.id = ad.activityid and ad.modtype = 'coursework'
                            JOIN  {course}    c ON c.id = cw.course
                            JOIN  {coursework_submissions} cs ON cw.id = cs.courseworkid
                            ORDER BY ad.cmid";
        $current = 0;
        do {
            $count = $this->process_coursework($courseworkssql, $current, $block);
            $current += $count;
        } while($count >= $block);

        // QUIZ
        $quizessql = " SELECT   gg.id,
                                q.id as quizid,
                                c.id AS courseid,
                                ad.categoryid,
                                catname,
                                catpath,
                                catpathname,
                                c.shortname AS courseshortname,
                                c.fullname AS coursefullname,
                                gg.userid AS studentid,
                                IF(c.visible = 0, 0 ,ad.visible) as visible,
                                cmid,
                                q.name,
                                q.timeclose,
                                accessrestrictions,
                                availability
                            FROM {quiz} q  
                            JOIN {local_exe_assessment_data} ad ON q.id = ad.activityid and ad.modtype = 'quiz'
                            JOIN {course} c ON c.id = q.course
                            JOIN {grade_items} gi  ON gi.iteminstance= q.id
                            JOIN {grade_grades} gg on gi.id = gg.itemid AND gi.itemmodule = 'quiz' AND gg.finalgrade IS NOT NULL
                            ORDER BY cmid";

        $current = 0;
        do {
            $count = $this->process_quiz($quizessql, $current, $block);
            $current += $count;
        } while($count >= $block);

        // free memory
        unset($this->users);

        // drop old table
        $sql = "DROP TABLE IF EXISTS {exe_grade_transfer_data_old}";
        $mydb->execute($sql);
        // rename current table to old if exists
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('exe_grade_transfer_data');
        if($dbman->table_exists($table)) {
            $sql = "RENAME TABLE {exe_grade_transfer_data} TO {exe_grade_transfer_data_old}";
            $mydb->execute($sql);
        }
        // rename new table to current
        $sql = "RENAME TABLE {exe_grade_transfer_data_new} TO {exe_grade_transfer_data}";
        $mydb->execute($sql);

        return $count;
    }


    /**
     *
     * @param $sql
     * @param $offset
     * @param int $block
     * @return int
     */
    function process_coursework($sql, $offset, $block = 50000) {
        global $DB;
        $field = (class_exists('local_user_info_ext\user_info_ext_data_retrieve'))? new user_info_ext_data_retrieve() : "";
        $result = 0;
        $gradetransferdatatosave = [];
        $last_cmid = $transfer_grades = null;
        $courseworks_rs = $DB->get_recordset_sql($sql . " LIMIT $offset, $block");

        foreach ($courseworks_rs as $cw) {
            $result++;
            $submission = \mod_coursework\models\submission::find($cw->submissionid);
            if(!$submission || ($submission && !$finalfeedback = $submission->get_final_feedback())){
                continue;
            }

            $coursework = \mod_coursework\models\coursework::find($cw->courseworkid);

            $gradingaudit   =  \mod_coursework\models\grading_audit::find(array("courseworkid"=>$coursework->id));

            $users =  $submission->get_students();

            $courseworkscale    =   false;

            if ($coursework->grade <0)  {

                $scaleid    =   abs($coursework->grade);

                $scalerecord    =   $DB->get_record('scale',array('id'=>$scaleid));

                $courseworkscale    =   explode(',',$scalerecord->scale);

            }

            foreach ($users as $idx => $user) {

                $studentuser = $user;

                //get grade information from grade transfer
                if ($last_cmid != $cw->cmid) {
                    $transfer_grades = null;
                    $last_cmid = $cw->cmid;

                    $sql = "SELECT         id, scaleid, grade, studentid, assessmentcode
                             FROM           {local_grade_transfer}  lgt
                             WHERE           modtype = 'coursework'
                             AND             cmid = :cmid";
                    $gradetransferrecords_rs = $DB->get_recordset_sql($sql, ['cmid' => $cw->cmid]);
                    foreach ($gradetransferrecords_rs as $record) {
                        $transfer_grades[$record->studentid] = $record;
                    }
                    $gradetransferrecords_rs->close();
                }
                $gradetransferrecord = $transfer_grades[$user->id] ?? null;

                $gradetransferdata = new \stdClass();
                $gradetransferdata->courseid = $cw->courseid;
                $gradetransferdata->categoryid = $cw->categoryid;
                $gradetransferdata->catname = $cw->catname;
                $gradetransferdata->catpath = $cw->catpath;
                $gradetransferdata->catpathname = $cw->catpathname;
                $gradetransferdata->courseshortname = $cw->courseshortname;
                $gradetransferdata->coursefullname = $cw->coursefullname;
                $gradetransferdata->activitytype = 'coursework';
                $gradetransferdata->activityid = $coursework->id;
                $gradetransferdata->activityname = $coursework->name;
                $gradetransferdata->cmid = $cw->cmid;
                $gradetransferdata->visible = $cw->visible;
                $gradetransferdata->duedate = $cw->deadline;
                $gradetransferdata->feedbackdate = $cw->individualfeedback;
                $gradetransferdata->disableautoindfeedback = $coursework->disableindfeedbackautorelease;
                $transferinformation      =   new     \local_grade_transfer\coursework_transfer_information();
                $coursemodule = ['availability' => $cw->availability];
                $gradetransferdata->assessmentcode = (!empty($gradetransferrecord))? $gradetransferrecord->assessmentcode
                    : $transferinformation->get_student_assessment_code($studentuser->id, $cw->courseid, (object) $coursemodule);
                $gradetransferdata->studentid = $studentuser->id;
                $gradetransferdata->username = $studentuser->username;
                $gradetransferdata->studentfirstname = $studentuser->firstname;
                $gradetransferdata->studentlastname = $studentuser->lastname;
                $gradetransferdata->SPR = ($field)? $field->get_user_field_value($cw->courseid, $studentuser->id,'spr'): "";
                $gradetransferdata->originalscaleid =  (!empty($gradetransferrecord))? $gradetransferrecord->scaleid : null;
                $gradetransferdata->changedscaleid =  ($coursework->grade <0)? abs($coursework->grade) : null ;
                $gradetransferdata->originalgrade = (!empty($gradetransferrecord))  ?   (int) $gradetransferrecord->grade : null;
                $gradetransferdata->gradedon = $finalfeedback->timecreated;
                $gradetransferdata->gradedbyid = $finalfeedback->assessorid;
                $gradedby = false;
                if ($gradetransferdata->gradedbyid != -1) {
                    $gradedby = $this->get_user($gradetransferdata->gradedbyid);
                }
                $gradetransferdata->gradedbyfirstname = (!empty($gradedby)) ? $gradedby->firstname : '';
                $gradetransferdata->gradedbylastname = (!empty($gradedby)) ? $gradedby->lastname : 'Assessed in GradeMark';
                $gradetransferdata->changedgrade = $finalfeedback->grade;
                $gradetransferdata->changedgradeon = $finalfeedback->timemodified;
                $gradetransferdata->gradechangedbyid = $finalfeedback->lasteditedbyuser;
                $changedby = false;
                if ($gradetransferdata->gradechangedbyid != -1) {
                    $changedby = $this->get_user($gradetransferdata->gradechangedbyid);
                }

                $gradetransferdata->changedbyfirstname = (!empty($changedby->firstname)) ? $changedby->firstname : '';
                $gradetransferdata->changedbylastname = (!empty($changedby->lastname)) ? $changedby->lastname : 'Assessed in GradeMark';
                $gradetransferdata->feedbackreleasedon = $submission->firstpublished;

                if (!empty($gradetransferrecord)) {

                    $logs = $this->get_transfer_logs_by_action($gradetransferrecord->id, ['queued', 'transferred', 'completed']);
                    foreach ($logs as $log) {
                        $currentuser = empty($log->userid) ? null : $this->get_user($log->userid);
                        switch ($log->action) {
                            case 'queued':
                                // get queue log
                                $gradetransferdata->queuedon = (!empty($log)) ? $log->timecreated : null;
                                $gradetransferdata->queuedbyid = (!empty($log)) ? $log->userid : null;
                                $gradetransferdata->queuedbyfirstname = $currentuser ? $currentuser->firstname : null;
                                $gradetransferdata->queuedbylastname = $currentuser ? $currentuser->lastname : null;
                                break;
                            case 'transferred':
                                // get exported/transferred log
                                $gradetransferdata->exportedon = (!empty($log)) ? $log->timecreated : null;
                                $gradetransferdata->exportedbyid = (!empty($log)) ? $log->userid : null;
                                $gradetransferdata->exportedbyfirstname = $currentuser ? $currentuser->firstname : null;
                                $gradetransferdata->exportedbylastname = $currentuser ? $currentuser->lastname : null;
                                break;
                        }
                    }
                }   else    {
                    $gradetransferdata->queuedon =  null;
                    $gradetransferdata->queuedbyid =  null;
                    $gradetransferdata->queuedbyfirstname = null;
                    $gradetransferdata->queuedbylastname = null;

                    $gradetransferdata->exportedon = null;
                    $gradetransferdata->exportedbyid = null;
                    $gradetransferdata->exportedbyfirstname =  null;
                    $gradetransferdata->exportedbylastname = null;

                }

                //grading audit information
                $gradetransferdata->gradingauditenabled         =   $cw->gradingaudit;
                $gradetransferdata->gradingauditcompleted       =   (!empty($gradingaudit)) ?   $gradingaudit->completed : 0;
                $gradetransferdata->moderatedgrade = NULL;
                $gradetransferdata->moderateduser = 0;
                $gradetransferdata->moderateddate = 0;

                //we only record data if the grading audit has been completed
                if (!empty($gradingaudit)  && $gradingaudit->completed == 1) {

                    $moderationrecord = $DB->get_records('coursework_mod_agreements', array('feedbackid' => $finalfeedback->id));

                    if ($coursework->numberofmarkers == 1 && !empty($moderationrecord) )    {

                        $moderationrecord = array_pop($moderationrecord);

                        $asessor    =   $DB->get_record('user',array('id'=>$moderationrecord->moderatorid));
                        $gradetransferdata->gradingauditmoderatedgrade = $moderationrecord->agreement;
                        $gradetransferdata->gradingauditmoderateduser = fullname($asessor);
                        $gradetransferdata->gradingauditmoderateddate = $moderationrecord->timemodified;

                    }

                    //changed so moderation information is only saved if  moderation exists


                }

                array_push($gradetransferdatatosave, $gradetransferdata);
            }
        }
        $courseworks_rs->close();
        unset($transfer_grades);
        $this->_save_grade_transfer_data($gradetransferdatatosave);
        return $result;
    }

    /**
     *
     * @param $sql
     * @param $offset
     * @param int $block
     * @return int
     */
    function process_quiz($sql, $offset, $block = 50000){
        global $DB;
        $field = (class_exists('local_user_info_ext\user_info_ext_data_retrieve'))? new user_info_ext_data_retrieve() : "";
        $result = 0;
        $gradetransferdatatosave = [];
        $last_cmid = $transfer_grades = null;
        $quizes_rs = $DB->get_records_sql($sql . " LIMIT $offset, $block");
        $userids = array_column($quizes_rs, 'studentid');

        if (empty($userids)) {
            $sqlin_users = '-1';
        }
        else {
            $sqlin_users = implode(',', $userids);
        }

        $students = $DB->get_records_sql("SELECT id, username, firstname, lastname FROM {user} WHERE id IN ($sqlin_users)");

        foreach ($quizes_rs as $quiz){
            $result++;

            if ($last_cmid != $quiz->cmid) {
                $transfer_grades = null;
                $last_cmid = $quiz->cmid;

                $sql = "SELECT         id, grade, studentid, assessmentcode
                             FROM           {local_grade_transfer}  lmgt
                             WHERE           modtype = 'quiz'
                             AND             cmid = :cmid";
                $gradetransferrecords_rs = $DB->get_recordset_sql($sql, ['cmid' => $quiz->cmid]);
                foreach ($gradetransferrecords_rs as $record) {
                    $transfer_grades[$record->studentid] = $record;
                }
                $gradetransferrecords_rs->close();
            }
            $gradetransferrecord = $transfer_grades[$quiz->studentid] ?? null;

            $gradetransferdata = new \stdClass();
            $gradetransferdata->courseid = $quiz->courseid;
            $gradetransferdata->categoryid = $quiz->categoryid;
            $gradetransferdata->catname = $quiz->catname;
            $gradetransferdata->catpath = $quiz->catpath;
            $gradetransferdata->catpathname = $quiz->catpathname;
            $gradetransferdata->courseshortname = $quiz->courseshortname;
            $gradetransferdata->coursefullname = $quiz->coursefullname;
            $gradetransferdata->activitytype = 'quiz';
            $gradetransferdata->activityid = $quiz->quizid;
            $gradetransferdata->activityname = $quiz->name;
            $gradetransferdata->visible = $quiz->visible;
            $gradetransferdata->cmid = $quiz->cmid;
            $transferinformation      =   new     \local_grade_transfer\quiz_transfer_information();
            $coursemodule = ['availability' => $quiz->availability];
            $gradetransferdata->assessmentcode =  (!empty($gradetransferrecord))? $gradetransferrecord->assessmentcode
                : $transferinformation->get_student_assessment_code($quiz->studentid,$quiz->courseid, (object) $coursemodule);
            $gradetransferdata->studentid = $quiz->studentid;
            $gradetransferdata->username = (empty($students[$quiz->studentid]) ? '' : $students[$quiz->studentid]->username);
            $gradetransferdata->studentfirstname = (empty($students[$quiz->studentid]) ? '' : $students[$quiz->studentid]->firstname);
            $gradetransferdata->studentlastname = (empty($students[$quiz->studentid]) ? '' : $students[$quiz->studentid]->lastname);
            $gradetransferdata->SPR = ($field)? $field->get_user_field_value($quiz->courseid, $quiz->studentid,'spr'): "";
            $gradetransferdata->originalscaleid = null;
            $gradetransferdata->changedscaleid = null;
            $gradetransferdata->originalgrade = (!empty($gradetransferrecord))  ?   round($gradetransferrecord->grade) : null;
            $gradetransferdata->duedate = $quiz->timeclose;

            //moderationinformation
            $gradetransferdata->gradingauditenabled         =   0;
            $gradetransferdata->gradingauditcompleted       =   0;
            $gradetransferdata->moderatedgrade          =   NULL;
            $gradetransferdata->moderateduser           =   0;
            $gradetransferdata->moderateddate           =   0;

            //get quiz grade
            $sqlparams = array('iteminstance' =>$quiz->quizid, 'userid'=>$quiz->studentid);
            $sql = "SELECT * FROM {grade_items} gi JOIN {grade_grades} gg on gi.id = gg.itemid
                    WHERE itemmodule = 'quiz' AND  gi.iteminstance = :iteminstance and userid = :userid
                    AND finalgrade IS NOT NULL";

            $quizgrade = $DB->get_record_sql($sql, $sqlparams);

            $gradetransferdata->gradedon = $gradetransferdata->gradedbyid = $gradetransferdata->changedgrade =
            $gradetransferdata->changedgradeon = $gradetransferdata->gradechangedbyid = null;

            if ($quizgrade) {
                $gradetransferdata->gradedon = $quizgrade->timemodified; // quiz_grades table oly stores timemodified

                $gradetransferdata->gradedbyid = ($quizgrade->usermodified == $quizgrade->userid) ? null : $quizgrade->usermodified;
                if ($gradetransferdata->gradedbyid) {
                    $gradedby = $this->get_user($gradetransferdata->gradedbyid);
                    $gradetransferdata->gradedbyfirstname = ($gradedby) ? $gradedby->firstname : '';
                    $gradetransferdata->gradedbylastname = ($gradedby) ? $gradedby->lastname : '';
                }
                $gradetransferdata->changedgrade = round($quizgrade->finalgrade);
                $gradetransferdata->changedgradeon = $quizgrade->timemodified;

                $gradetransferdata->gradechangedbyid = ($quizgrade->usermodified == $quizgrade->userid)? null : $quizgrade->usermodified;
                if ($gradetransferdata->gradechangedbyid) {
                    $changedby = $this->get_user($gradetransferdata->gradechangedbyid);
                    $gradetransferdata->changedbyfirstname = ($changedby) ? $changedby->firstname : '';
                    $gradetransferdata->changedbylastname = ($changedby) ? $changedby->lastname : '';
                }
            }


            if (!empty($gradetransferrecord)) {
                $logs = $this->get_transfer_logs_by_action($gradetransferrecord->id, ['queued', 'transferred', 'completed']);
                foreach ($logs as $log) {
                    $currentuser = empty($log->userid) ? null : $this->get_user($log->userid);
                    switch ($log->action) {
                        case 'queued':
                            // get queue log
                            $gradetransferdata->queuedon = (!empty($log)) ? $log->timecreated : null;
                            $gradetransferdata->queuedbyid = (!empty($log)) ? $log->userid : null;
                            $gradetransferdata->queuedbyfirstname = $currentuser ? $currentuser->firstname : null;
                            $gradetransferdata->queuedbylastname = $currentuser ? $currentuser->lastname : null;
                            break;
                        case 'transferred':
                            // get exported/transferred log
                            $gradetransferdata->exportedon = (!empty($log)) ? $log->timecreated : null;
                            $gradetransferdata->exportedbyid = (!empty($log)) ? $log->userid : null;
                            $gradetransferdata->exportedbyfirstname = $currentuser ? $currentuser->firstname : null;
                            $gradetransferdata->exportedbylastname = $currentuser ? $currentuser->lastname : null;
                            break;
                    }
                }
            }   else    {
                $gradetransferdata->queuedon =  null;
                $gradetransferdata->queuedbyid =  null;
                $gradetransferdata->queuedbyfirstname = null;
                $gradetransferdata->queuedbylastname = null;

                $gradetransferdata->exportedon = null;
                $gradetransferdata->exportedbyid = null;
                $gradetransferdata->exportedbyfirstname =  null;
                $gradetransferdata->exportedbylastname = null;

            }

            array_push($gradetransferdatatosave, $gradetransferdata);
        }

        unset($quizes_rs);
        unset($transfer_grades);
        unset($students);
        $this->_save_grade_transfer_data($gradetransferdatatosave);
        return $result;
    }

    /**
     * Get the latest value of the passed action for the transferred grade
     *
     * @param $gradetransid
     * @param $actions
     * @return mixed
     * @throws \dml_exception
     */
    public function get_transfer_logs_by_action($gradetransid, $actions){
        global $DB;

        $sql =  "SELECT * FROM {local_grade_transfer_log}
                 WHERE gradetransid = :gradetransid AND action = :action
                 ORDER BY id DESC LIMIT 1";
        $queued_record = $DB->get_record_sql($sql, ['gradetransid'=>$gradetransid, 'action'=>'queued']);
        $transferred_record = $DB->get_record_sql($sql, ['gradetransid'=>$gradetransid, 'action'=>'transferred']);
        $completed_record = $DB->get_record_sql($sql, ['gradetransid'=>$gradetransid, 'action'=>'completed']);

        $result_records = [];
        if (!empty($queued_record)) $result_records[] = $queued_record;
        if (!empty($transferred_record)) $result_records[] = $transferred_record;
        if (!empty($completed_record)) $result_records[] = $completed_record;

        return $result_records;

    }


    /**
     * @param $userid
     * @return mixed
     */
    public function get_user($userid) {
        if (!isset($this->users[$userid])) {
            global $DB;
            $user = $DB->get_record('user', array('id' => $userid));
            $this->users[$userid] = $user;
        }
        return $this->users[$userid];
    }



    /**
     * @param $gradetransferdatatosave
     * @throws \dml_exception
     */
    private function _save_grade_transfer_data(&$gradetransferdatatosave){
        if (empty($gradetransferdatatosave)) {
            return;
        }
        global $DB;
        $columns = [
            'courseid',
            'categoryid',
            'catname',
            'catpath',
            'catpathname',
            'courseshortname',
            'coursefullname',
            'activitytype',
            'activityid',
            'activityname',
            'visible',
            'cmid',
            'assessmentcode',
            'studentid',
            'username',
            'studentfirstname',
            'studentlastname',
            'SPR',
            'originalscaleid',
            'changedscaleid',
            'originalgrade',
            'gradedon',
            'gradedbyid',
            'gradedbyfirstname',
            'gradedbylastname',
            'changedgrade',
            'changedgradeon',
            'gradechangedbyid',
            'changedbyfirstname',
            'changedbylastname',
            'feedbackreleasedon',
            'queuedon',
            'queuedbyid',
            'queuedbyfirstname',
            'queuedbylastname',
            'exportedon',
            'exportedbyid',
            'exportedbyfirstname',
            'exportedbylastname',
            'duedate',
            'feedbackdate',
            'disableautoindfeedback',
            'gradingauditenabled',
            'gradingauditcompleted',
            'gradingauditmoderatedgrade',
            'gradingauditmoderateduser',
            'gradingauditmoderateddate'];



        $collumnlength = count($columns);
        $startsql = 'INSERT INTO {exe_grade_transfer_data_new} (' . implode(',', $columns) . ') VALUES ';
        $total = count($gradetransferdatatosave);
        $block = 1000;
        $current = 0;

        try {
            $transaction = $DB->start_delegated_transaction();
            while($current < $total) {
                $sql = $startsql;
                $params = [];
                for ($i = 0; $i < $block && $current < $total; $i++, $current++) {
                    $data = $gradetransferdatatosave[$current];
                    $sql .= '(' . implode(',', array_fill(0, $collumnlength, '?')) . '),';
                    foreach ($columns as $column) {
                        $params[] = $data->$column ?? null;
                    }
                }
                //execute
                $DB->execute(rtrim($sql, ','), $params);
            }

            // Assuming the all inserts work, we get to the following line.
            $transaction->allow_commit();
            unset($gradetransferdatatosave);

        } catch(Exception $e) {
            $transaction->rollback($e);
        }
    }


    /**
     * Task for collation of grade transfer grade change data
     *
     * @return int count of records produced
     * @throws \ddl_exception
     * @throws \dml_exception
     */


    function create_gt_grade_change_data(){
        global $DB;

        $mydb = $DB; //get_heavyDB_connection();
        $count = 0;
        $field = (class_exists('local_user_info_ext\user_info_ext_data_retrieve'))? new user_info_ext_data_retrieve() : "";

        $sql = "DROP TABLE IF EXISTS {exe_grade_td_grade_change}";

        $mydb->execute($sql);


        $sql = "CREATE TABLE IF NOT EXISTS  {exe_grade_td_grade_change} (
                   id bigint(10) NOT NULL AUTO_INCREMENT,
                   gradetransid bigint(10) DEFAULT NULL,
                   courseid bigint(20) NOT NULL DEFAULT '0',
                   categoryid bigint(10) DEFAULT NULL,
                   catname varchar(255) DEFAULT NULL,
                   catpath varchar(255) DEFAULT NULL,
                   catpathname longtext DEFAULT NULL,
                   courseshortname varchar(255) NOT NULL DEFAULT '',
                   coursefullname varchar(254) NOT NULL DEFAULT '',
                   activitytype varchar(20) NOT NULL DEFAULT '',
                   activityid bigint(20) NOT NULL DEFAULT '0',
                   activityname varchar(255) NOT NULL DEFAULT '',
                   visible tinyint(4) NOT NULL DEFAULT '0',
                   cmid bigint(10) NOT NULL DEFAULT '0',
                   assessmentcode varchar(254) DEFAULT NULL,
                   studentid bigint(20) NOT NULL DEFAULT '0',
                   username varchar(100) NOT NULL DEFAULT '',
                   SPR varchar(100) NOT NULL DEFAULT '',    
                   studentfirstname varchar(100) NOT NULL DEFAULT '',
                   studentlastname varchar(100) NOT NULL DEFAULT '',
                   originalscaleid bigint(10) DEFAULT NULL,
                   changedscaleid bigint(10) DEFAULT NULL,
                   originalgrade varchar(100) NULL,
                   changedgrade varchar(100) NULL,
                   changedgradeon  bigint(20) DEFAULT NULL,
                   gradedon bigint(20) DEFAULT NULL,
                   gradechangedbyid bigint(20) DEFAULT NULL,
                   changedbyfirstname varchar(100) DEFAULT NULL,
                   changedbylastname varchar(100) DEFAULT NULL,
                  PRIMARY KEY (id))";

        $mydb->execute($sql);



        // get all records from local_grade_transfer table and if grade is not same as in the gradebook, retrieve mare details
        // about the record and save it to 'grade_td_grade_change' table
        // status 6 = transferred

        $sql = "SELECT id , cmid, modtype, activityid, grade, scaleid, assessmentcode, studentid
                FROM {local_grade_transfer}
                WHERE status >= 6";
        $transferred_grades = $mydb->get_records_sql($sql);

        $insertedrecordscount   =   0;


        foreach($transferred_grades as $tg){

            $originalscaleid = null;
            $changedscaleid = null;
            $originalgrade = null;
            $changedgrade = null;
            $gradechangedbyid = null;
            $changedgradeon = null;
            $gradedon = null;

            $valuechanged = false;

            // get current grade
            if($tg->modtype == "coursework") {
                $coursework = \mod_coursework\models\coursework::find($tg->activityid);
                if(!$coursework){ // skip of Coursework doesn't exist
                    continue;
                }
                $activityname = $coursework->name;

                $submission = $coursework->get_user_submission(user::find($tg->studentid)); //this will also work for groups

                if(!$submission){ // skip if submission doesn't exist
                    continue;
                }

                // GRADE
                $originalgrade = $tg->grade;
                $cw_grade = $submission->get_final_feedback()->grade;
                $gradedon = $submission->get_final_feedback()->timecreated;
                if ($originalgrade != $cw_grade) {
                    $changedgrade = $cw_grade;
                    $changedgradeon = $submission->get_final_feedback()->timemodified;
                    $gradechangedbyid = $submission->get_final_feedback()->lasteditedbyuser;

                    $valuechanged = true;
                }

                //SCALE
                if ($coursework->grade < 0) { // we deal with scales
                    $cw_scaleid = abs($coursework->grade);

                    if ($tg->scaleid != $cw_scaleid) {
                        $originalscaleid = $tg->scaleid;
                        $changedscaleid = $cw_scaleid;
                        $originalgrade = $tg->grade;
                        $changedgrade = isset($cw_grade)? $cw_grade : $tg->grade;

                        $valuechanged = true;
                    }
                }

            } else if($tg->modtype == "quiz"){

                // get current grade
                $sqlparams = array('iteminstance' => $tg->activityid, 'userid'=>$tg->studentid);
                $sql = "SELECT * FROM {grade_items} gi
                JOIN {grade_grades} gg on gi.id = gg.itemid
                WHERE itemmodule = 'quiz'
                AND  gi.iteminstance = :iteminstance and userid = :userid
                AND finalgrade IS NOT NULL";

                $quiz = $DB->get_record('quiz', ['id' => $tg->activityid]);
                $activityname = $quiz ? $quiz->name : '';

                $quizgrade = $mydb->get_record_sql($sql, $sqlparams);
                if($quizgrade) {
                    $current_grade = $quizgrade->finalgrade;
                    $gradedon = $quizgrade->timemodified; // quiz_grades table oly stores timemodified


                    if (floor($tg->grade) != floor($current_grade)) {

                        $originalgrade = $tg->grade;
                        $changedgrade = $current_grade;
                        $changedgradeon = $quizgrade->timemodified;
                        $gradechangedbyid = $quizgrade->usermodified;

                        $valuechanged = true;

                    }
                }
            }

            if ($valuechanged == true) { //create an entry for 'grade_td_grade_change'


                $activitydetailssql = "SELECT
                                            c.id AS courseid,
                                            ad.categoryid,
                                            catname,
                                            catpath,
                                            catpathname,
                                            c.shortname AS courseshortname,
                                            c.fullname AS coursefullname,
                                            IF(c.visible = 0, 0, cm.visible) as visible,
                                            cm.id AS cmid
                                       FROM {local_exe_assessment_data} ad 
                                       JOIN {course} c  ON c.id  = ad.courseid
                                       JOIN {course_modules} cm ON cm.id = ad.cmid                 
                                       WHERE ad.cmid = :cmid AND ad.modtype = :activitytype";

                $activitydetails = $DB->get_record_sql($activitydetailssql, array('cmid' => $tg->cmid, 'activitytype' => $tg->modtype));

                $studentdetails = $DB->get_record('user', array('id' => $tg->studentid));
                if ($activitydetails) {
                    $grade_changed = new \stdClass();
                    $grade_changed->gradetransid = $tg->id;
                    $grade_changed->courseid = $activitydetails->courseid;
                    $grade_changed->categoryid = $activitydetails->categoryid;
                    $grade_changed->catname = $activitydetails->catname;
                    $grade_changed->catpath = $activitydetails->catpath;
                    $grade_changed->catpathname = $activitydetails->catpathname;
                    $grade_changed->courseshortname = $activitydetails->courseshortname;
                    $grade_changed->coursefullname = $activitydetails->coursefullname;
                    $grade_changed->activitytype = $tg->modtype;
                    $grade_changed->activityid = $tg->activityid;
                    $grade_changed->activityname = $activityname;
                    $grade_changed->visible = $activitydetails->visible;
                    $grade_changed->cmid = $tg->cmid;
                    $grade_changed->assessmentcode = $tg->assessmentcode;
                    $grade_changed->studentid = $tg->studentid;
                    $grade_changed->username = $studentdetails->username;
                    $grade_changed->SPR = ($field)? $field->get_user_field_value($activitydetails->courseid, $tg->studentid,'spr'): "";
                    $grade_changed->studentfirstname = $studentdetails->firstname;
                    $grade_changed->studentlastname = $studentdetails->lastname;
                    $grade_changed->originalscaleid = $originalscaleid;
                    $grade_changed->changedscaleid = $changedscaleid;
                    $grade_changed->originalgrade = $originalgrade;
                    $grade_changed->gradedon = $gradedon;
                    $grade_changed->changedgrade = $changedgrade;
                    $grade_changed->changedgradeon = $changedgradeon;
                    $grade_changed->gradechangedbyid = $gradechangedbyid;
                    $changedby = false;
                    if ($gradechangedbyid != -1) {
                        $changedby = $mydb->get_record('user', array('id' => $grade_changed->gradechangedbyid));
                    }
                    $grade_changed->changedbyfirstname = NULL;
                    $grade_changed->changedbylastname = NULL;
                    if (isset($gradechangedbyid)) {
                        $grade_changed->changedbyfirstname = ($changedby) ? $changedby->firstname : '';
                        $grade_changed->changedbylastname = ($changedby) ? $changedby->lastname : 'Assessed in GradeMark';
                    }


                    $mydb->insert_record('exe_grade_td_grade_change', $grade_changed);
                    $count++;

                }
            }
        }
        return $count;
    }
}