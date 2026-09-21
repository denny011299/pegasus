function doLogin() {
    LoadingButton("#btn-login");
    $(".is-invalid").removeClass("is-invalid");
    var username = $("#username").val();
    var password = $("#password").val();
    var valid = 0;
    $(".fill").each(function () {
        if (
            $(this).val() == null ||
            $(this).val() == "null" ||
            $(this).val() == ""
        ) {
            valid = -1;
            $(this).addClass("is-invalid");
        }
    });

    if (valid == -1) {
        notifikasi(
            "error",
            "Gagal Insert",
            "Silahkan cek kembali inputan anda"
        );
        ResetLoadingButton("#btn-login", "Login");
        return false;
    }

    $.ajax({
        url: "/loginUser",
        method: "post",
        data: {
            staff_username: username,
            staff_password: password,
            _token: token,
        },
        success: async function (response) {
            if (response.length > 0 && response != -1) {
                window.location.href = "/admin/";
            } else {
                notifikasi(
                    "error",
                    "Login Gagal",
                    "Silahkan cek kembali username dan password"
                );
                $(".fill").each(function () {
                    $(this).addClass("is-invalid");
                });
            }
            ResetLoadingButton("#btn-login", "Login");
        },
        error: function (xhr) {
            ResetLoadingButton("#btn-login", "Login");
            if (typeof handlePermissionError === "function" && handlePermissionError(xhr)) {
                return;
            }
            notifikasi("error", "Login Gagal", "");
        },
    });
}

$(document).on("click", "#btn-login", function (e) {
    e.preventDefault();
    doLogin();
});

// Enter di username/password → login (tombol type=button tidak submit form)
$(document).on("keydown", "#username, #password", function (e) {
    if (e.key === "Enter" || e.keyCode === 13) {
        e.preventDefault();
        doLogin();
    }
});
