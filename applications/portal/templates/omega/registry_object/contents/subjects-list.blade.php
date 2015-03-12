@if($ro->subjects)
<div class="swatch-white">
	<div class="panel panel-primary element-no-top element-short-bottom panel-content">
		<div class="panel-heading">
	        <a href="">Subjects</a>
	    </div>
		<div class="panel-body swatch-white">
			<?php 
				$subjects = $ro->subjects;
				uasort($subjects, 'subjectSortResolved');
			?>
			@foreach($subjects as $col)
                @if(isset($col['resolved']))
                    @if($col['type']=='anzsrc-for')
                        <a href="{{base_url().'search/#!/anzsrc-for='.$col['subject']}}" itemprop="about keywords">{{$col['resolved']}}</a> |
                    @elseif($col['type']=='anzsrc-seo')
                        <a href="{{base_url().'search/#!/anzsrc-seo='.$col['subject']}}" itemprop="about keywords">{{$col['resolved']}}</a> |
                    @else
                        <a href="{{base_url().'search/#!/subject_value_resolved='.$col['resolved']}}" itemprop="about keywords">{{$col['resolved']}}</a> |
                    @endif
                @else
                <a href="{{base_url().'search/#!/subject_value='.$col['subject']}}" itemprop="about keywords">{{$col['subject']}}</a> |
                @endif
			@endforeach
		</div>

        @include('registry_object/contents/tags')
	</div>
</div>
@else
    @if($ro->core['class']=='collection')
    <div class="swatch-white">
        <div class="panel panel-primary element-no-top element-short-bottom panel-content">
            <div class="panel-heading">
                <a href="">Tags</a>
            </div>

            @include('registry_object/contents/tags')
        </div>
    </div>
    @endif
@endif
