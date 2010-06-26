<?php
$ADMIN->add('reports', new admin_externalpage('reportfiles', get_string('title', 'report_files'), "$CFG->wwwroot/$CFG->admin/report/files/index.php", 'report/files:view'));
?>
