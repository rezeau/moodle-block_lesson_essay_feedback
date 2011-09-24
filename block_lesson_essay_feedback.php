<?php

class block_lesson_essay_feedback extends block_base {
    function init() {
        $this->title = get_string('blockname', 'block_lesson_essay_feedback');
        $this->version = 2011092400;
    }

    function get_content() {
        global $USER, $CFG, $COURSE;
        $this->course = $COURSE;
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
        $lessons = get_records_select_menu('lesson', 'course='.$this->course->id,'name','id,name');
        
        if(!empty($lessons)) {
        $lessonidhasessays = array();
            foreach ($lessons as $lessonid => $id) {
            //$lessonretake = get_record_select("lesson", "id = $lessonid", null, $fields='retake');
            $sql = 'SELECT COUNT(*) FROM '.$CFG->prefix.'lesson_pages WHERE lessonid = '.$lessonid.' AND qtype = 10';
            $select = 'lessonid = '.$lessonid.' AND qtype = 10';
            $nbessaysinlesson = count_records_select('lesson_pages', $select);
            $cm = get_coursemodule_from_instance("lesson", $lessonid);
            $cmid = $cm->id;
        if ($useranswers = get_records_select("lesson_attempts",  "lessonid = $lessonid AND userid = $userid")) {
        foreach ($useranswers as $useranswer) {
        
        $sql = 'SELECT qtype, contents FROM '.$CFG->prefix.'lesson_pages WHERE id = '.$useranswer->pageid;
                if ($record = get_record_sql($sql)) {
            if ($record->qtype == 10) {
            
                                if (!in_array($lessonid,$lessonidhasessays)) {
                                    $lessonidhasessays [] = $lessonid;
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
        return $this->content;//
    }

    function instance_allow_multiple() {
        return FALSE;
    }

}