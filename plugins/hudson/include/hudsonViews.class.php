<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'www/include/help.php';

class hudsonViews extends Views {

    function hudsonViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }

    function header() {
        $request =& HTTPRequest::instance();
        $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $request->get('group_id'), 'toptab' => 'hudson'));
        echo $this->_getHelp();
        echo '<h2>'.$this->_getTitle().'</h2>';
    }
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_hudson','title');
    }
    function _getHelp($section = '', $questionmark = false) {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        if ($questionmark) {
            $help_label = '[?]';
        } else {
            $help_label = $GLOBALS['Language']->getText('global', 'help');
        }
        return help_button('ci.html'.$section, false, $help_label);
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    // {{{ Views
    function projectOverview() {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user     = UserManager::instance()->getCurrentUser();
        $em       = EventManager::instance();
        $services = array();
        $params   = array('group_id' => $group_id, 'services' => &$services);
        /* $services will contain an array of details of all plugins that will trigger CI builds
           Example of what $services may contain:
            Array(
                [0] => Array(
                        [service] => plugin1
                        [title] => title1
                        [used] => Array(
                                [job_id_11] => true
                                [job_id_12] => true
                            )
                        [add_form]  => "html form"
                        [edit_form] => "html form"
                    )
                [1] => Array(
                        [service] => plugin2
                        [title] => title2
                        [used] => Array(
                                [job_id_21] => true
                                [job_id_22] => true
                            )
                        [add_form]  => "html form"
                        [edit_form] => "html form"
                    )
            )
        */
        $em->processEvent('collect_ci_triggers', $params);
        $this->_display_jobs_table($group_id, $services);
        if ($user->isMember($request->get('group_id'), 'A')) {
            $this->_display_add_job_form($group_id, $services);
        }
        $this->_display_iframe();
        $this->_hide_iframe();
    }

    function job_details() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if ($request->exist('job_id')) {
            $job_id = $request->get('job_id');
            $dar = $job_dao->searchByJobID($job_id);
        } elseif ($request->exist('job')) {
            // used for references (job #MyJob or job #myproject:MyJob)
            $job_name = $request->get('job');
            $dar = $job_dao->searchByJobName($job_name, $group_id);
        }
        if ($dar->valid()) {
            $row = $dar->current();
            $crossref_fact= new CrossReferenceFactory($row['name'], 'hudson_job', $group_id);
            $crossref_fact->fetchDatas();
            if ($crossref_fact->getNbReferences() > 0) {
                echo '<b> '.$GLOBALS['Language']->getText('cross_ref_fact_include','references').'</b>';
                $crossref_fact->DisplayCrossRefs();
            }
            $this->_display_iframe($row['job_url']);
        } else {
            echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
        }
    }

    function last_build() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/lastBuild/');
        } else {
            echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
        }
    }

    function build_number() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        if ($request->exist('build')) {
            $build_id = $request->get('build');
        } else {
            $build_id = $request->get('build_id');
        }
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        if ($request->exist('job_id')) {
            $job_id = $request->get('job_id');
            $dar = $job_dao->searchByJobID($job_id);
        } elseif ($request->exist('job')) {
            // used for references (build #MyJob/175 or job #myproject:MyJob/175 where 175 is the build number required)
            $job_name = $request->get('job');
            $dar = $job_dao->searchByJobName($job_name, $group_id);
        } else {
            // used for references (build #175 where 175 is the build number required)
            // If no job or project is specified, we check if there is only one job associated to the current project and we assume it is this job.
            $dar = $job_dao->searchByGroupID($group_id);
            if ($dar->rowCount() != 1) {
                $dar = null;
            }
        }

        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $crossref_fact= new CrossReferenceFactory($row['name'].'/'.$build_id, 'hudson_build', $group_id);
            $crossref_fact->fetchDatas();
            if ($crossref_fact->getNbReferences() > 0) {
                echo '<b> '.$GLOBALS['Language']->getText('cross_ref_fact_include','references').'</b>';
                $crossref_fact->DisplayCrossRefs();
            }
            $this->_display_iframe($row['job_url'].'/'.$build_id.'/');
        } else {
            echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
        }
    }

    function last_test_result() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/lastBuild/testReport/');
        } else {
            echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
        }
    }

    function test_trend() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();

        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByJobID($job_id);
        if ($dar->valid()) {
            $row = $dar->current();
            $this->_display_iframe($row['job_url'].'/test/?width=800&height=600&failureOnly=false');
        } else {
            echo '<span class="error">'.$GLOBALS['Language']->getText('plugin_hudson','error_object_not_found').'</span>';
        }
    }

    function editJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $user = UserManager::instance()->getCurrentUser();
        if ($user->isMember($group_id, 'A')) {

            $project_manager = ProjectManager::instance();
            $project = $project_manager->getProject($group_id);

            $em      = EventManager::instance();
            $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
            $dar = $job_dao->searchByJobID($job_id);
            if ($dar->valid()) {
                $row = $dar->current();

                echo '<a href="/plugins/hudson/?group_id='.$group_id.'">'.$GLOBALS['Language']->getText('plugin_hudson','back_to_jobs').'</a>';

                echo '<h3>'.$GLOBALS['Language']->getText('plugin_hudson','editjob_title').'</h3>';

                $services = array();
                $params   = array('group_id' => $group_id, 'job_id' => $job_id, 'services' => &$services);
                $em->processEvent('collect_ci_triggers', $params);

                $button = $GLOBALS['Language']->getText('plugin_hudson','form_editjob_button');
                $this->displayForm(
                    $project,
                    $services,
                    'edit',
                    'update',
                    $button,
                    $job_id,
                    $row['job_url'],
                    $row['name'],
                    $row['use_svn_trigger'],
                    $row['use_cvs_trigger'],
                    $row['token'],
                    $row['svn_paths']
                );
            }
        }
    }
    // }}}

    function _display_jobs_table($group_id, $services) {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $user = UserManager::instance()->getCurrentUser();
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByGroupID($group_id);

        if ($dar && $dar->valid()) {

            $project_manager = ProjectManager::instance();
            $project = $project_manager->getProject($group_id);

            echo '<table id="jobs_table" class="table table-bordered table-striped">';
            echo ' <thead><tr>';
            echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_job').'</th>';
            echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_lastsuccess').'</th>';
            echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_lastfailure').'</th>';
            echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_rss').'</th>';
            if ($project->usesSVN()) {
                echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_svn_trigger').'</th>';
            }
            if ($project->usesCVS()) {
                echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_cvs_trigger').'</th>';
            }
            if (!empty($services)) {
                foreach ($services as $service) {
                    echo '  <th>'.$service['title'].'</th>';
                }
            }
            if ($user->isMember($request->get('group_id'), 'A')) {
                echo '  <th>'.$GLOBALS['Language']->getText('plugin_hudson','header_table_actions').'</th>';
            }
            echo ' </tr></thead>';
            echo '<tbody>';
            $cpt = 1;
            while ($dar->valid()) {
                $row = $dar->current();
                $job_id = $row['job_id'];

                echo ' <tr>';

                try {
                    $job = new HudsonJob($row['job_url']);

                    // function toggle_iframe is in script plugins/hudson/www/hudson_tab.js
                    echo '<td>';
                    echo '<img src="'.$job->getStatusIcon().'" alt="'.$job->getStatus().'" title="'.$job->getStatus().'" /> ';
                    echo '<a href="'.$job->getUrl().'" onclick="toggle_iframe(this); return false;" title="'.$GLOBALS['Language']->getText('plugin_hudson','show_job', array($row['name'])).'">'.$row['name'].'</a>';
                    echo '</td>';
                    if ($job->getLastSuccessfulBuildNumber() != '') {
                        echo '  <td><a href="'.$job->getLastSuccessfulBuildUrl().'" onclick="toggle_iframe(this); return false;" title="'.$GLOBALS['Language']->getText('plugin_hudson','show_build', array($job->getLastSuccessfulBuildNumber(), $row['name'])).'">'.$GLOBALS['Language']->getText('plugin_hudson','build').' #'.$job->getLastSuccessfulBuildNumber().'</a></td>';
                    } else {
                        echo '  <td>&nbsp;</td>';
                    }
                    if ($job->getLastFailedBuildNumber() != '') {
                        echo '  <td><a href="'.$job->getLastFailedBuildUrl().'" onclick="toggle_iframe(this); return false;" title="'.$GLOBALS['Language']->getText('plugin_hudson','show_build', array($job->getLastFailedBuildNumber(), $row['name'])).'">'.$GLOBALS['Language']->getText('plugin_hudson','build').' #'.$job->getLastFailedBuildNumber().'</a></td>';
                    } else {
                        echo '  <td>&nbsp;</td>';
                    }
                    echo '  <td align="center"><a href="'.$job->getUrl().'/rssAll" onclick="toggle_iframe(this); return false;"><img src="'.$this->getControler()->getIconsPath().'rss_feed.png" alt="'.$GLOBALS['Language']->getText('plugin_hudson','rss_feed', array($row['name'])).'" title="'.$GLOBALS['Language']->getText('plugin_hudson','rss_feed', array($row['name'])).'"></a></td>';

                    if ($project->usesSVN()) {
                        if ($row['use_svn_trigger'] == 1) {
                            echo '  <td align="center"><img src="'.$this->getControler()->getIconsPath().'server_lightning.png" alt="'.$GLOBALS['Language']->getText('plugin_hudson','alt_svn_trigger').'" title="'.$GLOBALS['Language']->getText('plugin_hudson','alt_svn_trigger').'"></td>';
                        } else {
                            echo '  <td>&nbsp;</td>';
                        }
                    }
                    if ($project->usesCVS()) {
                        if ($row['use_cvs_trigger'] == 1) {
                            echo '  <td align="center"><img src="'.$this->getControler()->getIconsPath().'server_lightning.png" alt="'.$GLOBALS['Language']->getText('plugin_hudson','alt_cvs_trigger').'" title="'.$GLOBALS['Language']->getText('plugin_hudson','alt_cvs_trigger').'"></td>';
                        } else {
                            echo '  <td>&nbsp;</td>';
                        }
                    }
                    if (!empty($services)) {
                        foreach ($services as $service) {
                            if (isset($service['used'][$job_id]) && $service['used'][$job_id] == true) {
                                echo '  <td align="center"><img src="'.$this->getControler()->getIconsPath().'server_lightning.png" alt="'.$service['title'].'" title="'.$service['title'].'"></td>';
                            } else {
                                echo '  <td>&nbsp;</td>';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo '  <td><img src="'.$this->getControler()->getIconsPath().'link_error.png" alt="'.$e->getMessage().'" title="'.$e->getMessage().'" /></td>';
                    $nb_columns = 4;
                    if ($project->usesSVN()) { $nb_columns++; }
                    if ($project->usesCVS()) { $nb_columns++; }
                    foreach ($services as $service) {
                            $nb_columns++;
                    }
                    echo '  <td colspan="'.$nb_columns.'"><span class="error">'.$e->getMessage().'</span></td>';
                }

                if ($user->isMember($request->get('group_id'), 'A')) {
                    echo '  <td>';
                    // edit job
                    echo '   <span class="job_action">';
                    echo '    <a href="?action=edit_job&group_id='.$group_id.'&job_id='.$job_id.'">'.$GLOBALS['HTML']->getimage('ic/edit.png',
                                                            array('alt' => $GLOBALS['Language']->getText('plugin_hudson','edit_job'),
                                                                  'title' => $GLOBALS['Language']->getText('plugin_hudson','edit_job'))).'</a>';
                    echo '   </span>';
                    // delete job
                    echo '   <span class="job_action">';
                    echo '    <a href="?action=delete_job&group_id='.$group_id.'&job_id='.$job_id.'" onclick="return confirm(';
                    echo "'" . $GLOBALS['Language']->getText('plugin_hudson','delete_job_confirmation', array($row['name'], $project->getUnixName())) . "'";
                    echo ');">'.$GLOBALS['HTML']->getimage('ic/cross.png',
                                                            array('alt' => $GLOBALS['Language']->getText('plugin_hudson','delete_job'),
                                                                  'title' => $GLOBALS['Language']->getText('plugin_hudson','delete_job'))).'</a>';
                    echo '   </span>';
                    echo '  </td>';
                }

                echo ' </tr>';

                $dar->next();
                $cpt++;
            }
            echo '</table>';
        } else {
            echo '<p>'.$GLOBALS['Language']->getText('plugin_hudson','no_jobs_linked').'</p>';
        }
    }

    function _display_add_job_form($group_id, $services) {
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($group_id);

        // function toggle_addurlform is in script plugins/hudson/www/hudson_tab.js
        echo '<a href="#" onclick="toggle_addurlform(); return false;">' . $GLOBALS["HTML"]->getimage("ic/add.png") . ' '.$GLOBALS['Language']->getText('plugin_hudson','addjob_title').'</a>';
        echo ' '.$this->_getHelp('hudson-service', true);
        echo '<div id="hudson_add_job">';
        $this->displayForm($project, $services, 'add', 'add', 'Add job', null, null, null, null, null, null, '');
        echo '</div>';
        echo "<script>Element.toggle('hudson_add_job', 'slide');</script>";
    }

    private function displayForm(
        $project,
        $services,
        $add_or_edit,
        $action,
        $button,
        $job_id,
        $job_url,
        $name,
        $use_svn_trigger,
        $use_cvs_trigger,
        $token,
        $svn_paths
    ) {
        echo '  <form class="form-horizontal">
                    <input type="hidden" name="group_id" value="'.$project->getId().'" />
                    <input type="hidden" name="job_id" value="'. $job_id .'" />
                    <input type="hidden" name="action" value="'. $action .'_job" />
                    <div class="control-group">
                        <label class="control-label" for="hudson_job_url">'.$GLOBALS['Language']->getText('plugin_hudson','form_job_url').'</label>
                        <div class="controls">
                            <input id="hudson_job_url" name="hudson_job_url" type="text" size="64" value="'. $job_url .'" />
                            <span class="help help-inline">'. $GLOBALS['Language']->getText('plugin_hudson','form_joburl_example') .'</span>
                        </div>
                    </div>';
        if ($name !== null) {
            echo '  <div class="control-group">
                        <label class="control-label" for="hudson_job_name">'.$GLOBALS['Language']->getText('plugin_hudson','form_job_name').'</label>
                        <div class="controls">
                            <input id="hudson_job_name" name="hudson_job_name" type="text" size="64" value="'. $name .'" />
                            <span class="help help-inline">'. $GLOBALS['Language']->getText('plugin_hudson', 'form_jobname_help', $name) .'</span>
                        </div>
                    </div>';
        }
        if ($project->usesSVN() || $project->usesCVS() || !empty($services)) {
            echo '  <div class="control-group">
                        <label class="control-label" for="hudson_job_url">'.$GLOBALS['Language']->getText('plugin_hudson','form_job_use_trigger').'</label>
                            <div class="controls">';
            if ($project->usesSVN()) {
                $purifier = Codendi_HTMLPurifier::instance();

                $checked = '';
                if ($use_svn_trigger) {
                    $checked = ' checked="checked" ';
                }
                echo '<label class="checkbox">
                        <input id="hudson_use_svn_trigger" name="hudson_use_svn_trigger" type="checkbox" '. $checked .'/>
                        '. $GLOBALS['Language']->getText('plugin_hudson','form_job_scm_svn') .'
                      </label>
                      <div id="hudson_svn_paths">
                        <label for="hudson_svn_paths_textarea">'. $GLOBALS['Language']->getText('plugin_hudson','svn_paths_label') .'</label>
                        <textarea
                          id="hudson_svn_paths_textarea"
                          name="hudson_svn_paths"
                          placeholder="'. $GLOBALS['Language']->getText('plugin_hudson','svn_paths_placeholder') .'"
                        >'. $purifier->purify($svn_paths) .'</textarea>
                        <p class="help">'. $GLOBALS['Language']->getText('plugin_hudson','svn_paths_helper') .'</p>
                      </div>
                    ';
            }
            if ($project->usesCVS()) {
                $checked = '';
                if ($use_cvs_trigger) {
                    $checked = ' checked="checked" ';
                }
                echo '<label class="checkbox">
                        <input id="hudson_use_cvs_trigger" name="hudson_use_cvs_trigger" type="checkbox" '. $checked .'/>
                        '. $GLOBALS['Language']->getText('plugin_hudson','form_job_scm_cvs') .'
                        </label>';
            }
            foreach ($services as $service) {
                echo $service[$add_or_edit .'_form'];
            }
            echo '          <label>
                                '.$GLOBALS['Language']->getText('plugin_hudson','form_job_with_token').'
                                <input id="hudson_trigger_token" name="hudson_trigger_token" type="text" size="32" value="'. $token .'" />
                            </label>
                        </div>
                  </div>';
        }
        echo '    <div class="control-group">
                    <div class="controls">
                        <input type="submit" class="btn btn-primary" value="'. $button .'" />
                    </div>
                  </div>
                </form>';
    }

    function _display_iframe($url = '') {
        echo '<div id="hudson_iframe_div">';
        $GLOBALS['HTML']->iframe($url, array('id' => 'hudson_iframe', 'class' => 'iframe_service', 'width' => '100%', 'height' => '650px'));
        echo '</div>';
    }
    function _hide_iframe() {
        echo "<script>Element.toggle('hudson_iframe_div', 'slide');</script>";
    }
}
