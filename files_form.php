<?php
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/datalib.php');

class files_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('text', 'filter', get_string('filter', 'report_files'));
        $coursesel = optional_param('course', 'all', PARAM_RAW);
        $courselist = $this->gen_course_list($coursesel);
        $select = '<div class="fitem"><div class="fitemtitle"><label for="id_course">';
        $select .= get_string('course', 'report_files');
        $select .= '</label></div>';
        $select .= '<div class="felement fselect"><select id="id_course" name="course" size="1">';
        $select .= $courselist;
        $select .= '</select></div>';
        $select .= '</div>';
        $mform->addElement('html', $select);
        $mform->addElement('submit', 'update', get_string('update', 'report_files'));
    }
    
    function gen_course_list($selected)
    {
        global $CFG;
        $selectedtext = ' selected="selected"';
        $option_all = '<option value="all"';
        if ($selected == 'all') {
            $option_all .= $selectedtext;
        }
        $option_all .= '>'.get_string('all').'</option>';
        $courselist = array(0 => $option_all);
        $catcnt = 1;
        
        // get the list of course categories
        $categories = get_categories();
        foreach ($categories as $cat) {
            // for each category, add the <optgroup> to the string array first
            $courselist[$catcnt] = '<optgroup label="'.htmlspecialchars( $cat->name ).'">';
            $courselist[$catcnt] .= '<option value="t'.$cat->id.'"';
            if ($selected == 't'.$cat->id) {
                $courselist[$catcnt] .= $selectedtext;
            }
            $courselist[$catcnt] .= '>'.get_string('allincat', 'report_files').'</option>';
            
            // get the course list in that category
            $courses = get_courses($cat->id, 'c.sortorder ASC', 'c.fullname, c.id');
            $coursecnt = 0;
    
            foreach ($courses as $course) {
                $courselist[$catcnt] .= '<option value="c'.$course->id.'"';
                if ($selected == 'c'.$course->id) {
                    $courselist[$catcnt] .= $selectedtext;
                }
                $courselist[$catcnt] .= '>'.$course->fullname.'</option>';
                $coursecnt++;
            }
    
            // if no courses exist in that category, delete the current string
            if ($coursecnt == 0) {
                unset($courselist[$catcnt]);
            } else {
                $courselist[$catcnt] .= '</optgroup>';
                $catcnt++;
            }
        }
    
        // return the html code with categorized courses
        return implode(' ', $courselist);
    }    
}

?>
