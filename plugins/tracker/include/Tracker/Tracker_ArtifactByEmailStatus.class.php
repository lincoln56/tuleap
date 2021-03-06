<?php
/**
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

class Tracker_ArtifactByEmailStatus {

    /** @var TrackerPluginConfig */
    private $tracker_plugin_config;

    public function __construct(TrackerPluginConfig $tracker_plugin_config) {
        $this->tracker_plugin_config = $tracker_plugin_config;
    }

    /**
     * @return bool
     */
    private function isCreationEnabled(Tracker $tracker) {
        return $this->tracker_plugin_config->isInsecureEmailgatewayEnabled() && $tracker->isEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    public function canCreateArtifact(Tracker $tracker) {
        return $this->isCreationEnabled($tracker)
            && $this->isSemanticDefined($tracker)
            && $this->isRequiredFieldsPossible($tracker);
    }

    /**
     * @return bool
     */
    public function canUpdateArtifactInTokenMode(Tracker $tracker) {
        return $this->tracker_plugin_config->isTokenBasedEmailgatewayEnabled() ||
            $this->tracker_plugin_config->isInsecureEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    public function canUpdateArtifactInInsecureMode(Tracker $tracker) {
        return $this->tracker_plugin_config->isInsecureEmailgatewayEnabled() && $tracker->isEmailgatewayEnabled();
    }

    /**
     * @return bool
     */
    private function isSemanticDefined(Tracker $tracker) {
        $title_field       = $tracker->getTitleField();
        $description_field = $tracker->getDescriptionField();
        return $title_field !== null && $description_field !== null;
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsPossible(Tracker $tracker) {
        if ($this->isSemanticDefined($tracker)) {
            $title_field       = $tracker->getTitleField();
            $description_field = $tracker->getDescriptionField();
            return $this->isRequiredFieldsValid($tracker, $title_field, $description_field);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isSemanticConfigured(Tracker $tracker) {
        return !$this->isCreationEnabled($tracker) || $this->isSemanticDefined($tracker);
    }

    /**
     * @return bool
     */
    public function isRequiredFieldsConfigured(Tracker $tracker) {
        return !$this->isCreationEnabled($tracker) || !$this->isSemanticDefined($tracker) || $this->isRequiredFieldsPossible($tracker);
    }

    /**
     * @return bool
     */
    private function isRequiredFieldsValid(
        Tracker $tracker,
        Tracker_FormElement_Field $title_field,
        Tracker_FormElement_Field $description_field
    ) {
        $is_required_fields_valid = true;

        $form_elements = $tracker->getFormElementFields();
        reset($form_elements);
        while ($is_required_fields_valid && list(, $form_element) = each($form_elements)) {
            if ($form_element->isRequired()) {
                $is_required_fields_valid = $form_element->getId() === $title_field->getId() ||
                    $form_element->getId() === $description_field->getId();
            }
        }

        return $is_required_fields_valid;
    }
}
