<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'SwimlineFactory.class.php';
require_once 'OnTop/Config/ColumnFactory.class.php';
require_once 'Board.class.php';
require_once 'FieldsExtractor.class.php';
require_once 'OnTop/Config/MappedFieldProvider.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/CardFields.class.php';

/**
 * Builds Board given artifacts (for swimlines/cards) and a field (for columns)
 */
class Cardwall_BoardFactory {

    /**
     * @return Cardwall_Board
     */
    public function getBoard($field_retriever, $columns, $forests_of_artifacts, $config) {
        $acc_field_provider = new Cardwall_FieldsExtractor($field_retriever);
        $status_fields      = $acc_field_provider->extractAndIndexStatusFields($forests_of_artifacts);
        
        $mapping_collection = $config->_getCardwallMappings($status_fields, $columns);
        $forests_of_cardincell_presenters = $this->transformIntoForestOfCardInCellPresenters($forests_of_artifacts, $field_retriever, $mapping_collection);
        $swimlines                        = $this->getSwimlines($columns, $forests_of_cardincell_presenters, $config, $field_retriever);

        return new Cardwall_Board($swimlines, $columns, $mapping_collection);
        
    }

    private function transformIntoForestOfCardInCellPresenters($forests_of_artifacts, $field_retriever, $mapping_collection) {
        
        $card_presenter_mapper      = new TreeNodeMapper(new Cardwall_CreateCardPresenterCallback(new Tracker_CardFields()));
        $forests_of_card_presenters = $card_presenter_mapper->map($forests_of_artifacts);

        $column_id_mapper           = new TreeNodeMapper(new Cardwall_CardInCellPresenterCallback($field_retriever, $mapping_collection));
        return $column_id_mapper->map($forests_of_card_presenters);
    }

    private function getSwimlines(ArrayAccess $columns, TreeNode $forests_of_cardincell_presenters, $config, $field_provider) {
        $swimline_factory = new Cardwall_SwimlineFactory($config, $field_provider);
        return $swimline_factory->getSwimlines($columns, $forests_of_cardincell_presenters->getChildren());
    }

}
?>
