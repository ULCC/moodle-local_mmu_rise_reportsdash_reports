<?php

namespace local_exeter_reportsdash_reports\task;


use local_exeter_reportsdash_reports;

class coursework_settings_amendments_data_task extends \core\task\scheduled_task {


    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('courseworksettingsamendmentsdatatask', 'local_exeter_reportsdash_reports');
    }

    /**
     * Run cron.
     */
    public function execute() {

        mtrace(" Started data creation task at: ". date("d-m-Y H:i:s"));

        $lastcron = $this->get_last_run_time();

        $coursework_data        =       new     \local_exeter_reportsdash_reports\data_collection;

        $coursework_data->insert_coursework_settings_amendments_data($lastcron);

        mtrace(" Finished data creation task for Coursework Settings Amendments Data at: ". date("d-m-Y H:i:s"));
    }
}