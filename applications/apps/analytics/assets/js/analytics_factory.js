(function(){
    'use strict';
    angular
        .module('analytic_app')
        .factory('analyticFactory', analyticFactory);

    function analyticFactory($http, $log) {
        return {
            summary: getSummaryData,
            getGroups: getGroups,
            getEvents: getEvents,
            getOrg: getOrg,
            getStat: getStat,
            allTimeStats: function(filters){
                var ff = {};
                angular.copy(filters, ff);
                delete ff['period'];
                return getSummaryData(ff);
            }
        };

        function getOrg(id) {
            var params = id ? '?role_id='+id : '';
            return $http.get(apps_url+'analytics/getOrg/'+params)
                    .then(returnRaw)
                    .catch(handleError);
        }

        function getStat(type, filters) {
            return $http.post(apps_url+'analytics/getStat/'+type, {filters:filters})
                    .then(returnRaw)
                    .catch(handleError);
        }

        function getEvents(filters) {
            return $http.post(apps_url+'analytics/getEvents', {filters:filters})
                    .then(returnRaw)
                    .catch(handleError);
        }

        function getSummaryData(filters) {
            return $http.post(apps_url+'analytics/summary', {filters:filters})
                    .then(returnRaw)
                    .catch(handleError);
        }

        function returnRaw(response) {
            return response.data;
        }

        function getGroups() {
            var groups = [];
            return $http.get(base_url+'registry_object/getGroupSuggestor')
                    .then(function(response){
                        angular.forEach(response.data, function(obj){
                            groups.push(obj.value);
                        });
                        return groups;
                    }).catch(handleError);
        }

        function handleError(error) {
            $log.error(error);
        }
    }
})();