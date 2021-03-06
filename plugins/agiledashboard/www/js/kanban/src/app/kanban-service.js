(function () {
    angular
        .module('kanban')
        .service('KanbanService', KanbanService);

    KanbanService.$inject = ['Restangular', '$q', 'SharedPropertiesService'];

    function KanbanService(Restangular, $q, SharedPropertiesService) {
        _.extend(Restangular.configuration.defaultHeaders, {
            'X-Client-UUID': SharedPropertiesService.getUUID()
        });

        return {
            getKanban           : getKanban,
            getBacklog          : getBacklog,
            getColumn           : getColumn,
            getArchive          : getArchive,
            getItems            : getItems,
            collapseColumn      : collapseColumn,
            expandColumn        : expandColumn,
            reorderColumn       : reorderColumn,
            reorderBacklog      : reorderBacklog,
            reorderArchive      : reorderArchive,
            expandBacklog       : expandBacklog,
            collapseBacklog     : collapseBacklog,
            expandArchive       : expandArchive,
            collapseArchive     : collapseArchive,
            moveInBacklog       : moveInBacklog,
            moveInArchive       : moveInArchive,
            moveInColumn        : moveInColumn,
            updateKanbanLabel   : updateKanbanLabel,
            deleteKanban        : deleteKanban,
            addColumn           : addColumn,
            reorderColumns      : reorderColumns,
            removeColumn        : removeColumn,
            editColumn          : editColumn
        };

        function getKanban(id) {
            var data = $q.defer();

            Restangular
                .one('kanban', id)
                .get()
                .then(function (response) {
                    data.resolve(response.data);
                });

            return data.promise;
        }

        function getBacklog(id, limit, offset) {
            var data = $q.defer();

            Restangular
                .one('kanban', id)
                .one('backlog')
                .get({
                    limit: limit,
                    offset: offset
                })
                .then(function (response) {
                    var result = {
                        results : response.data.collection,
                        total   : response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getArchive(id, limit, offset) {
            var data = $q.defer();

            Restangular
                .one('kanban', id)
                .one('archive')
                .get({
                    limit: limit,
                    offset: offset
                })
                .then(function (response) {
                    var result = {
                        results: response.data.collection,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function getItems(id, column_id, limit, offset) {
            var data = $q.defer();

            Restangular
                .one('kanban', id)
                .one('items')
                .get({
                    column_id: column_id,
                    limit: limit,
                    offset: offset
                })
                .then(function (response) {
                    var result = {
                        results: response.data.collection,
                        total: response.headers('X-PAGINATION-SIZE')
                    };

                    data.resolve(result);
                });

            return data.promise;
        }

        function reorderColumn(kanban_id, column_id, dropped_item_id, compared_to) {
            return Restangular.one('kanban', kanban_id)
                .all('items')
                .patch({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
                }, {
                    column_id: column_id
                });
        }

        function reorderBacklog(kanban_id, dropped_item_id, compared_to) {
            return Restangular.one('kanban', kanban_id)
                .all('backlog')
                .patch({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
                });
        }

        function reorderArchive(kanban_id, dropped_item_id, compared_to) {
            return Restangular.one('kanban', kanban_id)
                .all('archive')
                .patch({
                    order: getOrderArgumentsFromComparedTo(dropped_item_id, compared_to)
                });
        }

        function moveInBacklog(kanban_id, dropped_item_id, compared_to) {
            var patch_arguments = {
                add: {
                    ids : [dropped_item_id]
                }
            };
            if (compared_to) {
                patch_arguments['order'] = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
            }

            return Restangular.one('kanban', kanban_id)
                .all('backlog')
                .patch(patch_arguments);
        }

        function moveInArchive(kanban_id, dropped_item_id, compared_to) {
            var patch_arguments = {
                add: {
                    ids : [dropped_item_id]
                }
            };
            if (compared_to) {
                patch_arguments['order'] = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
            }

            return Restangular.one('kanban', kanban_id)
                .all('archive')
                .patch(patch_arguments);
        }

        function moveInColumn(kanban_id, column_id, dropped_item_id, compared_to) {
            var patch_arguments = {
                add: {
                    ids : [dropped_item_id]
                }
            };
            if (compared_to) {
                patch_arguments['order'] = getOrderArgumentsFromComparedTo(dropped_item_id, compared_to);
            }

            return Restangular.one('kanban', kanban_id)
                .all('items')
                .patch(
                    patch_arguments,
                    {
                        column_id: column_id
                    }
                );
        }

        function getOrderArgumentsFromComparedTo(dropped_item_id, compared_to) {
            return {
                ids         : [dropped_item_id],
                direction   : compared_to.direction,
                compared_to : compared_to.item_id
            };
        }

        function updateKanbanLabel(kanban_id, kanban_label) {
            return Restangular.one('kanban', kanban_id).patch({
                label: kanban_label
            });
        }

        function deleteKanban(kanban_id) {
            return Restangular.one('kanban', kanban_id).remove();
        }

        function expandColumn(kanban_id, column_id) {
            return Restangular.one('kanban', kanban_id).patch({
                collapse_column: {
                    column_id: column_id,
                    value: false
                }
            });
        }

        function collapseColumn(kanban_id, column_id) {
            return Restangular.one('kanban', kanban_id).patch({
                collapse_column: {
                    column_id: column_id,
                    value: true
                }
            });
        }

        function expandBacklog(kanban_id) {
            return Restangular.one('kanban', kanban_id).patch({
                collapse_backlog: false
            });
        }

        function collapseBacklog(kanban_id) {
            return Restangular.one('kanban', kanban_id).patch({
                collapse_backlog: true
            });
        }

        function expandArchive(kanban_id) {
            return Restangular.one('kanban', kanban_id).patch({
                collapse_archive: false
            });
        }

        function collapseArchive(kanban_id) {
            return Restangular.one('kanban', kanban_id).patch({
                collapse_archive: true
            });
        }

        function getColumn(column_id) {
            return Restangular.one('kanban_columns', column_id).get();
        }

        function addColumn(kanban_id, column_label) {
            return Restangular
                .one('kanban', kanban_id)
                .post('columns', {
                    label: column_label
                });
        }

        function reorderColumns(kanban_id, sorted_columns_ids) {
            return Restangular
                .one('kanban', kanban_id)
                .all('columns')
                .customPUT(sorted_columns_ids);
        }

        function removeColumn(kanban_id, column_id) {
            return Restangular
                .one('kanban_columns', column_id)
                .remove({
                    kanban_id: kanban_id
                });
        }

        function editColumn(kanban_id, column) {
            return Restangular
                .one('kanban_columns', column.id)
                .patch({
                    label    : column.label,
                    wip_limit: column.limit_input || 0
                }, {
                    kanban_id: kanban_id
                });
        }
    }
})();
