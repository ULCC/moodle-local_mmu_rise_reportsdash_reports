<?php

namespace local_exeter_reportsdash_reports\task;


use local_exeter_reportsdash_reports;

class grade_transfer_data_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('gradetransferdatatask', 'local_exeter_reportsdash_reports');
    }

    /*
* Run cron.
*/
    public function execute() {

        mtrace(" Started data creation task at: ". date("d-m-Y H:i:s"));



        $coursework_data        =       new     \local_exeter_reportsdash_reports\data_collection;


        $count = $coursework_data->create_grade_transfer_data();


        mtrace(" Finished data creation task for Exeter Grade Transfer data at: ". date("d-m-Y H:i:s"));
        mtrace($count ." records created ");

        \core\task\manager::clear_static_caches();
    }
}