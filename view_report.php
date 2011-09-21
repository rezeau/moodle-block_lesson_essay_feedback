<?php
/// by Joseph Rezeau JUNE 2011; SEPTEMBER 2011

require_once("../../config.php");

$id = required_param('id', PARAM_INT);      // Course Module ID
$lessonid = required_param('lessonid', PARAM_INT);      // lesson ID

if (! $cm = get_coursemodule_from_id('lesson', $id)) {
    error("Course Module ID was incorrect");
}
if (! $course = get_record("course", "id", $cm->course)) {
    error("Course is misconfigured");
}
if (! $lesson = get_record("lesson", "id", $cm->instance)) {
    error("Course module is incorrect");
} 
require_login($course->id, false, $cm);

global $USER, $CFG;
$userid = $USER->id;
$strexportentries = '';
$strlesson = 'the lesson yes';
$navigation = build_navigation($strexportentries, $cm);
print_header_simple(format_string($lesson->name), "",$navigation,
        "", "", true, update_module_button($cm->id, $course->id, $strlesson),
        navmenu($course, $cm));

print_heading($strexportentries);

if ($useranswers = get_records_select("lesson_attempts",  "lessonid = $lessonid AND userid = $userid", $sort='pageid ASC')) {
    $lessonretake = get_record_select("lesson", "id = $lessonid", $fields='retake');
    $i = 0; $nbessays = 0; $oldretry = 0; $boxopen = false;
    foreach ($useranswers as $useranswer) {
        if ($oldretry > 0 && $lessonretake->retake && $oldretry == $useranswer->retry) {
            // to skip potential essay attempts made and recorded but then  student quitted lesson before completion
            // there must be a more elegant solution!
        } else {
            $sql = 'SELECT qtype, contents FROM '.$CFG->prefix.'lesson_pages WHERE id = '.$useranswer->pageid;
            if ($question = get_record_sql($sql)) {                
                if ($question->qtype == 10) {
                    $essayinfo = unserialize ($useranswer->useranswer); 

                    if ($useranswer->retry == 0) {
                        if ($boxopen) {
                            print_box_start('generalboxcontent');
                        }
                        //$boxopen = true;
                        $nbessays ++;
                        echo get_string('essayprompt', 'block_lesson_essay_feedback', $nbessays).
                            '<div class="generalbox generalboxcontent">'.$question->contents.'</div>';
                    }

                    if ($lessonretake->retake) {
                        echo '<strong>'.get_string('attempt', 'lesson', $useranswer->retry + 1).'</strong>';
                    }

                    $message  = get_string('yourresponse', 'block_lesson_essay_feedback');
                    echo '<div class="generalbox generalboxcontent"><h5>'.$message.'</h5>';
                    echo('<blockquote>'.stripslashes_safe($essayinfo->answer).'</blockquote></div>');
                    
                    if ($essayinfo->graded) {
                        $sql = 'SELECT score FROM '.$CFG->prefix.'lesson_answers WHERE pageid = '.$useranswer->pageid;
                        if ($score = get_record_sql($sql)) {
                            $maxscore = $score->score;
                        }                
        
                        // Set the grade
                        $grades = get_records_select('lesson_grades', "lessonid = $lessonid and userid = $userid", 'completed', '*', $useranswer->retry, 1);
                        $grade  = current($grades);
                        $newgrade = $grade->grade;
        
                        // Set the points
                        if ($lesson->custom) {
                            $a->earned = $essayinfo->score;
                            $a->outof  = $maxscore;
                        } else {
                            $a->earned = $essayinfo->score;
                            $a->outof  = 1;
                        }
        
                        // Set rest of the message values
                        $comment  = $essayinfo->response;
                        //$message  = get_string('feedbackmessage', 'block_lesson_essay_feedback', $a);
                        
                        echo '<div class="generalbox generalboxcontent">';
                        echo '<h5>'.get_string('graderscomments', 'block_lesson_essay_feedback').'</h5>';
                        echo '<blockquote>'.$essayinfo->response.'</blockquote>';
                        echo '<p>'.get_string('score', 'block_lesson_essay_feedback', $a).'<br />';
                        echo ''.get_string('newgrade', 'block_lesson_essay_feedback', $newgrade).'</p>';
                        
                        echo '</div>';
                    } else {
                        echo '<h5><b><em>'.get_string('defaultessayresponse', 'lesson').'</em></b></h5>';
                    }                
                }
            }
            $i++;
            $oldretry = $useranswer->retry;
        }
    }
}
print_footer($course);
?>
