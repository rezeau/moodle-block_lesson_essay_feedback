<?php
/// by Joseph Rezeau JUNE 2011

require_once("../../config.php");

$id = required_param('id', PARAM_INT);      // Course Module ID
$lessonid = required_param('lessonid', PARAM_INT);      // lesson ID

$url = new moodle_url('/mod/lesson_essay_feedback/view_report.php', array('id'=>$id));

$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('lesson', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $lesson = $DB->get_record("lesson", array("id"=>$cm->instance))) {
    print_error('invalidid', 'lesson');
}

require_login($course->id, false, $cm);

global $USER, $CFG, $DB;
$userid = $USER->id;

$PAGE->navbar->add(get_string('graderscomments', 'block_lesson_essay_feedback'));
$PAGE->set_title(format_string($lesson->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('graderscomments', 'block_lesson_essay_feedback').'<br />'.format_string($lesson->name));

if ($useranswers = $DB->get_records_select("lesson_attempts",  "lessonid = $lessonid AND userid = $userid", null, 'pageid,timeseen ASC')) {
    $lessonretake = $DB->get_record_select("lesson", "id = $lessonid", null, $fields='retake');
    $i = 0; $nbessays = 0; $oldretry = 0; $boxopen = false;
    foreach ($useranswers as $useranswer) {
    	if ($oldretry > 0 && $lessonretake->retake && $oldretry == $useranswer->retry) {
    		// to skip potential essay attempts made and recorded but then  student quitted lesson before completion
    		// there must be a more elegant solution!
    	} else {
	    	$sql = 'SELECT qtype, contents FROM '.$CFG->prefix.'lesson_pages WHERE id = '.$useranswer->pageid;
	        if ($question = $DB->get_record_sql($sql)) {
	            if ($question->qtype == 10) {
	                $essayinfo = unserialize ($useranswer->useranswer); 

	                if ($useranswer->retry == 0) {
						if ($boxopen) {
							echo $OUTPUT->box_end('generalbox');
						}
	                    echo $OUTPUT->box_start('generalbox');
	                    $boxopen = true;
	                    $nbessays ++;
						echo '<h3>'.get_string('essayprompt', 'block_lesson_essay_feedback', $nbessays).'</h3><blockquote>'.$question->contents.'</blockquote>';
	                }

	                if ($lessonretake->retake) {
		                echo '<h4>'.get_string('attempt', 'lesson', $useranswer->retry + 1).'</h4>';
	                }

	                $message  = get_string('yourresponse', 'block_lesson_essay_feedback');
	                echo $message;
	                // display student's answer exactly as it was typed in the HTML editor, including smileys, images, etc.
	                echo('<blockquote>'.format_text($essayinfo->answer, FORMAT_HTML).'</blockquote>');
	                
	                if ($essayinfo->graded) {
	                	$sql = 'SELECT score FROM '.$CFG->prefix.'lesson_answers WHERE pageid = '.$useranswer->pageid;
	                	if ($score = $DB->get_record_sql($sql)) {
	                		$maxscore = $score->score;
	                	}                
		
		                // Set the grade
		                $grades = $DB->get_records('lesson_grades', array("lessonid"=>$lesson->id, "userid"=>$userid), 'completed', '*', $useranswer->retry, 1);
		                $grade  = current($grades);
		                $a->newgrade = $grade->grade;
		
		                // Set the points
		                if ($lesson->custom) {
		                    $a->earned = $essayinfo->score;
		                    $a->outof  = $maxscore;
		                } else {
		                    $a->earned = $essayinfo->score;
		                    $a->outof  = 1;
		                }
		
		                // Set rest of the message values
		                $a->comment  = $essayinfo->response;
		                $message  = get_string('feedbackmessage', 'block_lesson_essay_feedback', $a);
		                echo $message;
	                } else {
	                    echo '<p><b><em>'.get_string('defaultessayresponse', 'lesson').'</em></b></p>';
	                }                
	            }
	        }
	        $i++;
	        $oldretry = $useranswer->retry;
	    }
    }
    echo $OUTPUT->box_end('generalbox');
}
echo $OUTPUT->footer();
?>
