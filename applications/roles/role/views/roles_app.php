<?php $this->load->view('header'); ?>
<div ng-app="roles_app">
	<div ng-view></div>
</div>

<div id="index_template" class="hide">
	<div class="content-header">
		<h1>Roles Management</h1>
	</div>
	<div id="breadcrumb" style="clear:both;">
		<?php echo anchor(registry_url('auth/dashboard'), '<i class="icon-home"></i> Home'); ?>
		<a href="#/" class="current">Roles Management</a>
	</div>
	<div class="container-fluid">

		<div class="row-fluid">
			<div class="span6">
				<div class="widget-box">
					<div class="widget-title">
						<h5>Roles Management</h5>
					</div>
					<div class="widget-content">
						
						<form action="" class="form-search">
							<input type="text" class="input-medium search-query" placeholder="Filter" ng-model="filter">
							<select name="" id="" ng-model="limit">
								<option value="10">View 10</option>
								<option value="20">View 20</option>
								<option value="50">View 50</option>
							</select>
						</form>
						<hr>
						<ul class="nav nav-tabs nav-stacked">
							<li ng-repeat="r in roles | filter:filter | limitTo:limit" ng-class="{r.role_id:'active'}[role.role_id]">
								<a href="#/view/{{r.role_id}}/{{filter}}">{{r.name}} 
									<br/>
									<span class="label">{{r.role_type_id}}</span>
								</a>
							</li>
						</ul>
						
						<div class="clearfix"></div>
					</div>
				</div>
			</div>

			<div class="span6">
				<div class="widget-box">
					<div class="widget-title">
						<h5>Add Role Relation</h5>
					</div>
					<div class="widget-content">
						<div class="alert alert-info" ng-show="loading">Loading</div>
						<div class="alert" ng-show="!role">Select a Role on the left to start quick adding role relation</div>
						<form action="" ng-show="role" class="form-horizontal">
							<div class="control-group">
								<label for="" class="control-label">Role: </label>
								<div class="controls">
									<div>
										<b>{{role.role.name}}</b>
									</div>
								</div>
							</div>

							<div class="control-group" ng-show="role.users && role.missingUsers">
								<label for="" class="control-label">Add User:</label>
								<div class="controls">
									<select ng-model="add.user" ng-options="c.role_id as c.name for c in role.missingUsers"></select>
									<button class="btn" ng-show="!loading" ng-click="add_relation(role.role.role_id, add.user)"><i class="icon icon-plus"></i> Add Role Relation</button>
								</div>
							</div>

							<div class="control-group">
								<label for="" class="control-label">Add Functional Role:</label>
								<div class="controls">
									<select ng-model="add.functional" ng-options="c.role_id as c.name for c in role.missingRoles.functional"></select>
									<button class="btn" ng-show="!loading" ng-click="add_relation(role.role.role_id, add.functional)"><i class="icon icon-plus"></i> Add Role Relation</button>
								</div>
							</div>

							<div class="control-group">
								<label for="" class="control-label">Add Organisational Role:</label>
								<div class="controls">
									<select ng-model="add.org" ng-options="c.role_id as c.name for c in role.missingRoles.organisational"></select>
									<button class="btn" ng-show="!loading" ng-click="add_relation(role.role.role_id, add.org)"><i class="icon icon-plus"></i> Add Role Relation</button>
								</div>
							</div>

							<div class="control-group">
								<label for="" class="control-label">Add DOI Application Identifier:</label>
								<div class="controls">
									<select ng-model="add.doi" ng-options="c.role_id as c.name for c in role.missing_doi"></select>
									<button class="btn" ng-show="!loading" ng-click="add_relation(role.role.role_id, add.doi)"><i class="icon icon-plus"></i> Add Role Relation</button>
								</div>
							</div>
							
						</form>
					</div>
					<div class="widget-content" ng-show="role.users">
						<h5>Users</h5>
						<ul>
							<li ng-repeat="c in role.users">
								<a href="">{{c.name}}</a> <a href="" ng-click="remove_relation(role.role_id, c.role_id)"><i class="icon icon-remove"></i></a>
								<ul ng-show="c.childs">
									<li ng-repeat="cc in c.childs">
										<a href="#/view/{{cc.role_id}}">{{cc.name}}</a>
										<ul ng-show="cc.childs">
											<li ng-repeat="ccc in cc.childs"><a href="#/view/{{ccc.role_id}}">{{ccc.name}}</a></li>
										</ul>
									</li>
								</ul>
							</li>
						</ul>
					</div>
					<div class="widget-content" ng-show="role.data_sources">
						<h5>Data Sources</h5>
						<ul><li ng-repeat="c in role.data_sources.result"><a href="">{{c.title}}</a></li></ul>
					</div>
					<div class="widget-content" ng-show="role.functional_roles">
						<h5>Functional Roles</h5>
						<ul>
							<li ng-repeat="c in role.functional_roles">
								<a href="#/view/{{c.role_id}}">{{c.name}}</a>
								<a href="" ng-click="remove_relation(role.role.role_id, c.role_id)" tip="Remove this role relation"><i class="icon icon-remove"></i></a>
								<ul ng-show="c.childs">
									<li ng-repeat="cc in c.childs">
										<a href="#/view/{{cc.role_id}}">{{cc.name}}</a>
										<ul ng-show="cc.childs">
											<li ng-repeat="ccc in cc.childs"><a href="#/view/{{ccc.role_id}}">{{ccc.name}}</a></li>
										</ul>
									</li>
								</ul>
							</li>
						</ul>
					</div>
					<div class="widget-content" ng-show="role.org_roles">
						<h5>Organisational Roles</h5>
						<ul>
							<li ng-repeat="c in role.org_roles">
								<a href="#/view/{{c.role_id}}">{{c.name}}</a>
								<a href="" ng-click="remove_relation(role.role.role_id, c.role_id)" tip="Remove this role relation"><i class="icon icon-remove"></i></a>
							</li>
						</ul>
					</div>
					<div class="widget-content" ng-show="role.doi_app_id">
						<h5>DOI Application Identifier</h5>
						<ul>
							<li ng-repeat="c in role.doi_app_id">
								<a href="#/view/{{c.role_id}}">{{c.name}}</a>
								<a href="" ng-click="remove_relation(role.role.role_id, c.role_id)" tip="Remove this role relation"><i class="icon icon-remove"></i></a>
							</li>
						</ul>
					</div>
				</div>

				<div class="widget-box" ng-show="role">
					<div class="widget-title">
						<h5>Edit: {{role.role.name}}</h5>
					</div>
					<div class="widget-content">
						<form class="form-horizontal" id="">
							<div class="control-group">
								<label for="" class="control-label">ID *</label>
								<div class="controls"><input type="text" name="role_id" disabled ng-model="role.role.role_id"></div>
							</div>
							<div class="control-group">
								<label for="" class="control-label">Name *</label>
								<div class="controls"><input type="text" name="name" required ng-model="role.role.name"></div>
							</div>
							<div class="control-group">
								<label for="" class="control-label">Type</label>
								<div class="controls">
									<select name="role_type_id" disabled id="role_type_id" ng-model="role.role.role_type_id">
										<option value="ROLE_USER">User</option>
										<option value="ROLE_ORGANISATIONAL">Organisational</option>
										<option value="ROLE_FUNCTIONAL">Functional</option>
										<option value="ROLE_DOI_APPID">DOI Application Identifier</option>
									</select>
								</div>
							</div>
							<div class="control-group">
								<label for="" class="control-label">Enabled</label>
								<div class="controls"><input type="checkbox" name="enabled" ng-model="role.role.enabled" ng-true-value="1" ng-false-value="0"></div>
							</div>
							<div class="control-group" id="authentication_id" ng-show="role.role.role_type_id=='ROLE_USER'">
								<label for="" class="control-label">Authentication Service</label>
								<div class="controls">
									<select name="authentication_service_id" disabled ng-model="role.role.authentication_service_id">
										<option value="AUTHENTICATION_BUILT_IN">Built In</option>
										<option value="AUTHENTICATION_LDAP">LDAP</option>
										<option value="AUTHENTICATION_SHIBBOLETH">Shibboleth</option>
									</select>
								</div>
							</div>
							<div class="alert alert-danger hide" id="msg"></div>
							<div class="control-group">
								<div class="controls">
									<button type="submit" class="btn btn-primary" ng-click="update()">Update Role</button>
									<hr>
									<button class="btn btn-danger" ng-click="delete()">Delete Role</button>
								</div>
							</div>
						</form>
					</div>
				</div>

				<div class="widget-box">
					<div class="widget-title">
						<h5>New Role</h5>
					</div>
					<div class="widget-content">
						<form class="form-horizontal" id="">
							<div class="control-group">
								<label for="" class="control-label">ID *</label>
								<div class="controls"><input type="text" name="role_id" required ng-model="newrole.role_id"></div>
							</div>
							<div class="control-group">
								<label for="" class="control-label">Name *</label>
								<div class="controls"><input type="text" name="name" required ng-model="newrole.name"></div>
							</div>
							<div class="control-group">
								<label for="" class="control-label">Type</label>
								<div class="controls">
									<select name="role_type_id" id="role_type_id" ng-model="newrole.role_type_id">
										<option value="ROLE_USER">User</option>
										<option value="ROLE_ORGANISATIONAL">Organisational</option>
										<option value="ROLE_FUNCTIONAL">Functional</option>
										<option value="ROLE_DOI_APPID">DOI Application Identifier</option>
									</select>
								</div>
							</div>
							<div class="control-group">
								<label for="" class="control-label">Enabled</label>
								<div class="controls"><input type="checkbox" name="enabled" ng-model="newrole.enabled" ng-true-value="1" ng-false-value="0"></div>
							</div>
							<div class="control-group" id="authentication_id" ng-show="newrole.role_type_id=='ROLE_USER'">
								<label for="" class="control-label">Authentication Service</label>
								<div class="controls">
									<select name="authentication_service_id" ng-model="newrole.authentication_service_id">
										<option value="AUTHENTICATION_BUILT_IN">Built In</option>
										<option value="AUTHENTICATION_LDAP">LDAP</option>
										<option value="AUTHENTICATION_SHIBBOLETH">Shibboleth</option>
									</select>
								</div>
							</div>
							<div class="alert alert-danger hide" id="msg"></div>
							<div class="control-group">
								<div class="controls"><button type="submit" class="btn btn-primary" ng-click="add()">Add Role</button></div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		
	</div>
</div>

<?php $this->load->view('footer'); ?>