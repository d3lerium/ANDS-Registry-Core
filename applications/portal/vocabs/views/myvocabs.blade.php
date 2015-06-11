@extends('layout/vocab_layout')
@section('content')
<article>
	<section class="section swatch-gray">
		<div class="container element-short-top">
			<div class="row">
				<div class="col-md-8">
					<div class="panel swatch-white">
						<div class="panel-heading">My Vocabs</div>
						<div class="panel-body">
							<a href="{{ portal_url('vocabs/add') }}" class="btn btn-block btn-primary"><i class="fa fa-plus"></i> Add a new Vocabulary</a>
							<hr>
							@if(sizeof($owned_vocabs) == 0)
								You don't own any vocabulary, start by adding a new one
							@else
								<h4>Published Vocabularies</h4>
								<table class="table">
									<thead>
										<tr><th>Vocabulary</th><th>Status</th><th>Action</th></tr>
									</thead>
									<tbody>
										@foreach($owned_vocabs as $vocab)
											@if($vocab['status']=='published')
											<tr>
												<td><a href="{{ portal_url('vocabs/edit/'.$vocab['slug']) }}">{{ $vocab['title'] }}</a></td>
												<td>{{titleCase($vocab['status'])}}</td>
												<td>
													<div class="btn-group">
														<a href="{{ portal_url($vocab['slug']) }}" class="btn btn-primary"><i class="fa fa-search"></i> View</a>
														<a href="{{ portal_url('vocabs/edit/'.$vocab['slug']) }}" class="btn btn-primary"><i class="fa fa-edit"></i> Edit</a>
														<a href="" class="btn btn-primary"><i class="fa fa-trash"></i></a>
													</div>
												</td>
											</tr>
											@endif
										@endforeach
									</tbody>
								</table>

								<h4>Drafts</h4>
								<table class="table">
									<thead>
										<tr><th>Vocabulary</th><th>Status</th><th>Action</th></tr>
									</thead>
									<tbody>
										@foreach($owned_vocabs as $vocab)
											@if($vocab['status']=='draft')
											<tr>
												<td><a href="{{ portal_url('vocabs/edit/'.$vocab['slug']) }}">{{ $vocab['title'] }}</a></td>
												<td>{{titleCase($vocab['status'])}}</td>
												<td>
													<div class="btn-group">
														<a href="{{ portal_url('vocabs/edit/'.$vocab['slug']) }}" class="btn btn-primary"><i class="fa fa-edit"></i> Edit</a>
														<a href="" class="btn btn-primary"><i class="fa fa-trash"></i></a>
													</div>
												</td>
											</tr>
											@endif
										@endforeach
									</tbody>
								</table>
                            <h4>Requested</h4>

                            <table class="table">
                                <thead>
                                <tr><th>Requested</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                @foreach($owned_vocabs as $vocab)
                                @if($vocab['status']=='requested')
                                <tr>
                                    <td><a href="{{ portal_url('vocabs/edit/'.$vocab['slug']) }}">{{ $vocab['title'] }}</a></td>
                                    <td>{{titleCase($vocab['status'])}}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="" class="btn btn-primary"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                                </tbody>
                            </table>
								
							@endif
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="panel swatch-white">
						<div class="panel-heading">Profile</div>
						<div class="panel-body">
							<h3>{{ $this->user->name() }}</h3>
							<a href="{{ portal_url('vocabs/logout') }}" class="btn btn-danger">Logout</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</article>
@stop