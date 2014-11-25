@if($ro->relationships && isset($ro->relationships[0]['collection']))
<h2>Related Publications</h2>
<ul>
	@foreach($ro->relationships[0]['collection'] as $col)
	<li><a href="">{{$col['title']}}</a></li>
	@endforeach
</ul>
@endif

@if($ro->relationships && isset($ro->relationships[0]['party_one']))
<h2>Related Researchers</h2>
<ul>
	@foreach($ro->relationships[0]['party_one'] as $col)
	<li><a href="">{{$col['title']}}</a></li>
	@endforeach
</ul>
@endif

@if($ro->relationships && isset($ro->relationships[0]['party_multi']))
<h2>Related Organisations</h2>
<ul>
	@foreach($ro->relationships[0]['party_multi'] as $col)
	<li><a href="">{{$col['title']}}</a></li>
	@endforeach
</ul>
@endif

@if($ro->relationships && isset($ro->relationships[0]['service']))
<h2>Related Services</h2>
<ul>
	@foreach($ro->relationships[0]['service'] as $col)
	<li><a href="">{{$col['title']}}</a></li>
	@endforeach
</ul>
@endif

@if($ro->relationships && isset($ro->relationships[0]['activity']))
<h2>Related Projects</h2>
<ul>
	@foreach($ro->relationships[0]['activity'] as $col)
	<li><a href="">{{$col['title']}}</a></li>
	@endforeach
</ul>
@endif