@extends('layouts/left-sidebar-fw')

@section('content')
    @include('includes/search-header')
<article class="post post-showinfo os-animation animated fadeInUp" ng-repeat="doc in result.response.docs">
    <header class="post-head">
        <h2 class="post-title"> <a href="{{base_url()}}[[doc.slug]]/[[doc.id]]">[[doc.title]]</a> </h2>
        <small> by <a href="">2 comments</a> </small>
        <!-- <span class="post-icon"> <i class="fa fa-picture-o"></i> </span> -->
    </header>
    <div class="post-body">
        <!-- [[doc.hl]] -->
        <div ng-repeat="x in doc.hl">
            <p ng-repeat="b in x" data-ng-bind-html="b"></p>
        </div>
    	<p data-ng-bind-html="doc.description | trustAsHtml" ng-show="!doc.hl"></p>
    </div>
</article>

@stop

@section('sidebar')



<div class="sidebar-widget widget_search">
	<h3 class="sidebar-header">Refine search result</h3>
	<form ng-submit="addKeyWord(extra_keywords)">
		<div class="input-group">
            <input type="text" value="" name="s" class="form-control" placeholder="Add more keywords" ng-model="extra_keywords">
            <span class="input-group-btn">
	            <button class="btn" type="submit" value="Search">
	                <i class="fa fa-search"></i> Go
	            </button>
	        </span>
        </div>
	</form>
    <ul class="list-unstyled">
        <li ng-repeat="filter in allfilters">
            <button class="btn btn-link btn-xs" ng-click="toggleFilter(filter.name,filter.value)">[[filter.value]] <i class="fa fa-remove"></i></button>
        </li>   
    </ul>
</div>

@foreach ($facets as $facet)
	@include('registry_object/facet/'.$facet)
@endforeach

@stop