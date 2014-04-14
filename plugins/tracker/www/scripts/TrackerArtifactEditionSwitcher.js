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

var tuleap     = tuleap || { };
tuleap.tracker = tuleap.tracker || { };

(function($) {

tuleap.tracker.editionSwitcher = function() {

    var init = function() {
        if ($("#artifact_informations").size() > 0) {
            bindClickOnEditableFields();
            bindSubmissionBarToFollowups();
            hideSubmissionBar();
        }
    };

    var bindClickOnEditableFields = function() {
        $(".tracker_artifact_field").each(bindField);
    };

    var bindField = function (index, element) {

        if (! fieldIsEditable(element)) {
            return;
        }

        setClickableCursor(element);
        bindEditionSwitch(element);
    };

    var bindEditionSwitch = function (element) {
        $(element).on('click', function() {
            toggleField(element);
        });
    };

    var toggleField = function (element) {
        removeReadOnlyElements(element);
        removeUnwrappedText(element);
        $(element).find('.tracker_hidden_edition_field').show();
        $(element).off('click');
        toggleDependencyIfAny(element);
        toggleSubmissionBar();
    };

    var toggleDependencyIfAny = function (element) {
        if (! codendi.tracker.rules_definitions) {
            return;
        }

        var field_id = $(element).find('.tracker_hidden_edition_field').attr('data-field-id');

        $(codendi.tracker.rules_definitions).each( function() {
            if (this.source_field == field_id) {
                var target_field = getTargetField(this.target_field);
                if (target_field) {
                    toggleField(target_field);
                }
            }
        });
    };

    var getTargetField = function(target_field_id) {
        var field = $(".tracker_artifact_field .tracker_hidden_edition_field[data-field-id="+target_field_id+"]");

        if (field) {
            return $(field).parent(".tracker_artifact_field");
        }
    };

    var removeReadOnlyElements = function (element) {
        $(element).children(":not(.tracker_formelement_label, .tracker_hidden_edition_field, #display-tracker-form-element-artifactlink-reverse, #tracker-form-element-artifactlink-reverse)").remove();
    };

    var removeUnwrappedText = function (element) {
        $(element).contents().filter(function(){ return this.nodeType == 3; }).remove();
    };

    var setClickableCursor = function (element) {
        $(element).css('cursor', 'pointer');
    };

    var fieldIsEditable = function(element) {
        return $(".tracker_hidden_edition_field", element).size() > 0;
    };

    var bindSubmissionBarToFollowups = function () {
        $('#tracker_followup_comment_new').bind('keyup', toggleSubmissionBar);
    };

    var toggleSubmissionBar = function () {
        if (submissionBarIsAlreadyActive()) {
            removeSubmissionBarIfNeeded();
        }

        displaySubmissionBarIfNeeded();
    };

    var displaySubmissionBarIfNeeded = function () {
        if (somethingIsEdited()) {
            $('.artifact-submit-button').slideDown();
        }
    };

    var removeSubmissionBarIfNeeded = function () {
        if (somethingIsEdited()) {
            return;
        }
        $('.artifact-submit-button').slideUp();
    };

    var somethingIsEdited = function () {
        return ! nothingIsEdited();
    };

    var nothingIsEdited = function () {
        return followUpIsEmpty() && noFieldIsSwitchedToEdit();
    };

    var noFieldIsSwitchedToEdit = function () {
        if ($('.tracker_hidden_edition_field:visible').size() > 0) {
            return false;
        }

        return true;
    };

    var followUpIsEmpty = function () {
        return ! $.trim($("#tracker_followup_comment_new").val());
    };

    var submissionBarIsAlreadyActive = function () {
        return $('.artifact-submit-button:visible').size() > 0;
    };

    var hideSubmissionBar = function () {
        $('.artifact-submit-button').hide();
    };

    return {
        init: init
    };
};

$(document).ready(function() {
    var edition_switcher = new tuleap.tracker.editionSwitcher();
    edition_switcher.init();
});

})(jQuery);