<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use AgileDashboard_PermissionsManager;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use AgileDashboard_KanbanColumnDao;
use AgileDashboard_KanbanColumnManager;
use AgileDashboard_KanbanColumnNotFoundException;
use AgileDashboard_UserNotAdminException;
use AgileDashboard_KanbanColumnNotRemovableException;
use AgileDashboardStatisticsAggregator;
use TrackerFactory;
use UserManager;
use PFUser;
use AgileDashboard_KanbanUserPreferences;
use AgileDashboard_KanbanActionsChecker;
use Tracker_FormElementFactory;
use Tracker_FormElement_Field_List_Bind_Static_ValueDao;

class KanbanColumnsResource {

    const MAX_LIMIT = 100;

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var AgileDashboard_KankanColumnFactory */
    private $kanban_column_factory;

    /** @var AgileDashboard_KanbanColumnManager */
    private $kanban_column_manager;

    /** @var AgileDashboardStatisticsAggregator */
    private $statistics_aggregator;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct() {
        $this->tracker_factory = TrackerFactory::instance();

        $this->kanban_factory = new AgileDashboard_KanbanFactory(
            $this->tracker_factory,
            new AgileDashboard_KanbanDao()
        );

        $kanban_column_dao           = new AgileDashboard_KanbanColumnDao();
        $permissions_manager         = new AgileDashboard_PermissionsManager();
        $this->kanban_column_factory = new AgileDashboard_KanbanColumnFactory(
            $kanban_column_dao,
            new AgileDashboard_KanbanUserPreferences()
        );
        $this->kanban_column_manager = new AgileDashboard_KanbanColumnManager(
            $kanban_column_dao,
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            new AgileDashboard_KanbanActionsChecker(
                $this->tracker_factory,
                $permissions_manager,
                Tracker_FormElementFactory::instance()
            )
        );

        $this->statistics_aggregator = new AgileDashboardStatisticsAggregator();
    }

    /**
     * @url OPTIONS
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     */
    public function options() {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Update column
     *
     * Change column properties
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}
     *
     * @param int                             $id        Id of the column
     * @param int                             $kanban_id Id of the Kanban {@from query}
     * @param KanbanColumnPATCHRepresentation $column    The kanban column {@from body} {@type Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnPATCHRepresentation}
     *
     * @throws 401
     * @throws 404
     */
    protected function patch($id, $kanban_id, KanbanColumnPATCHRepresentation $updated_column_properties) {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $kanban_id);

        try {
            $column = $this->kanban_column_factory->getColumnForAKanban($kanban, $id, $current_user);

            if (isset($updated_column_properties->wip_limit) && ! $this->kanban_column_manager->updateWipLimit($current_user, $kanban, $column, $updated_column_properties->wip_limit)) {
                throw new RestException(500);
            }

            if (isset($updated_column_properties->label) && ! $this->kanban_column_manager->updateLabel($current_user, $kanban, $column, $updated_column_properties->label)) {
                throw new RestException(500);
            }

        } catch (AgileDashboard_KanbanColumnNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (AgileDashboard_UserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch (AgileDashboard_SemanticStatusNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        $this->statistics_aggregator->addWIPModificationHit(
            $this->getProjectIdForKanban($kanban)
        );
    }

    /**
     * Delete column
     *
     * Delete a column from its Kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url DELETE {id}
     *
     * @param int $id           Id of the column
     * @param int $kanban_id    Id of the Kanban {@from query}
     *
     * @throws 401
     * @throws 404
     */
    protected function delete($id, $kanban_id) {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $kanban_id);

        try {
            $column = $this->kanban_column_factory->getColumnForAKanban($kanban, $id, $current_user);
            if (! $this->kanban_column_manager->deleteColumn($current_user, $kanban, $column)) {
                throw new RestException(500);
            }

        } catch (AgileDashboard_KanbanColumnNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (AgileDashboard_UserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch (AgileDashboard_SemanticStatusNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (AgileDashboard_KanbanColumnNotRemovableException $exception) {
            throw new RestException(409, $exception->getMessage());
        }
    }

    /** @return AgileDashboard_Kanban */
    private function getKanban(PFUser $user, $id) {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $id);
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        return $kanban;
    }

    private function getCurrentUser() {
        $user = UserManager::instance()->getCurrentUser();

        return $user;
    }

    /**
     * @return int
     */
    private function getProjectIdForKanban(AgileDashboard_Kanban $kanban) {
        return $this->tracker_factory->getTrackerById($kanban->getTrackerId())->getGroupId();
    }
}
