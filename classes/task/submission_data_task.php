<?php

namespace local_exeter_reportsdash_reports\task;


use local_exeter_reportsdash_reports;

class submission_data_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('submissiondatatask', 'local_exeter_reportsdash_reports');
    }

    /*
* Run cron.
*/
    public function execute() {

        mtrace(" Started data creation task at: ". date("d-m-Y H:i:s"));

        $coursework_data        =       new     \local_exeter_reportsdash_reports\data_collection;

        $coursework_data->insert_submission_status_data();
        $coursework_data->insert_activity_data();

        mtrace(" Finished data creation task for Submission data at: ". date("d-m-Y H:i:s"));

    }
}