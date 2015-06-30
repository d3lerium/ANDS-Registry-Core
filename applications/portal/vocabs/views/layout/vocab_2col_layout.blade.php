<!DOCTYPE html>
<html lang="en">
@include('includes/header')
<body ng-app="app" ng-controller="searchCtrl">
@include('includes/top-menu')
<?php
$publisher = array();
if(isset($vocab['related_entity'])){
    foreach($vocab['related_entity'] as $related){
        if($related['type']=='party'){
            if(is_array($related['relationship'])){
                foreach($related['relationship'] as $relationship){
                    if($relationship=='publishedBy'){
                        $publisher[]=$related;
                    }
                }
            }elseif($related['relationship']=='publishedBy'){
                $publisher[]=$related;
            }
        }
    }
}

$url = base_url().$vocab['slug'];
$title = $vocab['title'] ;

?>
<div id="content">
    <article>
        <section class="section swatch-gray" style="z-index:1">
            <div class="container">
                <div class="row element-short-top">
                    <div class="col-md-9 view-content" style="padding-right:0">

                        <div class="panel panel-primary swatch-white panel-content">
                            <div class="panel-body">
                                <h1 class="hairline bordered-normal" style="line-height:1.1em"><span itemprop="name">{{ $vocab['title'] }} </span></h1>
                                @if(isset($publisher))
                                @foreach($publisher as $apub)
                                <small>Publisher </small>  <a class="re_preview" related='{{json_encode($apub)}}' v_id="{{ $vocab['id'] }}" sub_type="publisher"> {{$apub['title']}} </a>
                                @endforeach
                                @endif
                                <div class="pull-right">
                                    {{ isset($vocab['creation_date']) ? "Created: ".date("d-m-Y",strtotime($vocab['creation_date'])) : ''}}
                                    <a href="http://www.facebook.com/sharer.php?u={{$url}}"><i class="fa fa-facebook" style="padding-right:4px"></i></a>
                                    <a href="https://twitter.com/share?url={{$url}}&text={{$title}}&hashtags=andsdata"><i class="fa fa-twitter" style="padding-right:4px"></i></a>
                                    <a href="https://plus.google.com/share?url={{$url}}"><i class="fa fa-google" style="padding-right:4px"></i></a>
                                </div>
                            </div>
                        </div>
                        @yield('content')
                    </div>

                    <div class="col-md-3">
                        @yield('sidebar')
                    </div>

                </div>
            </div>
        </section>
    </article>

</div>
@include('includes/footer')
</body>
</html>