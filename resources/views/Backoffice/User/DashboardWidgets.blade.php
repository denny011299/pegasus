<?php $page = 'roles-permission'; ?>
@extends('layout.mainlayout')
@section('content')
    <div class="page-wrapper">
        <div class="content container-fluid">
            @component('components.page-header')
                @slot('title')
                    Widget Dashboard Role
                @endslot
            @endcomponent

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Role: {{ $role->role_name }}</h5>
                            <p class="text-muted mb-0">Centang widget dashboard yang boleh dilihat role ini.</p>
                        </div>
                        <a href="{{ url('/role') }}" class="btn btn-secondary btn-sm">Kembali</a>
                    </div>

                    <form method="POST" action="{{ url('/updateDashboardWidgets') }}">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role->role_id }}">

                        <div class="mb-3">
                            <label class="checkboxs mb-0">
                                <input type="checkbox" id="check_all_widgets">
                                <span class="checkmarks"></span>
                            </label>
                            <span class="ms-2">Centang semua widget</span>
                        </div>

                        <div class="row g-2">
                            @foreach ($widgetOptions as $key => $label)
                                <div class="col-md-6">
                                    <div class="border rounded p-2 d-flex align-items-center">
                                        <label class="checkboxs mb-0">
                                            <input type="checkbox" class="widget-checkbox" name="widgets[]" value="{{ $key }}"
                                                {{ in_array($key, $selectedWidgets, true) ? 'checked' : '' }}>
                                            <span class="checkmarks"></span>
                                        </label>
                                        <span class="ms-2">{{ $label }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">Simpan Widget</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        (function () {
            function syncAllCheckbox() {
                var boxes = document.querySelectorAll(".widget-checkbox");
                var checked = document.querySelectorAll(".widget-checkbox:checked");
                var all = document.getElementById("check_all_widgets");
                if (!all) return;
                all.checked = boxes.length > 0 && boxes.length === checked.length;
            }

            document.addEventListener("change", function (e) {
                if (e.target && e.target.id === "check_all_widgets") {
                    var boxes = document.querySelectorAll(".widget-checkbox");
                    for (var i = 0; i < boxes.length; i++) {
                        boxes[i].checked = e.target.checked;
                    }
                }
                if (e.target && (e.target.classList.contains("widget-checkbox") || e.target.id === "check_all_widgets")) {
                    syncAllCheckbox();
                }
            });

            syncAllCheckbox();
        })();
    </script>
@endsection

