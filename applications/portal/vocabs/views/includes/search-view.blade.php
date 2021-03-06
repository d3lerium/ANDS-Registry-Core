<section class='section swatch-gray'>
	<div class="container element-short-bottom element-short-top">
		<div class="row">

			<div class="col-md-4 col-lg-3 sidebar search-sidebar">

				<div ng-if="facets.subject_labels">
				<h3>Subject</h3>
					<ul class="list-unstyled">
						<li ng-repeat="facet in facets.subject_labels.slice(0,8)">

							<a href="" ng-click="toggleFilter('subject_labels', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
							<a href="" ng-click="toggleFilter('subject_labels', facet.name, true)" ng-if="isFacet('subject_labels',facet.name)"><i class="fa fa-remove"></i></a>
						</li>

                        <div id="moresubject_labels" style="display:none">
                            <li ng-repeat="facet in facets.subject_labels.slice(8)">
                                <a href="" ng-click="toggleFilter('subject_labels', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
                                <a href="" ng-click="toggleFilter('subject_labels', facet.name, true)" ng-if="isFacet('subject_labels',facet.name)"><i class="fa fa-remove"></i></a>

                            </li>
                        </div>
                        <a href="" ng-click="toggleFacet('subject_labels')" ng-if="facets.subject_labels.length>8" id="linksubjects"  style="display:block">
                          <small>View
                            <span ng-if="isMoreVisible('subject_labels')">Less...</span>
                            <span ng-if="!isMoreVisible('subject_labels')">More...</span>
                          </small>
                        </a>
                    </ul>
				</div>
				<div ng-if="facets.publisher">
				<h3>Publisher</h3>
   					<ul class="list-unstyled">
						<li ng-repeat="facet in facets.publisher.slice(0,8)">
							<a href="" ng-click="toggleFilter('publisher', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
							<a href="" ng-click="toggleFilter('publisher', facet.name, true)" ng-if="isFacet('publisher',facet.name)"><i class="fa fa-remove"></i></a>
						</li>
                        <div id="morepublisher" style="display:none">
                            <li ng-repeat="facet in facets.publisher.slice(8)">
                                <a href="" ng-click="toggleFilter('publisher', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
                                <a href="" ng-click="toggleFilter('publisher', facet.name, true)" ng-if="isFacet('publisher',facet.name)"><i class="fa fa-remove"></i></a>
                            </li>
                        </div>
                        <a href="" ng-click="toggleFacet('publisher')" ng-if="facets.publisher.length>8" id="linkpublisher"  style="display:block">
                          <small>View
                            <span ng-if="isMoreVisible('publisher')">Less...</span>
                            <span ng-if="!isMoreVisible('publisher')">More...</span>
                          </small>
                        </a>
					</ul>
				</div>
				<div ng-if="facets.language">
				<h3>Language</h3>
					<ul class="list-unstyled">
						<li ng-repeat="facet in facets.language.slice(0,8)">
							<a href="" ng-click="toggleFilter('language', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
							<a href="" ng-click="toggleFilter('language', facet.name, true)" ng-if="isFacet('language',facet.name)"><i class="fa fa-remove"></i></a>
						</li>
                        <div id="morelanguage" style="display:none">
                            <li ng-repeat="facet in facets.language.slice(8)">
                                <a href="" ng-click="toggleFilter('language', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
                                <a href="" ng-click="toggleFilter('language', facet.name, true)" ng-if="isFacet('language',facet.name)"><i class="fa fa-remove"></i></a>
                            </li>
                        </div>
                        <a href="" ng-click="toggleFacet('language')" ng-if="facets.language.length>8" id="linklanguage"  style="display:block">
                          <small>View
                            <span ng-if="isMoreVisible('language')">Less...</span>
                            <span ng-if="!isMoreVisible('language')">More...</span>
                          </small>
                        </a>
                    </ul>
				</div>
				<div ng-if="facets.format">
				<h3>Format</h3>
					<ul class="list-unstyled">
						<li ng-repeat="facet in facets.format">
							<a href="" ng-click="toggleFilter('format', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
							<a href="" ng-click="toggleFilter('format', facet.name, true)" ng-if="isFacet('format',facet.name)"><i class="fa fa-remove"></i></a>
						</li>
					</ul>
				</div>
				<div ng-if="facets.access">
				<h3>Access</h3>
					<ul class="list-unstyled">
						<li ng-repeat="facet in facets.access">
							<a href="" ng-click="toggleFilter('access', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
							<a href="" ng-click="toggleFilter('access', facet.name, true)" ng-if="isFacet('access',facet.name)"><i class="fa fa-remove"></i></a>
						</li>
					</ul>
				</div>
				<div ng-if="facets.licence">
				<h3>Licence</h3>
					<ul class="list-unstyled">
						<li ng-repeat="facet in facets.licence">
							<a href="" ng-click="toggleFilter('licence', facet.name, true)">[[ facet.name ]] ([[facet.value]])</a>
							<a href="" ng-click="toggleFilter('licence', facet.name, true)" ng-if="isFacet('licence',facet.name)"><i class="fa fa-remove"></i></a>
						</li>
					</ul>
				</div>
			</div>

			<div class="col-md-8 col-lg-9">

                <ul class="pagi element element-shorter-bottom pull-right" ng-if="result">
                    <li><small>Page [[ page.cur ]] / [[ page.end ]]</small></li>
                    <li ng-if="page.cur!=1"><a href="" ng-click="goto(1)"><span aria-hidden="true">&laquo;</span><span class="sr-only">First</span></a></li>
                    <li ng-repeat="x in page.pages"><a ng-class="{'active':page.cur==x}" href="" ng-click="goto(x)">[[x]]</a></li>
                    <li ng-if="page.cur!=page.end"><a href="" ng-click="goto(page.end)"><span aria-hidden="true">&raquo;</span><span class="sr-only">Last</span></a></li>
                </ul>
                <div class="clearfix"></div>

				<div ng-repeat="doc in result.response.docs" class="animated fadeInLeft vocab-search-result">
                    <span class="label label-default pull-right" ng-if="doc.status=='deprecated'"  style="margin-left:5px">[[ doc.status ]]</span>
                    <a id="widget-link" class="pull-right" href="javascript:showWidget()" ng-if="doc.widgetable" tip="<b>Widgetable</b><br/>This vocabulary can be readily used for resource description or discovery in your system using our vocabulary widget.<br/><a id='widget-link2' href='javascript:showWidget()'>Learn more</a>">
                       <span class="label label-default pull-right"><img class="widget-icon" height="16" width="16"src="{{asset_url('images/cogwheels_white.png', 'core')}}"/> widgetable</span>
                    </a>

                    <h3 class="break"><a href="[[ base_url ]][[ doc.slug ]]">[[ doc.title ]]</a></h3>

                   	<p ng-if="doc.acronym">
						<small>Acronym: [[ doc.acronym ]]</small>
					</p>
					<p ng-if="doc.publisher">
						Publisher: [[ doc.publisher.join(',') ]]
					</p>
					<p ng-if="getHighlight(doc.id)===false">[[ doc.description | limitTo:500 ]]<span ng-if="doc.description.length > 500">...</span></p>
					<div ng-repeat="(index, content) in getHighlight(doc.id)" class="element-shorter-bottom">
	                    <div ng-repeat="c in content track by $index" class="element-shortest-bottom">
	                        <span ng-bind-html="c | trustAsHtml"></span> <span class="muted">(in [[ index | removeSearchTail ]])</span>
	                    </div>
	                </div>
				</div>

                <div class="clearfix"></div>
                <ul class="pagi element element-shorter-top pull-right" ng-if="result">
                    <li><small>Page [[ page.cur ]] / [[ page.end ]]</small></li>
                    <li ng-if="page.cur!=1"><a href="" ng-click="goto(1)"><span aria-hidden="true">&laquo;</span><span class="sr-only">First</span></a></li>
                    <li ng-repeat="x in page.pages"><a ng-class="{'active':page.cur==x}" href="" ng-click="goto(x)">[[x]]</a></li>
                    <li ng-if="page.cur!=page.end"><a href="" ng-click="goto(page.end)"><span aria-hidden="true">&raquo;</span><span class="sr-only">Last</span></a></li>
                </ul>

				<div ng-if="result.response.numFound == 0" class="animated fadeInLeft vocab-search-result">
					Your search did not return any results
				</div>
			</div>

		</div>

	</div>
</section>