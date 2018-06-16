@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div style="text-align: right;" class="panel-heading">پیام</div>

                    <div class="panel-body" style="text-align: center;direction: rtl">
                        {{$message}}
                        <br><br>
                        <div>
                            <a class="btn btn-success" href="{{env('FRONT_URL')}}">{{'هدایت به سایت'}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
