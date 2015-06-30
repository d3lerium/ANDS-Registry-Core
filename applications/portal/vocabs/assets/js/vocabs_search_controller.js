app.controller('searchCtrl', function($scope, $log, $location, vocabs_factory){
	
	$scope.vocabs = [];
	$scope.filters = {};

	// $log.debug($location.search());
	// The form of filters value for this will be <base_url>+/#!/?<filter>=<value>
	// eg. <base_url>+/#!/?q=fish, #!/?q=fish&subjects=Fish
	$scope.filters = $location.search();

	$scope.search = function() {
		if ($scope.searchRedirect()) {
			window.location = base_url+'#!/?q='+$scope.filters['q'];
		} else {
			$location.path('/').replace();
			window.history.pushState($scope.filters,'ANDS Research Vocabulary', $location.absUrl());
			vocabs_factory.search($scope.filters).then(function(data){
				$log.debug(data);
				$scope.result = data;
				facets = [];
				angular.forEach(data.facet_counts.facet_fields, function(item, index) {
					facets[index] = [];
					for (var i = 0; i < data.facet_counts.facet_fields[index].length ; i+=2) {
						var fa = {
							name: data.facet_counts.facet_fields[index][i],
							value: data.facet_counts.facet_fields[index][i+1]
						}
						facets[index].push(fa);
					}
				});
				$scope.facets = facets;
			});
		}
	}

	$scope.searchRedirect = function() {
		if ($('#search_app').length > 0) {
			return false;
		} else {
			return true;
		}
	}

	if (!$scope.searchRedirect()) {
		$scope.search();
	}

	// Works with ng-debounce="500" defined in the search field, goes into effect every 500ms 
	$scope.$watch('filters.q', function(newv, oldv) {
		if ((newv || newv=='')) {
			$scope.search();
		}
	});

	//Below this line are all the searching directives
	
	$scope.getHighlight = function(id){
		if ($scope.result.highlighting && !$.isEmptyObject($scope.result.highlighting[id])) {
			return $scope.result.highlighting[id];
		} else return false;
	}

	$scope.toggleFilter = function(type, value, execute) {
		if($scope.filters[type]) {
			if($scope.filters[type]==value) {
				$scope.clearFilter(type,value);
			} else {
				if($scope.filters[type].indexOf(value)==-1) {
					$scope.addFilter(type, value);
				} else {
					$scope.clearFilter(type,value);
				}
			}
		} else {
			$scope.addFilter(type, value);
		}
		$scope.filters['p'] = 1;
		if(execute) $scope.search();
	}

	$scope.addFilter = function(type, value) {
		if($scope.filters[type]){
			if(typeof $scope.filters[type]=='string') {
				var old = $scope.filters[type];
				$scope.filters[type] = [];
				$scope.filters[type].push(old);
				$scope.filters[type].push(value);
			} else if(typeof $scope.filters[type]=='object') {
				$scope.filters[type].push(value);
			}
		} else $scope.filters[type] = value;
	}

	$scope.clearFilter = function(type, value, execute) {
		if(typeof $scope.filters[type]!='object') {
			if(type=='q') {
				$scope.query = '';
				search_factory.update('query', '');
				$scope.filters['q'] = '';
			} else if(type=='description' || type=='title' || type=='identifier' || type == 'related_people' || type == 'related_organisations' || type == 'institution' || type == 'researcher') {
				$scope.query = '';
				search_factory.update('query', '');
				delete $scope.filters[type];
				delete $scope.filters['q'];
			}
			delete $scope.filters[type];
		} else if(typeof $scope.filters[type]=='object') {
			var index = $scope.filters[type].indexOf(value);
			$scope.filters[type].splice(index, 1);
		}
		if(execute) $scope.search();
	}

	$scope.isFacet = function(type, value) {
		if($scope.filters[type]) {
			if(typeof $scope.filters[type]=='string' && $scope.filters[type]==value) {
				return true;
			} else if(typeof $scope.filters[type]=='object') {
				if($scope.filters[type].indexOf(value)!=-1) {
					return true;
				} else return false;
			}
			return false;
		}
		return false;
	}

});

app.filter('trustAsHtml', ['$sce', function($sce){
	return function(text){
		var decoded = $('<div/>').html(text).text();
		return $sce.trustAsHtml(decoded);
	}
}])

app.directive('ngDebounce', function($timeout) {
    return {
        restrict: 'A',
        require: 'ngModel',
        priority: 99,
        link: function(scope, elm, attr, ngModelCtrl) {
            if (attr.type === 'radio' || attr.type === 'checkbox') return;
            
            elm.unbind('input');
            
            var debounce;
            elm.bind('input', function() {
                $timeout.cancel(debounce);
                debounce = $timeout( function() {
                    scope.$apply(function() {
                        ngModelCtrl.$setViewValue(elm.val());
                    });
                }, attr.ngDebounce || 1000);
            });
            elm.bind('blur', function() {
                scope.$apply(function() {
                    ngModelCtrl.$setViewValue(elm.val());
                });
            });
        }
                     
    }
});