$(document).on("click", "#btn-login", function () {
    LoadingButton("#btn-login");
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
        ResetLoadingButton("#btn-login", "Save changes");
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
            if (response.length > 0) {
                var sendTo = "/admin/";
                window.location.href = sendTo;
            } else {
                notifikasi("error", "Login Gagal", "");
            }
            ResetLoadingButton("#btn-login", "Sign In");
        },
        error: function (xhr, status, error) {
            ResetLoadingButton("#btn-login", "Sign In");
            notifikasi("error", "Login Gagal", "");
        },
    });
});
