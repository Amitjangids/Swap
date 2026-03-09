@extends('layouts.inner')
@section('content')
<div class="page-heading">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <h2>
                    {!! __('message.'.$pageInfo->title.'') !!}
                </h2>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <div class="col-sm-10 m-auto">
            <div class="main-option-thumb-box">
                <div class="row justify-content-center">
                    <div class="form-cards">
                        {!! $pageInfo->description !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection