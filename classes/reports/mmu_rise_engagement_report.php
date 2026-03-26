<?php
/**
 *
 * @package    local_mmu_rise_reportsdash_reports
 * @copyright  2022 University of London
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_mmu_rise_reportsdash_reports\reports;

require_once("$CFG->dirroot/local/mmu_rise_reportsdash_reports/locallib.php");

class mmu_rise_engagement_report extends \block_reportsdash\report {

    protected $count;

    const DEFAULT_PAGE_SIZE = 50;

    //Instance
    function __construct() {
        parent::__construct(static::column_names(), false, 'local_mmu_rise_reportsdash_reports');
        $this->count = 0;
    }

    protected function setSql($usesort = true) {
        global $DB;
        $filtersql = "";
        $course_mod_completetions_timedate_sql = "";
        $course_completetions_timedate_sql = "";
        $order_by = "";
        $filtersqljoin = "";

        if(!empty($this->filters->enablesubdate) && !empty($this->filters->timedatefromfilter) &&!empty($this->filters->timedatetofilter)) {
            $course_completetions_timedate_sql      =    " AND ccomp.timecompleted >=  :cctimedatefrom AND ccomp.timecompleted < :cctimedateto";
            $course_mod_completetions_timedate_sql  =    " AND cmc.timemodified >=  :cmctimedatefrom AND cmc.timemodified < :cmctimedateto";

            $this->params['cctimedatefrom'] = $this->params['cmctimedatefrom'] = strtotime("midnight",  $this->filters->timedatefromfilter);
            $this->params['cctimedateto'] = $this->params['cmctimedateto'] =   strtotime("tomorrow", strtotime("midnight",  $this->filters->timedatetofilter)) - 1;
        }

        // FILTERS
/*
        if(!empty($this->filters->categoryfilter)) {
            $categorydefault  =   get_string('selectcategory','local_mmu_rise_reportsdash_reports');
            if($this->filters->categoryfilter !== $categorydefault) {

                $cat = \core_course_category::get($this->filters->categoryfilter);

                $filtersqljoin .= " JOIN (SELECT id FROM {course_categories} WHERE path LIKE :catpath) cat
                                            ON sd.categoryid = cat.id";
                $this->params['catpath'] = $cat->path."%";

            }
        } else {
            $filtersqljoin .= " JOIN (SELECT id FROM {course_categories} WHERE id = 0) cat
                                            ON sd.categoryid = cat.id";
        }

        if(!empty($this->filters->assessmenttagfilter)) {

            list($in_sql, $in_params) = $DB->get_in_or_equal($this->filters->assessmenttagfilter, SQL_PARAMS_NAMED, 't', true);
            $this->params = array_merge($this->params, $in_params);

            $filtersql .= " AND coursemoduleid IN (SELECT DISTINCT ti.itemid
                                                   FROM {tag} t
                                                   JOIN {tag_instance} ti ON t.id = ti.tagid
                                                   WHERE t.name {$in_sql})";
        }

        if(!empty($this->filters->duedatefromfilter) &&!empty($this->filters->duedatetofilter)) {
            $dueatesql =    " AND duedate >=  :from AND duedate < :to";

            $this->params['from'] = strtotime("midnight",  $this->filters->duedatefromfilter);
            $this->params['to'] =  strtotime("tomorrow", strtotime("midnight",  $this->filters->duedatetofilter)) - 1;

        } else {
            $dudatesql =    " AND duedate >=  :from AND duedate < :to";

            $this->params['from'] = strtotime("midnight",  time());
            $this->params['to'] =  strtotime("tomorrow", strtotime("midnight",  time())) - 1;


        }



        if(!empty($this->filters->submissionstatusfilter)) {
            $filtersql .=     " AND submissionstatus = :submissionstatus ";


            switch($this->filters->submissionstatusfilter){
                case 'ontime':
                    $submissionstatus_param = 'Submitted on time';
                    break;
                case 'late':
                    $submissionstatus_param = 'Submitted late';
                    break;
                case 'notsubmitted':
                    $submissionstatus_param = 'Not Submitted';
                    break;
            }

            $this->params['submissionstatus'] = $submissionstatus_param;
        }


        if(!empty($this->filters->mitigationfilter)) {
            $filtersql .=     " AND mittype = :mittype ";
            $this->params['mittype'] = $this->filters->mitigationfilter;
        }


        if(!empty($this->filters->coursefullnamefilter)) {
            if(!empty($this->filters->coursefullnamefilter)) {
                $filtersql .=    " AND coursefullname LIKE :coursefullname ";
                $this->params['coursefullname'] = str_replace('*','%',$this->filters->coursefullnamefilter);
            }
        }

        if(!empty($this->filters->studentsfilter)) {
            if(!empty($this->filters->studentsfilter)) {
                $filtersql .=    " AND (firstname LIKE :studentsfilter1 OR lastname LIKE :studentsfilter2 OR SPR LIKE :studentsfilter3)";
                $this->params['studentsfilter1'] = $this->params['studentsfilter2'] = $this->params['studentsfilter3'] = str_replace('*','%',$this->filters->studentsfilter);
            }
        }

        if(!empty($this->filters->activityfiltertype)) {
            switch($this->filters->activityfiltertype) {
                case 1:
                    $filtersql .=    " AND visible = 1 ";break;
                case 2:
                    $filtersql .=    " AND visible = 0 ";break;
                default:
                    break;
            }
        }


        //plagiarismstatusfilter
        if(isset($this->filters->plagiarismstatusfilter)
            && $this->filters->plagiarismstatusfilter != 'all' ){
            $filtersql .=     " AND plagiarismstatus = :plagiarismstatus ";

            $this->params['plagiarismstatus'] = $this->filters->plagiarismstatusfilter;
        }

*/
        if($this->sort()) {
            $order_by = " order by " . $this->sort();
        }


        $sql = "SELECT  c.fullname AS course_name,
                        COUNT(DISTINCT ue.userid) AS student_count,
                        IFNULL(COUNT(DISTINCT ccomp.id),0) course_completions,
                        IFNULL(COUNT(DISTINCT cmc.id),0) course_module_completions
                FROM    {course} c
                JOIN    {enrol} e ON e.courseid = c.id
                JOIN    {user_enrolments} ue ON ue.enrolid = e.id
                JOIN    {user} u ON u.id = ue.userid
                LEFT JOIN {course_completions} ccomp ON c.id = ccomp.course AND (timecompleted IS NOT NULL AND ccomp.timecompleted > 0)  {$course_completetions_timedate_sql}
                LEFT JOIN {course_modules} cm ON c.id = cm.course
                LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid AND cmc.completionstate = 1 {$course_mod_completetions_timedate_sql}                
                WHERE   ue.status = 0
                AND     u.deleted = 0
                AND     u.suspended = 0
                 GROUP BY c.fullname
                {$order_by} ";

        $this->sql = $sql;
    }

    function get_filter_form() {
        global  $PAGE, $CFG;

        $this->filterform = new \local_mmu_rise_reportsdash_reports\forms\mmu_rise_engagement_report_filter_form(null, array('rptname'=>"local_mmu_rise_reportsdash_reports\\reports\\mmu_rise_engagement_report"),
            '', '', array('id' => 'rptdashfilter'));

        return $this->filterform;
    }

    //Extract column names from output fields data, not strictly a webservice thing
    protected static function column_names() {
        $col    =   array();


        $col[]  =   'course_name';
        $col[]  =   'student_count';
        $col[]  =   'course_completions';
        $col[]  =   'course_module_completions';



        return $col;
    }

    protected function getData($usesort = true) {

        // This function run twice. This will fix this issue.
        if ($this->count != 0) return;


        // Add CSS class to 'latethreshold' column to hide it in table view
    //    $this->table->column_class('latethreshold', 'class_latethreshold');
        static::checkInstall();

        $this->setSql($usesort);
        $this->data = $this->mydb->get_recordset_sql($this->sql, $this->params);

        if($this->data->valid())        {
            $data=(array)$this->data;
            $this->records=array_shift($data)->num_rows;
        }  else {
            $this->records=0;
        }


        $this->noSorting(array());

        $this->count++;


        return $this->data;
    }


    function divspan($data,$colour) {
        return "<div style='color: {$colour}'>{$data}</div>";
    }

    protected function preprocessShow($rowdata) {
        global $CFG;

        $colour = "";
       /* $rowdata->catname =  '<div title="'.$rowdata->catpathname.'">'.$rowdata->catname.'</div>';

        if(($rowdata->submissionstatus == 'Not Submitted' && time() > $rowdata->duedate)) { //After deadline and not submitted
            $colour = '#A40C0A';
        }
        if (!empty($rowdata->latetime) && $rowdata->latetime!= 'N/A')    {


            if ($rowdata->latetime > 60*60*24){ // More than 24hours late
                $colour = '#A40C0A';
            }else if ($rowdata->latetime> 60*60 && $rowdata->latetime< 60*60*24-1){ // 1 hr to up to 23 hrs, 59 mins 59 secs late
                $colour = '#7215D8';
            } else if($rowdata->latetime < 60*60-1){ // Up to 59 mins, 59 secs late
                $colour = "#26762C";
            }

            $days = floor($rowdata->latetime / 86400);
            $hours = floor($rowdata->latetime / 3600) % 24;
            $minutes = floor($rowdata->latetime / 60) % 60;
            $seconds = $rowdata->latetime % 60;

            $rowdata->latetime =    "{$days} days $hours hours $minutes minutes $seconds seconds";

        }

        if (!empty($rowdata->accessrestrictions)) {
            $accessrestrictions = get_course_module_availability($rowdata->accessrestrictions);
            $rowdata->accessrestrictions = $accessrestrictions;
        }

        if (!empty($rowdata->timesubmitted)) {
            $rowdata->timesubmitted = date('d/m/Y G:i', $rowdata->timesubmitted);
        }

        if (!empty($rowdata->duedate)) {
            $rowdata->duedate = date('d/m/Y G:i', $rowdata->duedate);
        }

        if (!empty($rowdata->extensiondate)) {
            $rowdata->extensiondate = date('d/m/Y G:i', $rowdata->extensiondate);
        }

        if (!empty($rowdata->mitigationtype) && $rowdata->mitigationtype != 'extension') {
            $rowdata->mitigationtype = $rowdata->mitigationtype ." exemption";
        }

        if($rowdata->selfcert != ''){
            $rowdata->selfcert = $rowdata->selfcert == 1 ? 'Yes' : 'No';
        }

        if($rowdata->plagiarismstatus != ''){
            $rowdata->plagiarismstatus = get_string('plagiarism_'.$rowdata->plagiarismstatus, 'mod_coursework');
        }

        foreach($rowdata as $n => $d)   {
            $rowdata->$n = $this->divspan($d, $colour);
        }
       */

        return $rowdata;
    }

    protected function preprocessExport($rowdata) {
        global $CFG;

        $rowdata->latethreshold = '';
        // swap category name with the full path
        $rowdata->catname = $rowdata->catpathname;
        if(($rowdata->submissionstatus == 'Not Submitted' && time() > $rowdata->duedate)) { //After deadline and not submitted
            $colour = '#A40C0A';
            $rowdata->latethreshold = 'Not submitted';
        }
        if (!empty($rowdata->latetime) && $rowdata->latetime!= 'N/A')    {

            if ($rowdata->latetime > 60*60*24){ // More than 24hours late
                $colour = '#A40C0A';
                $rowdata->latethreshold = 'Over 24hrs late';
            }else if ($rowdata->latetime> 60*60 && $rowdata->latetime< 60*60*24-1){ // 1 hr to up to 23 hrs, 59 mins 59 secs late
                $colour = '#7215D8';
                $rowdata->latethreshold = '1hr-24hrs late';
            } else if($rowdata->latetime < 60*60-1){ // Up to 59 mins, 59 secs late
                $colour = "#26762C";
                $rowdata->latethreshold = 'Up to 1hr late';
            }


            $days = floor($rowdata->latetime / 86400);
            $hours = floor($rowdata->latetime / 3600) % 24;
            $minutes = floor($rowdata->latetime / 60) % 60;
            $seconds = $rowdata->latetime % 60;

            $rowdata->latetime =    "{$days} days $hours hours $minutes minutes $seconds seconds";
        }

        if (!empty($rowdata->accessrestrictions)) {
            $accessrestrictions = get_course_module_availability($rowdata->accessrestrictions);
            $rowdata->accessrestrictions = strip_tags($accessrestrictions);
            if ($_REQUEST['download'] == 'csv' || $_REQUEST['download'] == 'xlsx') {
                $rowdata->accessrestrictions = trim(preg_replace('/\s+/', ' ', $rowdata->accessrestrictions));;
            }
        }

        if (!empty($rowdata->timesubmitted)) {
            $rowdata->timesubmitted = date('d/m/Y G:i', $rowdata->timesubmitted);
        }

        if (!empty($rowdata->duedate)) {
            $rowdata->duedate = date('d/m/Y G:i', $rowdata->duedate);
        }

        if (!empty($rowdata->extensiondate)) {
            $rowdata->extensiondate = date('d/m/Y G:i', $rowdata->extensiondate);
        }

        if (!empty($rowdata->mitigationtype) && $rowdata->mitigationtype != 'extension') {
            $rowdata->mitigationtype = $rowdata->mitigationtype ." exemption";
        }

        if($rowdata->selfcert != ''){
            $rowdata->selfcert = $rowdata->selfcert == 1 ? 'Yes' : 'No';
        }

        if($rowdata->plagiarismstatus != ''){
            $rowdata->plagiarismstatus = get_string('plagiarism_'.$rowdata->plagiarismstatus, 'mod_coursework');
        }
        $downloadtype = required_param('download', PARAM_RAW);
        if ($downloadtype == 'html') {
            foreach ($rowdata as $n => $d) {
                $rowdata->$n = $this->divspan($d, $colour);
            }
        }


        return $rowdata;
    }

    static function get_report_category() {
        return 'exereports';
    }

    function noSorting($columns) {
        foreach($columns as $col) {
            $this->table->no_sorting($col) ;
        }
    }

    protected function setColumnStyles() {
        parent::setColumnStyles();
        $this->table->column_style_all('text-align', 'left');
    }

    static function check_dependency($dependencies) {
        $dependencies = array('mod_coursework');

        return parent::check_dependency($dependencies);
    }


    protected function reportHeader($export=false)    {

        $downloadtype = optional_param('download', 0,PARAM_RAW);


    }


}
