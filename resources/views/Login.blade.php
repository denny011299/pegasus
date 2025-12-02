<?php $page = 'signin-3'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="account-content">
        <div class="login-wrapper login-new">
            <div class="container">
                <div class="login-content user-login">
                    <div class="login-logo">
                        <img src="{{ URL::asset('assets/indoraya_logo.png') }}" alt="img">
                        <a href="/login" class="login-logo logo-white">
                            <img src="{{ URL::asset('assets/indoraya_logo.png') }}" alt="">
                        </a>
                    </div>
                    <form action="index">
                        <div class="login-userset">
                            <div class="login-userheading">
                                <h3>Sign In</h3>
                                <h4>Access the Indoraya panel using your username and passcode.</h4>
                            </div>
                            <div class="form-login">
                                <label class="form-label">Username</label>
                                <div class="form-addons">
                                    <input type="text" class="form-control fill" id="username">
                                </div>
                            </div>
                            <div class="form-login">
                                <label>Password</label>
                                <div class="pass-group">
                                    <input type="password" class="pass-input fill" id="password">
                                    <span class="fas toggle-password fa-eye-slash"></span>
                                </div>
                            </div>
                            <div class="form-login">
                                <div class="btn btn-login" id="btn-login">Sign In</div>
                            </div>
                        </div>
                    </form>

                </div>
                <div class="my-4 d-flex justify-content-center align-items-center copyright-text">
                    <p>Copyright &copy; 2025 In. All rights reserved</p>
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
