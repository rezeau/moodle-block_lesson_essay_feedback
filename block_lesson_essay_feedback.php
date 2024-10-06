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
 * Lesson essay feedback block.
 *
 * This block can be added to a course page or an activity page to enable a student
 * to view the teacher's comments and grade given to a lesson graded essay.
 *
 * @package    block_lesson_essay_feedback
 * @copyright  Joseph Rézeau - moodle@rezeau.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Lesson essay feedback block.
 *
 * This block can be added to a course page or an activity page to enable a student
 * to view the teacher's comments and grade given to a lesson graded essay.
 */
class block_lesson_essay_feedback extends block_base {

    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_lesson_essay_feedback');
    }

    /**
     * Used to generate the content for the block.
     * @return string
     */
    public function get_content() {
        global $USER, $CFG, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $userid = $USER->id;
        $lessons = $DB->get_records_menu('lesson', ['course' => $this->page->course->id], '', 'id, name');
        if (!empty($lessons) ) {
            $lessonidhasessays = [];
            foreach ($lessons as $lessonid => $id) {
                $select = 'lessonid = '.$lessonid.' AND qtype = 10';
                $nbessaysinlesson = $DB->count_records_select('lesson_pages', $select);
                $cm = get_coursemodule_from_instance("lesson", $lessonid);
                $cmid = $cm->id;
                if ($cm->visible) {
                    if ($useranswers = $DB->get_records_select("lesson_attempts", "lessonid = $lessonid AND userid = $userid")) {
                        foreach ($useranswers as $useranswer) {
                            $sql = 'SELECT qtype, contents FROM '.$CFG->prefix.'lesson_pages WHERE id = '.$useranswer->pageid;
                            if ($record = $DB->get_record_sql($sql)) {
                                if ($record->qtype == 10) {
                                    if (!in_array($lessonid, $lessonidhasessays)) {
                                        $lessonidhasessays[] = $lessonid;
                                        $a = new stdClass();
                                        $a->lessonname = $lessons[$lessonid];
                                        $a->nbessaysinlesson = $nbessaysinlesson;
                                        if ($nbessaysinlesson == 1) {
                                            $a->essay = get_string('essay', 'lesson');
                                        } else {
                                            $a->essay = get_string('essays', 'lesson');
                                        }
                                        $this->content->text .= '<li><a title="'.
                                            get_string('clicktosee', 'block_lesson_essay_feedback', $a).'" href='.
                                            $CFG->wwwroot.'/blocks/lesson_essay_feedback/view_report.php?cmid='.$cmid.
                                            '&amp;lessonid='.$lessonid.'>'.
                                            $a->lessonname.' ['.$nbessaysinlesson.']'.
                                            '</a></li>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->content;
    }

    /**
     * Allows the block to be added multiple times to a single page
     * @return boolean
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Core function, specifies where the block can be used.
     * @return array
     */
    public function applicable_formats() {
        return ['all' => true];
    }
}
