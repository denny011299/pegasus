$(document).on("click", "#btn-login", function () {
    LoadingButton("#btn-login");
    $('.is-invalid').removeClass('is-invalid');
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
    // Perform login action
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
                var sendTo = "/admin/";
                window.location.href = sendTo;
            } else {
                notifikasi("error", "Login Gagal", "Silahkan cek kembali username dan password");
                $(".fill").each(function () {
                    $(this).addClass("is-invalid");
                });
            }
            ResetLoadingButton("#btn-login", "Login");
        },
        error: function (xhr, status, error) {
            ResetLoadingButton("#btn-login", "Login");
            notifikasi("error", "Login Gagal", "");
        },
    });
});
