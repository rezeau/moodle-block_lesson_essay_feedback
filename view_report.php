<?php
/// by Joseph Rezeau JUNE 2011

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
/*
$PAGE->navbar->add(get_string('graderscomments', 'block_lesson_essay_feedback'));
$PAGE->set_title(format_string($lesson->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('graderscomments', 'block_lesson_essay_feedback').'<br />'.format_string($lesson->name));
*/
$strexportentries = '';
$strlesson = 'the lesson yes';
$navigation = build_navigation($strexportentries, $cm);
    print_header_simple(format_string($lesson->name), "",$navigation,
        "", "", true, update_module_button($cm->id, $course->id, $strlesson),
        navmenu($course, $cm));

    print_heading($strexportentries);

if ($useranswers = get_records_select("lesson_attempts",  "lessonid = $lessonid AND userid = $userid", $sort='pageid ASC')) {
    //function get_record_select($table, $select='', $fields='*') {
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
                        print_box_start('generalbox');						}
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
	                	if ($score = get_record_sql($sql)) {
	                		$maxscore = $score->score;
	                	}                
		
		                // Set the grade
		                //$grades = get_records('lesson_grades', array("lessonid"=>$lesson->id, "userid"=>$userid), 'completed', '*', $useranswer->retry, 1);
		                //$categories = get_records("glossary_categories", "glossaryid", $key ,"name ASC");
	                	$grades = get_records_select('lesson_grades', "lessonid = $lessonid and userid = $userid", 'completed', '*', $useranswer->retry, 1);
	                	//$grades = get_records('lesson_grades', "lessonid", $lesson->id, "userid"=>$userid), 'completed', '*', $useranswer->retry, 1);
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
    print_box_end();
}

    print_footer($course);

?>
