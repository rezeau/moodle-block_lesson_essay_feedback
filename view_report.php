<?php
/// by Joseph Rezeau JUNE - SEPTEMBER 2011

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/lesson/locallib.php');

$cmid = required_param('cmid', PARAM_INT);      // Course Module ID
$lessonid = required_param('lessonid', PARAM_INT);      // lesson ID

$url = new moodle_url('/mod/lesson_essay_feedback/view_report.php', array('id'=>$cmid));

$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('lesson', $cmid)) {
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

$PAGE->navbar->ignore_active();
$PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', array('id' => $course->id)));
$PAGE->navbar->add($lesson->name);
$PAGE->navbar->add(get_string('graderscommentsandscore', 'block_lesson_essay_feedback'));

$PAGE->set_title(format_string($lesson->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('graderscommentsandscore', 'block_lesson_essay_feedback').'<br />'.format_string($lesson->name));

if ($useranswers = $DB->get_records_select("lesson_attempts",  "lessonid = $lessonid AND userid = $userid", null, 'pageid,timeseen ASC')) {
    $lessonretake = $DB->get_record_select("lesson", "id = $lessonid", null, $fields='retake');
    $i = 0; $nbessays = 0; $boxopen = false;

    // get the current user's latest grade date for this lesson
    // to find out if there is currently one attempt pending in an unfinished lesson
    $params = array ("userid" => $USER->id, "lessonid" => $lesson->id);
    if ($rs = $DB->get_record_sql('SELECT MAX(completed) AS lastgraded FROM {lesson_grades} WHERE userid = :userid
            AND lessonid = :lessonid ', $params)) {
            $lastgraded = $rs->lastgraded;
    }

    foreach ($useranswers as $useranswer) {        
        $sql = 'SELECT qtype, id, contents, contentsformat FROM '.$CFG->prefix.'lesson_pages WHERE id = '.$useranswer->pageid;
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
                    echo '<h3>'.get_string('essayprompt', 'block_lesson_essay_feedback', $nbessays).'</h3><blockquote>';
                    $context = get_context_instance(CONTEXT_MODULE, $PAGE->cm->id);
                    $contents = file_rewrite_pluginfile_urls($question->contents, 'pluginfile.php', $context->id, 'mod_lesson', 'page_contents', 
                    $question->id);
                    echo format_text($contents, $question->contentsformat, array('context'=>$context, 'noclean'=>true));
                    echo '</blockquote>';
                }
                if ($lessonretake->retake) {
                    echo '<h4>'.get_string('attempt', 'lesson', $useranswer->retry + 1).'</h4>';
                }
                echo '<h5>'.get_string('yourresponse', 'block_lesson_essay_feedback').'</h5>';               
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
                    $newgrade = $grade->grade;
                    $a = new stdClass();
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
                    echo '<h5>'.get_string('graderscomments', 'block_lesson_essay_feedback').'</h5>';
                    echo '<blockquote>'.$essayinfo->response.'</blockquote>';
                    echo '<p>'.get_string('score', 'block_lesson_essay_feedback', $a).'<br />';
                    echo ''.get_string('newgrade', 'block_lesson_essay_feedback', $newgrade).'</p>';
                } else {
                    if ($useranswer->timeseen > $lastgraded) {
                        echo '<p><b><em>'.get_string('incompletelesson', 'block_lesson_essay_feedback', userdate($useranswer->timeseen)).'</em></b></p>';
                        $link = '<a href="'.$CFG->wwwroot.'/mod/lesson/view.php?id='.$cmid.'">'.format_string($lesson->name,true).'</a>';
                        $link = get_string('finish', 'block_lesson_essay_feedback', $link);
                        echo $link;
                    } else {
                        echo '<p><b><em>'.get_string('defaultessayresponse', 'lesson').'</em></b></p>';
                    }
                }                
            }
        }
        $i++;
        $oldretry = $useranswer->retry;
    }
    echo $OUTPUT->box_end('generalbox');
}
echo $OUTPUT->footer();
?>
