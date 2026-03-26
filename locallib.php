<?php

require_once($CFG->dirroot.'/tag/classes/tag.php');
/*
function get_course_module_availability($cmid) {
    global $DB;

    $cm = $DB->get_record('course_modules', array('id' => $cmid), 'id, course');

    if(!empty($cm)) {

        $modinfo = get_fast_modinfo($cm->course);

        $cms = $modinfo->get_cms();

        if (empty($cms) || empty($cms[$cm->id])) return '';

        $cminfo = $modinfo->get_cm($cm->id);

        $availability = new \core_availability\info_module($cminfo);
        $fullinfo = $availability->get_full_information();

        if (!empty($fullinfo)) {
            $fullinfo = \core_availability\info::format_info($fullinfo, $cm->course);
            return $fullinfo;
        }
    } else {
        return get_string('nolongerexist', 'local_exeter_reportsdash_reports');
    }

    return '';
}

function get_course_module_tags($cmid) {
    $tags = core_tag_tag::get_item_tags_array('core', 'course_modules', $cmid);

    return (!empty($tags)) ? array_values($tags) : '';
}

/**
 * Get expected number of submissions in the specified activity
 *
 * @return int
 * @throws dml_exception
 */
/*

function get_expected_submissions_count($assessmenttype, $assessmentid){
    global $COURSE, $USER, $DB;

    if ($assessmenttype == 'coursework') {

        $count = get_student_count_in_coursework($assessmentid);
    } else{
        $count = get_student_count_in_quiz($assessmentid);
    }


return $count;

}

/**
 *  Get actual number of submissions in the specified activity
 *
 * @param $assessmenttype
 * @param $assessmentid
 * @return int
 * @throws dml_exception
 */
/*
function get_submissions_made_count($assessmenttype, $assessmentid){
    global $DB;

    if ($assessmenttype == 'coursework'){
        $count =  $DB->count_records('coursework_submissions', array('courseworkid'=>$assessmentid));
    } else{
        // count just one attempt
        $sql = "SELECT count(distinct(userid)) 
                FROM {quiz_attempts}
                WHERE quiz =:quizid";
        $count = $DB->count_records_sql($sql, array('quizid'=>$assessmentid));
    }

    return $count;



}

/**
 * Function to get the number of expected submissions in coursework
 *
 * @param $activityid
 * @return int
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
/*
function get_student_count_in_quiz($activityid){
    global  $DB;


    $quiz = $DB->get_record('quiz', array('id' => $activityid));
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
    $context = \context_module::instance($cm->id);

    $raw_users = get_enrolled_users($context, 'mod/quiz:attempt');
    $modinfo = get_fast_modinfo($quiz->course);

    // filter students who are restricted from the coursework
    $cmobject = $modinfo->get_cm($cm->id);

    $info = new \core_availability\info_module($cmobject);
    $raw_users = $info->filter_user_list($raw_users);
    $expected_number = 0;
    $cur_course_context = context_course::instance($quiz->course);

    foreach ($raw_users as $raw_user) {
        $current_role = current(get_user_roles($cur_course_context, $raw_user->id));

        if ($current_role == false) {
            continue;
        }

        if ($current_role->shortname == 'student') {
            $expected_number ++;
        }
    }

    return $expected_number;
}


/**
 * Function to get the number of expected submissions in coursework
 *
 * @param $assessmentid
 * @return int
 */
/*
function get_student_count_in_coursework($assessmentid){
    global $DB;


    $coursework =  new \mod_coursework\models\coursework($assessmentid);
    $all_students = $coursework->get_allocatables();
    $expected_number = count($all_students);

    foreach ($all_students as $student) {
        // remove students with permanent or tenmporary extension as they are not expected to submitt
        if ($coursework->user_mitigation_exists('permanent', $student->id) || $coursework->user_mitigation_exists('temporary', $student->id)) {
            $expected_number--;
        }
    }

    return $expected_number;
}

/**
 * Function to swap category path with the actual names
 *
 * @param $path
 * @return string
 * @throws moodle_exception
 */
/*
function dc_get_cat_pathnames($path){
    global $DB;

    // get categories id from the path
    $path = ltrim($path, '/');
    $categories = explode('/', $path);

    $pathnames = '';
    foreach ($categories as $catid) {
        $category = $DB->get_record('course_categories', array('id'=>$catid));
        $pathnames .= $category->name.'/';
    }
    $pathnames = rtrim($pathnames, '/');
    return $pathnames;

}
*/