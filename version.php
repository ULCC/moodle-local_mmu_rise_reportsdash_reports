<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * local exeter_reportsdash_reports reports version file
 *
 * @copyright 2022 University of London
 * * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;

$plugin->version        =   2026042900; // The (date) version of this plugin
$plugin->requires       =   2022041900;  // Requires this Moodle version - at least 4.0
$plugin->component      =   'local_mmu_rise_reportsdash_reports';  // Full name of the plugin (used for diagnostics)
$plugin->dependencies   =   array(
    'block_reportsdash' => ANY_VERSION
);
