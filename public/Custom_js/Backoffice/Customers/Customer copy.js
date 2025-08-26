var table;
$(document).ready(function () {
    inisialisasi();
    refreshCustomers();
});

function inisialisasi() {
    // Cek apakah DataTable sudah diinisialisasi sebelumnya
    if ($.fn.DataTable.isDataTable("#tableCustomers")) {
        // Destroy DataTable yang sudah ada
        $("#tableCustomers").DataTable().destroy();
    }

    table = $("#tableCustomers").DataTable({
        bFilter: true,
        sDom: "fBtlpi",
        ordering: true,
        language: {
            search: " ",
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Search",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> ',
            },
        },
        columns: [
            { data: "nama" },
            { data: "customer_id" },
            { data: "email" },
            { data: "telepon" },
            {
                data: null,
                render: function (data, type, row) {
                    // Gabungkan kota dan provinsi dalam satu kolom
                    return `${row.kota || ""}${
                        row.kota && row.provinsi ? ", " : ""
                    }${row.provinsi || ""}`;
                },
            },
            { data: "action", class: "action-table-data" },
        ],
        initComplete: (settings, json) => {
            $(".dataTables_filter").appendTo("#tableSearch");
            $(".dataTables_filter").appendTo(".search-input");
        },
    });
}
function refreshCustomers() {
    var url = "/admin/getCustomers";
    $.ajax({
        url: url,
        method: "get",
        success: function (e) {
            table.clear().draw();
            e = JSON.parse(e);
            for (let i = 0; i < e.length; i++) {
                // Menambahkan status untuk customers
                e[
                    i
                ].customer_status = `<span class="badge badge-linesuccess">Active</span>`;

                // Format tanggal jika ada created_at
                if (e[i].created_at) {
                    e[i].customer_date = moment(e[i].created_at).format(
                        "D MMM YYYY"
                    );
                }

                // Action buttons untuk edit dan delete
                e[i].action = `
                        <div class="edit-delete-action">
                            <a class="me-2 p-2 btn_edit" 
                                data-customer-id="${e[i].customer_id}"
                                data-bs-toggle="modal"
                                data-bs-target="#edit-customer">
                                <i data-feather="edit" class="feather-edit"></i>
                            </a>
                            <a class="p-2 btn_delete" 
                                data-customer-id="${e[i].customer_id}"
                                href="javascript:void(0);">
                                <i data-feather="trash-2" class="feather-trash-2"></i>
                            </a>
                        </div>
                    `;
            }
            console.log(e);

            table.rows.add(e).draw();

            // Re-initialize feather icons after adding new content
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        },
        error: function (e) {
            console.log("Error loading customers:", e);
            // Tampilkan pesan error jika diperlukan
            if (e.responseJSON && e.responseJSON.message) {
                console.error("Server error:", e.responseJSON.message);
            }
        },
    });
}

// Function untuk insert customer baru
function insertCustomer(formData) {
    $.ajax({
        url: "/admin/insertCustomer",
        method: "POST",
        data: formData,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            // Re-enable button
            $("#btn-submit-add-customer")
                .prop("disabled", false)
                .text("Simpan");

            if (response.success) {
                // Tutup modal
                $("#add-customer").modal("hide");

                // Reset form
                $("#addCustomerForm")[0].reset();
                // Reset individual fields jika form tidak ter-reset
                $(
                    "#nama_customer, #email_customer, #telepon_customer, #alamat_customer, #deskripsi_customer, #provinsi_customer, #kota_customer"
                ).val("");

                // Refresh table
                refreshCustomers();

                // Tampilkan success message
                showSuccessMessage(response.message);
            } else {
                showErrorMessage(response.message);
            }
        },
        error: function (xhr) {
            // Re-enable button
            $("#btn-submit-add-customer")
                .prop("disabled", false)
                .text("Simpan");

            let errorMessage = "Terjadi kesalahan saat menambah customer";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showErrorMessage(errorMessage);
        },
    });
}

// Function untuk update customer
function updateCustomer(formData) {
    $.ajax({
        url: "/admin/updateCustomer",
        method: "POST",
        data: formData,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            // Re-enable button
            $("#btn-submit-edit-customer")
                .prop("disabled", false)
                .text("Update");

            if (response.success) {
                // Tutup modal
                $("#edit-customer").modal("hide");

                // Refresh table
                refreshCustomers();

                // Tampilkan success message
                showSuccessMessage(response.message);
            } else {
                showErrorMessage(response.message);
            }
        },
        error: function (xhr) {
            // Re-enable button
            $("#btn-submit-edit-customer")
                .prop("disabled", false)
                .text("Update");

            let errorMessage = "Terjadi kesalahan saat mengupdate customer";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showErrorMessage(errorMessage);
        },
    });
}

// Function untuk delete customer
function deleteCustomer(customerId) {
    if (confirm("Apakah Anda yakin ingin menghapus customer ini?")) {
        $.ajax({
            url: "/admin/deleteCustomer",
            method: "POST",
            data: {
                customer_id: customerId,
            },
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.success) {
                    // Refresh table
                    refreshCustomers();

                    // Tampilkan success message
                    showSuccessMessage(response.message);
                } else {
                    showErrorMessage(response.message);
                }
            },
            error: function (xhr) {
                let errorMessage = "Terjadi kesalahan saat menghapus customer";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showErrorMessage(errorMessage);
            },
        });
    }
}

// Helper functions untuk menampilkan pesan
function showSuccessMessage(message) {
    // Implementasi sesuai dengan sistem notifikasi yang digunakan
    console.log("Success:", message);
    // Contoh jika menggunakan toast atau alert
    if (typeof toastr !== "undefined") {
        toastr.success(message);
    } else {
        alert(message);
    }
}

function showErrorMessage(message) {
    // Implementasi sesuai dengan sistem notifikasi yang digunakan
    console.log("Error:", message);
    // Contoh jika menggunakan toast atau alert
    if (typeof toastr !== "undefined") {
        toastr.error(message);
    } else {
        alert(message);
    }
}

$(document).on("click", "#btn-submit-add-customer", function () {
    // Ambil nilai dari form input
    const nama = $("#nama_customer").val();
    const email = $("#email_customer").val();
    const telepon = $("#telepon_customer").val();
    const alamat = $("#alamat_customer").val();
    const deskripsi = $("#deskripsi_customer").val();
    const provinsi = $("#provinsi_customer").val();
    const kota = $("#kota_customer").val();

    // Validasi input required
    if (!nama.trim()) {
        showErrorMessage("Nama customer harus diisi!");
        return;
    }

    // Siapkan data untuk dikirim
    const formData = {
        nama: nama,
        email: email,
        telepon: telepon,
        alamat: alamat,
        deskripsi: deskripsi,
        provinsi: provinsi,
        kota: kota,
    };

    // Disable button saat proses
    $(this).prop("disabled", true).text("Menyimpan...");

    // Panggil function insertCustomer
    insertCustomer(formData);

    // Enable button kembali setelah selesai (akan di-handle di success/error callback)
    setTimeout(() => {
        $(this).prop("disabled", false).text("Simpan");
    }, 2000);
});

// Event handler untuk submit edit customer
$(document).on("click", "#btn-submit-edit-customer", function () {
    // Ambil nilai dari form edit
    const customer_id = $("#edit_customer_id").val();
    const nama = $("#edit_nama").val();
    const email = $("#edit_email").val();
    const telepon = $("#edit_telepon").val();
    const alamat = $("#edit_alamat").val();
    const deskripsi = $("#edit_deskripsi").val();
    const provinsi = $("#edit_provinsi").val();
    const kota = $("#edit_kota").val();

    // Validasi input required
    if (!customer_id) {
        showErrorMessage("Customer ID tidak ditemukan!");
        return;
    }

    if (!nama.trim()) {
        showErrorMessage("Nama customer harus diisi!");
        return;
    }

    // Siapkan data untuk dikirim
    const formData = {
        customer_id: customer_id,
        nama: nama,
        email: email,
        telepon: telepon,
        alamat: alamat,
        deskripsi: deskripsi,
        provinsi: provinsi,
        kota: kota,
    };

    // Disable button saat proses
    $(this).prop("disabled", true).text("Mengupdate...");

    // Panggil function updateCustomer
    updateCustomer(formData);

    // Enable button kembali setelah selesai (akan di-handle di success/error callback)
    setTimeout(() => {
        $(this).prop("disabled", false).text("Update");
    }, 2000);
});

// Event handlers untuk tombol edit dan delete di table
$(document).on("click", ".btn_edit", function () {
    const customerId = $(this).data("customer-id");
    // Load data customer untuk edit
    loadCustomerForEdit(customerId);
});

$(document).on("click", ".btn_delete", function () {
    const customerId = $(this).data("customer-id");
    deleteCustomer(customerId);
});

// Function untuk load data customer untuk edit
function loadCustomerForEdit(customerId) {
    $.ajax({
        url: "/admin/getCustomers",
        method: "GET",
        data: {
            customer_id: customerId,
        },
        success: function (response) {
            const data = JSON.parse(response);
            if (data.length > 0) {
                const customer = data[0];

                // Populate form edit
                $("#edit_customer_id").val(customer.customer_id);
                $("#edit_nama").val(customer.nama);
                $("#edit_email").val(customer.email);
                $("#edit_telepon").val(customer.telepon);
                $("#edit_alamat").val(customer.alamat);
                $("#edit_provinsi").val(customer.provinsi);
                $("#edit_kota").val(customer.kota);
                $("#edit_deskripsi").val(customer.deskripsi);
            }
        },
        error: function (xhr) {
            showErrorMessage("Gagal memuat data customer");
        },
    });
}
