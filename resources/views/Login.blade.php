<?php $page = 'login'; ?>
@extends('layout.mainlayout')
@section('content')
    <style>
        body{
            background-color: #082a58;
        }
    </style>
    <div class="login-wrapper">
        <div class="container">

            <img class="img-fluid logo-dark mb-2 logo-color" src="{{asset('assets/pegasus_banner_small.png') }}" alt="Logo">
            <img class="img-fluid logo-light mb-2" src="{{asset('assets/pegasus_banner_small.png') }}" alt="Logo">
            <div class="loginbox">

                <div class="login-right">
                    <div class="login-right-wrap">
                        <h1>Login</h1>
                        <p class="account-subtitle">Access to our dashboard</p>

                        <form method="post" action="">
                            @csrf
                            <div class="input-block mb-3">
                                <label class="form-control-label">Username</label>
                                <input type="text" class="form-control fill" id="username" name="username">
                                <div class="text-danger pt-2">
                                    @error('0')
                                        {{ $message }}
                                    @enderror
                                    @error('email')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>
                            <div class="input-block mb-3">
                                <label class="form-control-label">Password</label>
                                <div class="pass-group">
                                    <input type="password" class="form-control pass-input fill" id="password" name="password"
                                        value="">
                                    <span class="fa-solid fa-eye-slash toggle-password"></span>
                                    <div class="text-danger pt-2">
                                        @error('0')
                                            {{ $message }}
                                        @enderror
                                        @error('password')
                                            {{ $message }}
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-lg  btn-primary w-100" type="submit" id="btn-login">Login</button>
                          
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="{{ asset('/Custom_js/Backoffice/Login.js') }}?v={{ time() }}"></script>
@endsection

