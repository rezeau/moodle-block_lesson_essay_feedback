<?php

class block_lesson_essay_feedback extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_lesson_essay_feedback');
    }

    function get_content() {
        global $USER, $CFG, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $userid = $USER->id;
        $lessons = $DB->get_records_menu('lesson', array('course' => $this->page->course->id), '', 'id, name');
        if(!empty($lessons)) {
            $lessonidhasessays = array();
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
                                    if (!in_array($lessonid,$lessonidhasessays)) {
                                        $lessonidhasessays [] = $lessonid;
                                        $a = new stdClass();
                                        $a->lessonname = $lessons[$lessonid];
                                        $a->nbessaysinlesson = $nbessaysinlesson;
                                        if ($nbessaysinlesson == 1) {
                                            $a->essay = get_string('essay', 'lesson');
                                        } else {
                                            $a->essay = get_string('essays', 'lesson');
                                        }
                                        $this->content->text .= '<li><a title="'.get_string('clicktosee', 'block_lesson_essay_feedback', $a).'" href='
                                            .$CFG->wwwroot.'/blocks/lesson_essay_feedback/view_report.php?id='.$cmid.'&amp;lessonid='.$lessonid.'>'
                                            .$a->lessonname.' ['.$nbessaysinlesson.']'.
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

    function instance_allow_multiple() {
        return FALSE;
    }

    function applicable_formats() {
        return array('all'=>true);
    }
}