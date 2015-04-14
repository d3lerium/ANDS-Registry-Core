<!DOCTYPE html>
<html lang="en" ng-app="app">
    @include('includes/header')
    <body>
        @include('includes/top-menu')
        <div id="content" ng-controller="searchCtrl">
            @include('includes/hidden-metadata')
            @include('includes/search-section')
        	<article ng-controller="viewController">	
    		    <section class="section swatch-gray" style="z-index:1">
    		    	<div class="container">
    		    		<div class="row element-short-top">
                            <div class="col-md-9 view-content" style="padding-right:0"  itemscope itemtype="http://schema.org/Dataset">
                                <div class="panel panel-primary swatch-white panel-content">
                                    <div class="panel-body">
                                        <div class="container-fluid">
                                            <div class="row">
                                        @if($logo)
                                        <div class="col-xs-12 col-md-2">
                                            <a href="{{base_url('contributors')}}/{{$group_slug}}" title="Record provided by {{$ro->core['group']}}"><img src="{{$logo}}" alt="logo" class="header-logo animated fadeInDown"></a>
                                        </div>
                                        @endif
                                        <div class="col-xs-12 col-md-10">
                                        <h1 class="hairline bordered-normal"><span itemprop="name">{{$ro->core['title']}}</span></h1>
                                        @if(isset($ro->core['alt_title']))
                                            <small>Also known as:
                                                <span>{{implode(', ',$ro->core['alt_title'])}}</span>
                                            </small><br/>
                                        @endif
                                        @if(!$logo)
                                        <a href="{{base_url('contributors')}}/{{$group_slug}}" tip="Record provided by {{$ro->core['group']}}" title="Record provided by {{$ro->core['group']}}"><span itemprop="sourceOrganization">{{$ro->core['group']}}</span></a>
                                        @else
                                        <small itemprop="sourceOrganization">{{$ro->core['group']}}</small>
                                        @endif

                                        @if(is_array($ro->identifiermatch) && sizeof($ro->identifiermatch) > 0)
                                            @if($show_dup_identifier_qtip)
                                            <a href="" qtip="#identifiermatch" qtip_popup="{{sizeof($ro->identifiermatch)}} linked Records"><i class="fa fa-caret-down"></i></a>
                                            @else
                                            <a href="" qtip="#identifiermatch"><i class="fa fa-caret-down"></i></a>
                                            @endif
                                            <div id="identifiermatch" class="hide">
                                                <b>{{sizeof($ro->identifiermatch)}} linked Records:</b>
                                                <ul class="swatch-white">
                                                    @foreach($ro->identifiermatch as $mm)
                                                    <li><a href="{{base_url($mm['slug'].'/'.$mm['registry_object_id'])}}{{$fl}}">{{$mm['title']}} <br/><small>Contributed by {{$mm['group']}}</small></a></li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="clear"></div>
                                     
                                                    @include('registry_object/contents/related-parties')
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel-body" style="padding:0 0 10px 0">
                                        <div class="panel-tools">
                                            <div ng-if="ro.stat">
                                                <span style="padding-right:4px;" tip="This page has been viewed [[ro.stat.viewed]] times.<br/><span style='font-size:0.8em'>Statistics collected since April 2015</span>"><small>Viewed: </small>[[ro.stat.viewed]]</span>
                                            </div>
                                        </div>
                                        <div class="panel-tools">
                                            @include('registry_object/contents/social-sharing')
                                        </div>
                                    </div>
                                </div>

                                <div>

                                   <div class="pull-left swatch-white" style="position:relative;z-index:9999;margin:35px 15px 15px 15px;width:350px;">
                                        @include('registry_object/service_contents/wrap-getdatalicence')
                                    </div>
                                    @yield('content')
                                </div>

                            </div>

                            <div class="col-md-3">

                                @yield('sidebar')
                            </div>


    		    		</div>
    		    	</div>
    		    </section>
        	</article>
            @include('registry_object/contents/citation-modal')
            @include('includes/advanced_search')
            @include('includes/my-rda')
        </div>
        
        @include('includes/footer')
    </body>
</html>