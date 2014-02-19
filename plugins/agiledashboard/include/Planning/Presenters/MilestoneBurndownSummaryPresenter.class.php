<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Planning_Presenter_MilestoneBurndownSummaryPresenter extends Planning_Presenter_MilestoneSummaryPresenterAbstract{

    public function has_burndown() {
        return true;
    }

    public function burndown_data() {
        $REST_resource = new Tuleap\AgileDashboard\REST\v1\MilestoneResource();
        return json_encode($REST_resource->getBurndown($this->milestone->getArtifactId()));
    }
}
?>