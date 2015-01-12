<?php
	$order = array('brief', 'full');
?>
<div id="descriptions" style="clear:both">
@if($ro->descriptions)

	@foreach($order as $o)
		@foreach($ro->descriptions as $desc)
			@if($desc['type']==$o)
				<small>{{$desc['type']}}</small>
				<p>{{$desc['description']}}</p>
			@endif
		@endforeach
	@endforeach
	
	@foreach($ro->descriptions as $desc)
		@if(!in_array($desc['type'], $order))
			<small>{{$desc['type']}}</small>
			<p>{{$desc['description']}}</p>
		@endif
	@endforeach

@endif
</div>