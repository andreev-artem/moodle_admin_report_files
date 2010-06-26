<?php

    define("MAX_TIME_DIFF", 2);
    define("FILES_PER_PAGE", 30);
    
    function find_new_filename($file)
    {
        if (! $basedir = make_upload_directory("$file->courseid")) {
            return false;
        }
        $originaldir = dirname(substr($file->path, strlen($basedir)));
        $originalext = strrchr($file->path, '.');
        // Find a file in the course directory recursively that matches file data specified
        $dirlist = array();
        $addedroot = false;
        if ($originaldir != "/") {
            $dirlist[] = $originaldir . "/";
        } else {
            $dirlist[] = "/";
            $addedroot = true;
        }
        $result = "";
        $samedir = false;
        while (!empty($dirlist)) {
            $curdir = array_shift($dirlist);
            if (!file_exists($basedir.$curdir)) {
                continue;
            }
            
            $dirhandle = opendir($basedir.$curdir);
            while (false !== ($curfile = readdir($dirhandle))) {
                if ($curfile == "." || $curfile == ".." || $curfile == "backupdata" || $curfile == "moddata") {
                    continue;
                }

                if (is_dir($basedir.$curdir.$curfile) && $curdir.$curfile != $originaldir) {
                    $dirlist[] = $curdir.$curfile."/";
                } else {
                    $fullname = $basedir.$curdir.$curfile;
                    $timediff = $file->uploadtime - filemtime($fullname);
                    if ($timediff >= 0 && $timediff <= MAX_TIME_DIFF && strrchr($curfile, '.') == $originalext) {
                        $result = $fullname;
                        if (($curdir == "/" && $originaldir == "/") || $curdir == $originaldir . "/") {
                            $samedir = true;
                        }
                        break;
                    }
                }
            }
            closedir($dirhandle);
            
            if(!empty($result)) {
                return array($result, $samedir);
            }
            
            if (empty($dirlist) && $addedroot == false) {
                $dirlist[] = "/";
                $addedroot = true;
            }
        }

        return false;
    }

    require_once('../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->libdir.'/filelib.php');
    require_once('files_form.php');
    
    $strfilter = optional_param('filter', "*.swf", PARAM_RAW);
    $course = optional_param('course', 'all', PARAM_RAW);
    
    admin_externalpage_setup('reportfiles');
    admin_externalpage_print_header();

    $mform = new files_form();
    $mform->set_data(array( 'filter' => $strfilter, 'course' => $course ));
    
    $tablecolumns = array('path', 'course', 'size', 'uploadtime', 'author');
    $tableheaders = array(get_string('path', 'report_files'), get_string('course'), get_string('size'),
                          get_string('uploadtime', 'report_files'), get_string('author', 'report_files'));
    $baseurl = $CFG->wwwroot.'/admin/report/files/index.php?filter='. $strfilter. '&course='. $course;
    
    $table = new flexible_table('uploaded-files');

    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($baseurl);

    $table->sortable(true, 'uploadtime', SORT_DESC);
    $table->no_sorting('size');
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'uploaded-files');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('width', '95%');

    $table->setup();
    
    $SQL = "SELECT l.id, l.info as path, c.id as courseid, c.fullname as course, 
            max(l.time) as uploadtime, l.userid as userid, CONCAT(u.firstname, ' ', u.lastname) as author
            FROM {$CFG->prefix}log as l
            INNER JOIN {$CFG->prefix}user as u ON l.userid = u.id
            INNER JOIN {$CFG->prefix}course as c ON l.course = c.id
    		WHERE l.action = 'upload' ";

	if ($course != "all") {
        $id = substr($course, 1);
        if ($course[0] == 'c') {
            $SQL .= "AND l.course = $id ";
        } else if ($course[0] == 't') {
            $catcourses = get_courses($id, 'c.sortorder ASC', 'c.fullname, c.id');
            $courseids = array();
            foreach ($catcourses as $catcourse) {
                $courseids[] = $catcourse->id;
            }
            $courseids = implode(',', $courseids);
            $SQL .= "AND l.course IN ($courseids) ";
        }
    }
	$SQL .= "GROUP BY path ORDER BY " . $table->get_sql_sort();
    $files = get_records_sql($SQL);
    $table->pagesize(FILES_PER_PAGE, count($files));
    
    if (!empty($files)) {
        $counter = 0;
        $pagestart = $table->get_page_start();
        $basedirlen = strlen($CFG->dataroot);
        $patterns = explode(';', strtolower($strfilter));
        foreach ($files as $file) {
            $matched = false;
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, strtolower($file->path))) {
                    $matched = true;
                    break;
                }
            }
            if ($matched == false) {
                continue;
            }        
            $counter++;
            if($counter - 1 < $pagestart || $counter > $pagestart + FILES_PER_PAGE) {
                continue;
            }
            $row = array();
            $moved = false;
            $samedir = false;
            $filesize = false;
            if (file_exists($file->path)) {
                $filesize = filesize($file->path);
            }
            if ($filesize === false) {
                $moved = true;
                $newname = find_new_filename($file);
                if ($newname !== false) {
                    $file->path = $newname[0];
                    $samedir = $newname[1];
                    $filesize = filesize($file->path); 
                }
            }
            $filename = substr($file->path, $basedirlen);
            $filelink = '';
            if ($filesize !== false) {
                $filelink = '<a href="' . get_file_url($filename) . '">' . $filename . '</a>';
                if ($moved) {
                    if ($samedir) {
                        $filelink .= ' ' . get_string('renamed', 'report_files');
                    } else {
                        $filelink .= ' ' . get_string('moved', 'report_files');
                    }
                }
            } else {
                $filelink = $filename . ' ' . get_string('deleted', 'report_files');
            }
            $row[] = $filelink;
            $row[] = '<a href="'.$CFG->wwwroot.'/course/view.php?id='. $file->courseid. '">'. $file->course. '</a>';
            if ($filesize == false) {
                $row[] = '-';
            } else {
                $row[] = display_size($filesize);
            }
            $row[] = userdate($file->uploadtime);
            $row[] = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$file->userid.'&amp;course='. $file->courseid. '">'.$file->author.'</a>';
            $table->add_data($row);
        }
        $table->pagesize(FILES_PER_PAGE, $counter);
    }
    
    print_heading(get_string('title', 'report_files'));

    $mform->display();
    $table->print_html();

    admin_externalpage_print_footer();
?>
