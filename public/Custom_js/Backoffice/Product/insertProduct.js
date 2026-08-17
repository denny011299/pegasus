var dataRelasi=[];
var canAdd = true;
var rowId = 0;
var relasi =[];
var modeRelasi=0;
var canAccessSafetyStock = false;
autocompleteVariant("#product_variant");
autocompleteCategory("#product_category");
autocompleteUnit("#product_unit");

function refreshSafetyStockAccess() {
    canAccessSafetyStock = typeof hasAccessAction === "function"
        && hasAccessAction("Safety Stock", "edit");
    var whName = typeof getActiveWarehouseName === "function" ? getActiveWarehouseName() : null;
    var whLabel = whName
        ? ("Gudang aktif: " + whName)
        : "Mengikuti gudang aktif di header";
    $(".alert-stock-wh-label").text(whLabel);
    if (canAccessSafetyStock) {
        $(".col-safety-stock").removeClass("d-none");
        $(".cell-safety-stock").removeClass("d-none");
        $(".safety-stock-wh-label").text(whLabel);
    } else {
        $(".col-safety-stock").addClass("d-none");
        $(".cell-safety-stock").addClass("d-none");
    }
}

$(document).ready(function() {
    refreshSafetyStockAccess();
    $('.btn-save').html(mode == 1?"Tambah Produk" : "Update Produk");
    if (mode == 1) {
        canAdd=true;
        $('#tbVariant').html("")
        relasi.push([]);
        addRow();
        $('#tbRelasi').html("");
        reset();
    }

    if (mode == 2) {
        $('#product_name').val(data.product_name);
        $('#product_category').empty().append(`<option value="${data.category_id}">${data.product_category}</option>`);
        $('#tbVariant').html("");
        data.pr_variant.forEach((element,index) => {
            addRow(element.product_variant_name);
            $('.row-variant').last().find('.variant_sku').val(element.product_variant_sku);
            $('.row-variant').last().find('.variant_barcode').val(element.product_variant_barcode);
            $('.row-variant').last().find('.variant_id').val(element.product_variant_id);
            $('.row-variant').last().find('.variant_alert').val(element.product_variant_alert);
            $('.row-variant').last().find('.variant_safety_stock').val(element.safety_stock ?? 0);
            $('.row-variant').last().find('.variant_lead_time').val(element.lead_time_days ?? 0);
            if (element.retail_unit) {
                $('.row-variant').last().find('.unit_retail').val(element.retail_unit);
            }
            $('.row-variant').last().find('.variant_qty_per_pallet').val(element.qty_per_pallet || '');
        });

        $('#product_unit').empty();
        $('#unit_id').empty();
        data.pr_unit.forEach(element => {
            var newOption = new Option(element.unit_short_name, element.unit_id, true, true);
            $('#product_unit').append(newOption).trigger("change");
        });
        $('#unit_id').val(data.unit_id).trigger("change");

        relasi = [];
        data.pr_variant.forEach((element,index) => {
            relasi.push([]);
            element.relasi.forEach(rl => {
                relasi[index].push({
                    ...rl,
                    pr_id: rl.pr_id,
                    unit_id_1: rl.pr_unit_id_1,
                    unit_value_1: rl.pr_unit_value_1,
                    unit_id_2: rl.pr_unit_id_2,
                    unit_value_2: rl.pr_unit_value_2,
                    index: index,
                });
            });
            $('.unit_alert').eq(index).val(element.unit_id);
            if (element.safety_unit_id) {
                $('.unit_safety').eq(index).val(element.safety_unit_id);
            }
            if (element.retail_unit) {
                $('.unit_retail').eq(index).val(element.retail_unit);
            }
        });
    }
    refreshSafetyStockAccess();
});

function syncRelasi(idx) {
    relasi[idx].push({
        pvr_id : null,
        unit_id_1: null,
        unit_value_1: 1,
        unit_id_2: null,
        unit_value_2: 0,
    });
}

$('#unit_id').on('click', function() {
   $('.select2-search__field').remove();
});
$('#unit_id').on('change', function() {
   $('.select2-search__field').remove();
});

$(document).on('click','.btnAddRow',function(){
    var units;
    var safetyUnits;
    var retailUnits;
    if($('#product_variant').val()!=""&&$('#product_variant').val()!=null) {
        $('#tbVariant').html("")
        relasi = [];
        var sel = $('#product_variant').select2('data')[0];
        sel.name = JSON.parse(sel.variant_attribute);
        $('#product_variant').empty();
        sel.name.forEach((element,idx) => {
            addRow(element,idx);
            units = $('.unit_alert').last();
            units.html("");
            dataRelasi.forEach(item => {
                units.append(`<option value="${item.id}" >${item.text}</option>`);
            });
            safetyUnits = $('.unit_safety').last();
            safetyUnits.html("");
            dataRelasi.forEach(item => {
                safetyUnits.append(`<option value="${item.id}" >${item.text}</option>`);
            });
            retailUnits = $('.unit_retail').last();
            retailUnits.html('<option value="">-</option>');
            dataRelasi.forEach(item => {
                retailUnits.append(`<option value="${item.id}">${item.text}</option>`);
            });
            relasi.push([]);
        });
    }
   else{
        relasi.push([]);
        addRow();
        units = $('.unit_alert').last();
        units.html("");
        dataRelasi.forEach(item => {
            units.append(`<option value="${item.id}" >${item.text}</option>`);
        });
        safetyUnits = $('.unit_safety').last();
        safetyUnits.html("");
        dataRelasi.forEach(item => {
            safetyUnits.append(`<option value="${item.id}" >${item.text}</option>`);
        });
        retailUnits = $('.unit_retail').last();
        retailUnits.html('<option value="">-</option>');
        dataRelasi.forEach(item => {
            retailUnits.append(`<option value="${item.id}">${item.text}</option>`);
        });
    }
    if (mode==2) modeRelasi=1;

    if (units && units.length) units.val(units.find('option:first').val());
    if (safetyUnits && safetyUnits.length) safetyUnits.val(safetyUnits.find('option:first').val());
   if(mode==2) $(".btn-save").trigger("click");
   refreshSafetyStockAccess();
});

function addRow(names="",idx=0) {
    var safetyCellClass = canAccessSafetyStock ? "cell-safety-stock" : "cell-safety-stock d-none";
    $('#tbVariant').append(`
        <tr class="row-variant align-middle" style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s ease;">
            <td style="padding: 12px 16px;"><input type="text" class="form-control fill variant_name" placeholder="Masukan Nama" value="${names}"></td>
            <td style="padding: 12px 16px;"><input type="text" class="form-control fill variant_sku" placeholder="SKU"></td>
            <td style="padding: 12px 16px;"><input type="text" class="form-control variant_barcode" placeholder="Barcode"><input type="hidden" class="form-control variant_id"></td>
            <td style="padding: 12px 16px;">
                <div class="input-group">
                    <input type="text" class="form-control fill variant_alert" placeholder="Qty">
                    <select class="form-select variant_alert_type fill unit_alert"></select>
                </div>
            </td>
            <td style="padding: 12px 16px;">
                <select class="form-select unit_retail">
                    <option value="">-</option>
                </select>
            </td>
            <td style="padding: 12px 16px;">
                <input type="number" min="1" class="form-control number-only variant_qty_per_pallet" placeholder="Qty">
            </td>
            <td class="${safetyCellClass}" style="padding: 12px 16px;">
                <div class="input-group">
                    <input type="text" class="form-control variant_safety_stock nominal-only" placeholder="Qty" value="0">
                    <select class="form-select unit_safety"></select>
                </div>
            </td>
            <td style="padding: 12px 16px;">
                <input type="number" min="0" step="1" class="form-control variant_lead_time" placeholder="Hari" value="0">
            </td>
            <td class="d-flex align-items-center justify-content-center" style="padding: 12px 16px; gap: 8px;">
                <a class="btn_edit_relasi d-inline-flex align-items-center justify-content-center" index="${$('.row-variant').length}" href="javascript:void(0);" style="width: 32px; height: 32px; background: #eff6ff; color: #3b82f6; border-radius: 8px; transition: all 0.2s ease;" title="Atur Relasi">
                    <i data-feather="git-merge" style="width: 16px; height: 16px;"></i>
                </a>
                <a class="btn_delete_row d-inline-flex align-items-center justify-content-center" href="javascript:void(0);" style="width: 32px; height: 32px; background: #fee2e2; color: #ef4444; border-radius: 8px; transition: all 0.2s ease;" title="Hapus Variasi">
                    <i data-feather="trash-2" style="width: 16px; height: 16px;"></i>
                </a>
            </td>
        </tr>
    `);
    feather.replace();
    rowId++;
    refreshSafetyStockAccess();
}

$(document).on("click",".btn-save",function(){
    LoadingButton(this);
    $('.is-invalid').removeClass('is-invalid');

    var url ="/insertProduct";
    var valid=1;
    if(modeRelasi==0){
        $(".fill").each(function(){
            if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
                valid=-1;
                $(this).addClass('is-invalid');
            }
        });
    }

    if(valid==-1){
        notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
        ResetLoadingButton('.btn-save', mode == 1?"Tambah Produk" : "Update Produk");
        return false;
    };

    param = {
         product_name:$('#product_name').val(),
         category_id:$('#product_category').val(),
         unit_id:$('#unit_id').val(),
         product_unit:JSON.stringify($('#product_unit').val()),
         modeRelasi:modeRelasi,
         _token:token
    };

    var temp=[];
    $('.row-variant').each(function(){
        var variant = {
            variant_name: $(this).find('.variant_name').val(),
            variant_sku: $(this).find('.variant_sku').val(),
            variant_barcode: $(this).find('.variant_barcode').val(),
            variant_alert: $(this).find('.variant_alert').val(),
            product_variant_id: $(this).find('.variant_id').val(),
            unit_id: $(this).find('.unit_alert').val(),
            retail_unit: $(this).find('.unit_retail').val() || null,
            lead_time_days: Math.max(0, parseInt($(this).find('.variant_lead_time').val(), 10) || 0),
            qty_per_pallet: $(this).find('.variant_qty_per_pallet').val(),
        };
        if (canAccessSafetyStock) {
            variant.safety_stock = $(this).find('.variant_safety_stock').val() || 0;
            variant.safety_unit_id = $(this).find('.unit_safety').val() || null;
        }
        temp.push(variant);
    });

    param.product_variant = JSON.stringify(temp);
    param.product_relasi = JSON.stringify(relasi);

    if(mode==2){
        url="/updateProduct";
        param.product_id = data.product_id;
    }

    LoadingButton($(this));
    $.ajax({
        url:url,
        data: param,
        method:"post",
        headers: { 'X-CSRF-TOKEN': token },
        success:function(e){
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Produk" : "Update Produk");
            if (e == 1){
                if(modeRelasi==0){
                    if(mode==1)notifikasi('success', "Berhasil Insert", "Berhasil Tambah Produk");
                    else if(mode==2)notifikasi('success', "Berhasil Update", "Berhasil Update Produk");
                    afterInsert();
                }
                else{
                    $('#modalRelasi').modal('hide');
                    notifikasi('success', "Berhasil Simpan", 'Berhasil Simpan Relasi Unit');
                    modeRelasi=0;
                }
            }
            else {
                $('#product_name').addClass('is-invalid');
                notifikasi('error', "Gagal Insert", e.message);
            }
        },
        error:function(e){
            ResetLoadingButton(".btn-save", mode == 1?"Tambah Produk" : "Update Produk");
            console.log(e);
        }
    });
});

function afterInsert() {
    window.location.href = "/product";
}

$(document).on("click","#btnAddRowRelasi",function(){
    var r1 = $('#relasi1').val();
    var r2 = $('#relasi2').val();
    if(!r1 || !r2){
        notifikasi('error', "Gagal Tambah", "Relasi unit tidak boleh kosong");
        return false;
    }
    if(r1==r2){
        notifikasi('error', "Gagal Tambah", "Relasi unit tidak boleh sama");
        return false;
    }
    // Rantai harus berurutan: begitu sudah ada baris, sisi kiri wajib = sisi kanan baris
    // terakhir (dikunci lewat refreshRelasiUnitOptions()), dan sisi kanan tidak boleh unit yang
    // sudah pernah dipakai di rantai ini — cek ulang di sini sebagai jaga-jaga kalau opsi yang
    // seharusnya disabled itu tetap ke-submit.
    var existingRows = $('#tbRelasi .row-relasi');
    if (existingRows.length > 0) {
        var expectedLeft = String(existingRows.last().attr('right'));
        if (String(r1) !== expectedLeft) {
            notifikasi('error', "Gagal Tambah", "Unit sisi kiri harus melanjutkan dari unit sisi kanan relasi sebelumnya");
            return false;
        }
        var usedUnitIds = [];
        existingRows.each(function () {
            usedUnitIds.push(String($(this).attr('left')));
            usedUnitIds.push(String($(this).attr('right')));
        });
        if (usedUnitIds.indexOf(String(r2)) !== -1) {
            notifikasi('error', "Gagal Tambah", "Unit ini sudah dipakai pada rantai relasi sebelumnya");
            return false;
        }
    }

    var currentIndex = $('.row-relasi').length;
    console.log("Menambahkan Baris ke-" + currentIndex + ": " + r1 + " - " + r2);

    addRowRelasi(
        {
            pr_unit_id_1: r1,
            pr_unit_name_1: $('#relasi1 option:selected').text().trim()

        },
        {
            pr_unit_id_2: r2,
            pr_unit_name_2: $('#relasi2 option:selected').text().trim()
        }
    );
    $('#relasi2').val('');
    refreshRelasiUnitOptions();
});

$(document).on("change","#product_unit",function(){
    dataRelasi = $(this).select2("data");
    var select = dataRelasi.length==1?1:$('#unit_id').val();

    $('#unit_id,#relasi1,#relasi2').html("");
    dataRelasi.forEach(item => {
        $('#unit_id').append(`<option value="${item.id}">${item.text}</option>`);
        $('#relasi1,#relasi2').append(`<option value="${item.id}">${item.text}</option>`);
    });

    if(dataRelasi.length>1)$('#unit_id').val(select);
    else $('#unit_id').eq(select).prop('selected', true);
    $('#unit_id').trigger("change");

    if (dataRelasi.length == 1 || dataRelasi.length < 1) {
        $('#tbRelasi').html("");
        if (dataRelasi.length < 1) $('#unit_id').val("");
    }

    $('.unit_alert, .unit_safety, .unit_retail').each(function() {
        var units = $(this);
        var vl = units.val();
        var isRetail = units.hasClass('unit_retail');
        units.html(isRetail ? '<option value="">-</option>' : '');
        dataRelasi.forEach(item => {
            units.append(`<option value="${item.id}" ${vl==item.id?'selected':''}>${item.text}</option>`);
        });
        if (vl != null && vl !== "") units.val(vl);
        else if (!isRetail) units.val(units.find('option:first').val());
    });
});

$(document).on("change","#unit_id",function(){
    $('.select2-search__field').remove();
});
$(document).on("change",".unit_alert",function(){
    if (mode == 2){
        modeRelasi=1;
        $(".btn-save").trigger("click");
    }
});

$('#unit_id').on('click', function() {
   $('.select2-search__field').remove();
});

// Ditambahkan (2026-08-06): relasi unit produk HARUS membentuk satu rantai berurutan
// (mis. Dos->Pcs->Liter), bukan pasangan bebas — kalau tidak, konversi satuan-terkecil di
// backend (ProductionController::convertQtyToSmallestUnit()) bisa salah hitung. Dulu user bebas
// pilih pasangan unit mana pun di #relasi1/#relasi2 setiap klik "Tambah Relasi", tidak ada yang
// menjamin pasangan baru itu nyambung ke pasangan sebelumnya. Sekarang begitu sudah ada minimal 1
// baris, sisi kiri (#relasi1, unit yang lebih besar) OTOMATIS dikunci ke sisi kanan (unit yang
// lebih kecil) dari baris TERAKHIR — user hanya perlu pilih unit berikutnya di sisi kanan. Unit
// yang sudah dipakai di rantai (kiri maupun kanan mana pun) juga disingkirkan dari pilihan sisi
// kanan supaya rantai tidak bisa memutar balik ke unit yang sudah dipakai.
function refreshRelasiUnitOptions() {
    var rows = $('#tbRelasi .row-relasi');
    var usedUnitIds = [];
    rows.each(function () {
        usedUnitIds.push(String($(this).attr('left')));
        usedUnitIds.push(String($(this).attr('right')));
    });

    if (rows.length > 0) {
        var lockedUnitId = String(rows.last().attr('right'));
        $('#relasi1').val(lockedUnitId).prop('disabled', true);
    } else {
        $('#relasi1').prop('disabled', false);
    }

    $('#relasi2 option').each(function () {
        var opt = $(this);
        var isUsed = usedUnitIds.indexOf(String(opt.val())) !== -1;
        opt.prop('disabled', isUsed);
    });
    if (usedUnitIds.indexOf(String($('#relasi2').val())) !== -1) {
        $('#relasi2').val('');
    }
}
function addRowRelasi(element1,element2) {
    $('#tbRelasi').append(`
        <tr class="row-relasi" left="${element1.pr_unit_id_1 ? element1.pr_unit_id_1 : element2.id}" right="${element2.pr_unit_id_2 ? element2.pr_unit_id_2 : element2.id}">
            <td>
                <div class="input-group">
                    <input type="text" class="form-control nominal-only unit1 fill" value="1"
                    data-unit_id="${element1.pr_unit_id_1 ? element1.pr_unit_id_1 : element1.id}" disabled>
                    <span class="input-group-text unit_text_1">
                        ${element1.pr_unit_name_1 ? element1.pr_unit_name_1 : element1.text}
                    </span>
                    <input type="hidden" class="form-control pr_id" value="${element1.pr_id || ''}">
                </div>
            </td>
            <td>
                <div class="input-group">
                    <input type="text" class="form-control nominal-only unit2 fill" placeholder="Masukan Nilai"
                    data-unit_id="${element2.pr_unit_id_2 ? element2.pr_unit_id_2 : element2.id}" value="${element2.pr_unit_value_2 ? element2.pr_unit_value_2 : element2.unit_value_2??0}">
                    <span class="input-group-text unit_text_2">
                        ${element2.pr_unit_name_2 ? element2.pr_unit_name_2 : element2.text}
                    </span>
                </div>
            </td>
            <td>
                <a class="p-2 btn-action-icon btn_delete_relasi" href="javascript:void(0);">
                    <i class="fe fe-trash-2"></i>
                </a>
            </td>
        </tr>
    `);
    feather.replace();
}

$(document).on('click', '.btn_delete_relasi', function() {
    $(this).closest('tr').remove();
    refreshRelasiUnitOptions();
});

$(document).on("click",".btn_delete_row",function(){
    if($('.row-variant').length<2) {
        notifikasi('error', "Gagal Hapus", "Minimal 1 varian harus ada");
        return false;
    }
    var index = $(this).closest("tr").index();
    relasi.splice(index,1);
    $(this).closest("tr").remove();
    if(mode==2){
        modeRelasi=1;
        $(".btn-save").trigger("click");
    }
});

function reset() {
    $('#tbRelasi').html(`
        <tr>
             <td class="text-center" colspan="2">
                 Pilih Minimal 2 unit untuk mengatur relasi unit
             </td>
        </tr>
    `);
}

$(document).on('click', '.btn-back', function(){ history.go(-1); })
$(document).on('click', '.btn-clear', function(){
    $('.is-invalid').removeClass('is-invalid');
    $('.form-control').val("");
    $('.form-select').empty();
})

$(document).on('click', '#btnSaveRelasi', function(){
    var index = parseInt($(this).attr('index'));
    var valid=1;
    $('.is-invalid').removeClass('is-invalid');
    $(".unit2").each(function(){
        if($(this).val()==null||$(this).val()==0||$(this).val()==""){
            valid=-1;
            $(this).addClass('is-invalid');
        }
    });
    if(valid==-1) return false;

    relasi[index] = $('.row-relasi').map(function(idx, el) {
        var row = $(el);
        return {
            index: index,
            unit_value_2: row.find('.unit2').val(),
            pr_unit_id_1: row.attr('left'),
            pr_unit_id_2: row.attr('right'),
            pr_unit_name_1: row.find('.unit_text_1').text().trim(),
            pr_unit_name_2: row.find('.unit_text_2').text().trim(),
            pr_id: row.find('.pr_id').val()
        };
    }).get();

    if(mode == 1){
        $('#modalRelasi').modal('hide');
        notifikasi('success', "Berhasil Simpan", 'Berhasil Simpan Relasi Unit');
    } else {
        modeRelasi = 1;
        $(".btn-save").trigger("click");
    }
});

$(document).on('click', '.btn_edit_relasi', function(){
    $('.is-invalid').removeClass('is-invalid');
    var index = $(this).attr('index');
    $('#btnSaveRelasi').attr('index',index);
    $('#tbRelasi').html("");
    if(relasi[index]) {
        relasi[index].forEach((item, idx) => {
            addRowRelasi(item, item);
        });
    }
    $('#relasi2').val('');
    refreshRelasiUnitOptions();

    $('#modalRelasi').modal('show');
});
