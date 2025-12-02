<?php $page = 'bank-settings-grid'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        .bank-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            border: 1px solid #092C4C;
            position: relative;
            margin-bottom: 24px;
            color: #092C4C !important;
        }

        .bank-box.active {
            border-color: #28C76F;
        }

        .bank-box .bank-header .bank-name p,
        .bank-box .bank-header .bank-name h6,
        .bank-box .bank-info h6 {
            color: #092C4C !important;
        }
    </style>
    <div class="page-wrapper">
        <div class="content settings-content">
            <div class="page-header settings-pg-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Settings</h4>
                        <h6>Manage your settings on portal</h6>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="settings-wrapper d-flex">
                        @component('Backoffice.Setting.settings-sidebar')
                        @endcomponent
                        <div class="settings-page-wrap w-50">
                            <div class="setting-title">
                                <h4>Bank Account</h4>
                            </div>
                            <div class="page-header bank-settings justify-content-end">
                                <div class="page-btn">
                                    <a href="#" class="btn btn-added btnAdd"><i data-feather="plus-circle"
                                            class="me-2"></i>Add
                                        New Account</a>
                                </div>
                            </div>
                            <div class="row" id="container-bank">

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('custom_js')
    <script src="{{ asset('/Custom_js/Backoffice/Setting/Bank.js') }}?v={{ time() }}"></script>
@endsection
