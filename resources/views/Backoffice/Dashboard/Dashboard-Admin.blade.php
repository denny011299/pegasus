@php
    $page = 'index';
    $aksesHome = Session::has('user') ? collect(json_decode(Session::get('user')->role_access)) : collect();
@endphp

@extends('layout.mainlayout')

@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Dashboard
                @endslot
            @endcomponent

            @include('Backoffice.Dashboard.partials.home-dashboard-widgets', ['aksesHome' => $aksesHome])
        </div>
    </div>
@endsection

@section('custom_js')
    <script src="{{ asset('Custom_js/Backoffice/Dashboard/Dashboard-Admin.js') }}?v=19"></script>
@endsection
