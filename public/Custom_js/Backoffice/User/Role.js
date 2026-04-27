    var mode=1;
    var table;
    var selectedRoleForWidgets = null;
    $(document).ready(function(){
        inisialisasi();
        refreshRole();
    });
    
    $(document).on('click','.btnAdd',function(){
        mode=1;
        $('#add_role .modal-title').html("Tambah Peran");
        $('#add_role input').val("");
        $('.is-invalid').removeClass('is-invalid');
        $('#add_role').modal("show");
    });
    
    function inisialisasi() {
        table = $('#tableRole').DataTable({
            bFilter: true,
            sDom: 'fBtlpi',
            lengthMenu: [10, 25, 50, 100],
            ordering: true,
            autoWidth: false,
            language: {
                search: ' ',
                sLengthMenu: '_MENU_',
                searchPlaceholder: "Cari Peran",
                info: "_START_ - _END_ of _TOTAL_ items",
                paginate: {
                    next: ' <i class=" fa fa-angle-right"></i>',
                    previous: '<i class="fa fa-angle-left"></i> '
                },
            },
            columns: [
                { data: "role_id" },
                { data: "role_name" },
                { data: "role_date" },
                { data: "action", class: "d-flex align-items-center" },
            ],
            initComplete: (settings, json) => {
                $('.dataTables_filter').appendTo('#tableSearch');
                $('.dataTables_filter').appendTo('.search-input');
                $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
            },
        });
    }

    function refreshRole() {
        $.ajax({
            url: "/getRole",
            method: "get",
            success: function (e) {
                if (!Array.isArray(e)) {
                    e = e.original || [];
                }
                console.log(e);
                table.clear().draw(); 
                // Manipulasi data sebelum masuk ke tabel
                for (let i = 0; i < e.length; i++) {
                    e[i].role_date = moment(e[i].created_at).format('D MMM YYYY');
                    var rp = "";
                    if (hasAccessAction("Peran & Perizinan", "edit")) {
                        rp +=
                            '<a href="#" class="btn btn-greys btn_edit me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i class="fa fa-edit me-1"></i> Edit Peran</a>';
                    }
                    if (hasAccessAction("Peran & Perizinan", "view")) {
                        rp +=
                            '<a href="/permission/' +
                            e[i].role_id +
                            '" class="btn btn-greys me-2"><i class="fa fa-shield me-1"></i> Perizinan</a>';
                        rp +=
                            '<a class="btn btn-greys me-2 btn_dashboard_widgets"><i class="fa fa-th-large me-1"></i> Widget Dashboard</a>';
                    }
                    e[i].action =
                        rp ||
                        '<span class="text-muted small">—</span>';
                }

                table.rows.add(e).draw();
                feather.replace(); // Biar icon feather muncul lagi
            },
            error: function (err) {
                console.error("Gagal load role:", err);
            }
        });
    }

    $(document).on("click",".btn-save",function(){
       LoadingButton(this);
        $('.is-invalid').removeClass('is-invalid');
        var url ="/insertRole";
        var valid=1;

        $("#add_role .fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });

        if(valid==-1){
            notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
            ResetLoadingButton('.btn-save', 'Simpan perubahan');
            return false;
        };

        param = {
            role_name:$('#role_name').val(),
             _token:token
        };

        if(mode==2){
            url="/updateRoleName";
            param.role_id = $('#add_role').attr("role_id");
        }

        LoadingButton($(this));
        $.ajax({
            url:url,
            data: param,
            method:"post",
            headers: {
                'X-CSRF-TOKEN': token
            },
            success:function(e){      
                ResetLoadingButton(".btn-save", 'Simpan perubahan');      
                afterInsert();
            },
            error:function(e){
                ResetLoadingButton(".btn-save", 'Simpan perubahan');
                console.log(e);
            }
        });
    });

    function afterInsert() {
        $(".modal").modal("hide");
        if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Peran");
        else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Peran");
        refreshRole();
    }

    $(document).on("keyup","#filter_role_name",function(){
        refreshRole();
    });
    //edit
    $(document).on("click",".btn_edit",function(){
        var data = $('#tableRole').DataTable().row($(this).parents('tr')).data();//ambil data dari table
        mode=2;
        $('#add_role .modal-title').html("Update Peran");
        $('#add_role input').empty().val("");
        $('#role_name').val(data.role_name);
        $('.is-invalid').removeClass('is-invalid');
        $('#add_role').modal("show");
        $('#add_role').attr("role_id", data.role_id);
    });

    function parseRoleAccess(roleAccessRaw) {
        if (Array.isArray(roleAccessRaw)) return roleAccessRaw;
        if (!roleAccessRaw) return [];
        try {
            var parsed = JSON.parse(roleAccessRaw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function getDashboardWidgetsFromAccess(roleAccessRaw) {
        var access = parseRoleAccess(roleAccessRaw);
        var row = access.find(function (item) {
            return String(item && item.name ? item.name : "").toLowerCase() === "dashboard widgets";
        });
        var list = row && Array.isArray(row.akses) ? row.akses : [];
        return list.map(function (v) { return String(v || "").trim(); }).filter(Boolean);
    }

    function syncDashboardCheckAll() {
        var boxes = $(".dash-widget-checkbox");
        var checked = $(".dash-widget-checkbox:checked");
        $("#check_all_dash_widgets").prop("checked", boxes.length > 0 && boxes.length === checked.length);
    }

    $(document).on("click", ".btn_dashboard_widgets", function () {
        var data = $('#tableRole').DataTable().row($(this).parents('tr')).data();
        selectedRoleForWidgets = data;
        $("#dash_widget_role_name").text(data.role_name || "-");
        $(".dash-widget-checkbox").prop("checked", false);
        var selected = getDashboardWidgetsFromAccess(data.role_access);
        for (var i = 0; i < selected.length; i++) {
            $('.dash-widget-checkbox[value="' + selected[i].replace(/"/g, '\\"') + '"]').prop("checked", true);
        }
        syncDashboardCheckAll();
        $("#dashboard_widgets_modal").modal("show");
    });

    $(document).on("change", "#check_all_dash_widgets", function () {
        $(".dash-widget-checkbox").prop("checked", $(this).is(":checked"));
    });

    $(document).on("change", ".dash-widget-checkbox", function () {
        syncDashboardCheckAll();
    });

    $(document).on("click", "#btn_save_dash_widgets", function () {
        if (!selectedRoleForWidgets) return;
        LoadingButton(this);
        var widgets = $(".dash-widget-checkbox:checked").map(function () {
            return $(this).val();
        }).get();
        $.ajax({
            url: "/updateDashboardWidgets",
            method: "post",
            data: {
                role_id: selectedRoleForWidgets.role_id,
                widgets: widgets,
                _token: token
            },
            headers: {
                'X-CSRF-TOKEN': token
            },
            success: function () {
                ResetLoadingButton("#btn_save_dash_widgets", "Simpan Widget");
                $("#dashboard_widgets_modal").modal("hide");
                notifikasi('success', "Berhasil Update", "Berhasil update widget dashboard role");
                refreshRole();
            },
            error: function (e) {
                ResetLoadingButton("#btn_save_dash_widgets", "Simpan Widget");
                console.log(e);
                notifikasi('error', "Gagal Update", "Gagal update widget dashboard role");
            }
        });
    });