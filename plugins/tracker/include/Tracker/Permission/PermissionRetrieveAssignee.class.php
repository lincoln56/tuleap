<?php
/**
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

class Tracker_Permission_PermissionRetrieveAssignee {
    /**
     * Retrieve the Id of assignee for a given artifact
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Array
     */
    public function getAssigneeIds(Tracker_Artifact $artifact) {
        $contributor_field = $artifact->getTracker()->getContributorField();
        if ($contributor_field) {
            $assignee = $artifact->getValue($contributor_field);
            if ($assignee) {
                return $assignee->getValue();
            }
        }
        return array();
    }
}
?>