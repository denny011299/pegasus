$(document).ready(function () {
    sorotBagianAktif();
});

// Ganti versi memuat ulang halaman yang SEDANG dibuka, bukan selalu kembali ke
// halaman Umum — supaya pengguna tetap berada di modul yang sedang dibaca.
$(document).on('change', '#docVersion', function () {
    var version = encodeURIComponent($(this).val());
    var path = docGroupKey
        ? '/externalApiDocumentation/' + encodeURIComponent(docGroupKey)
        : '/externalApiDocumentation';

    window.location.href = path + '?version=' + version;
});

// Hanya tautan dalam halaman (#anchor) yang digulir halus. Tautan antar modul
// kini benar-benar berpindah halaman, jadi dibiarkan berjalan seperti biasa.
$(document).on('click', '.extapi-toc-list a[href^="#"]', function (e) {
    var target = $($(this).attr('href'));
    if (!target.length) return;

    e.preventDefault();
    $('html, body').animate({ scrollTop: target.offset().top - 80 }, 250);
    $('.extapi-toc-list a[href^="#"]').removeClass('active');
    $(this).addClass('active');
});

// Menandai bagian yang sedang dibaca saat pengguna menggulir. Hanya berlaku
// untuk tautan dalam halaman; tautan modul aktif ditandai server lewat Blade.
function sorotBagianAktif() {
    var sections = $('.extapi-toc-list a[href^="#"]').map(function () {
        var id = $(this).attr('href');
        return $(id).length ? { id: id, top: $(id).offset().top } : null;
    }).get();

    if (!sections.length) return;

    $(window).on('scroll', function () {
        var pos = $(window).scrollTop() + 100;
        var current = sections[0];

        for (let i = 0; i < sections.length; i++) {
            if (sections[i].top <= pos) current = sections[i];
        }

        $('.extapi-toc-list a[href^="#"]').removeClass('active');
        $('.extapi-toc-list a[href="' + current.id + '"]').addClass('active');
    });
}
