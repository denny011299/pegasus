var mode=1;//1 = auto scan, 2 = manual input
var type= 1;//1 = all, 2 = In, 3 = Out
var tableIn, tableOut, tableAll;

$('#input_barcode').trigger("focus");
// $('#filter_start_date').val(getCurrentDate(-1));
// $('#filter_end_date').val(getCurrentDate());
// afterInsert();

$(document).ready(function(){
    inisialisasi();
    refreshManageStock();
})

function inisialisasi(){
    tableAll = $('#tableProductAll').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        ordering: true,
        autoWidth: false,
        searching: false,
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Search Products (All)",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "dates" },
            { data: "product" },
            { data: "ms_sku" },
            { data: "ms_product_in" },
            { data: "ms_product_out" },
        ],
        initComplete: (settings, json) => {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });

    tableIn = $('#tableProductIn').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        ordering: true,
        autoWidth: false,
        searching: false,
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Search Products (In)",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "dates" },
            { data: "product" },
            { data: "ms_sku" },
            { data: "ms_product_in" },
            { data: "ms_product_out" },
        ],
        initComplete: (settings, json) => {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });

    tableOut = $('#tableProductOut').DataTable({
        bFilter: true,
        sDom: 'fBtlpi',
        ordering: true,
        autoWidth: false,
        searching: false,
        language: {
            search: ' ',
            sLengthMenu: '_MENU_',
            searchPlaceholder: "Search Products (Out)",
            info: "_START_ - _END_ of _TOTAL_ items",
            paginate: {
                next: ' <i class=" fa fa-angle-right"></i>',
                previous: '<i class="fa fa-angle-left"></i> '
            },
        },
        columns: [
            { data: "dates" },
            { data: "product" },
            { data: "ms_sku" },
            { data: "ms_product_in" },
            { data: "ms_product_out" },
        ],
        initComplete: (settings, json) => {
            $('.dataTables_filter').appendTo('#tableSearch');
            $('.dataTables_filter').appendTo('.search-input');
            $('.dataTables_filter label').prepend('<i class="fa fa-search"></i> ');
        },
    });
}

function refreshManageStock(){
    $.ajax({
        url: "/getManageStock",
        type: "get",
        // data:{
        //     ms_type:1,
        //      "ms_start_date":$('#filter_start_date').val(),
        //     "ms_end_date":$('#filter_end_date').val(),
        // },
        success: function (e) {
            if (!Array.isArray(e)) {
                e = e.original || [];
            }
            tableIn.clear().draw();
            tableOut.clear().draw();
            tableAll.clear().draw();
            for (var i = 0; i < e.length; i++) {
                e[i].dates = moment(e[i].ms_date).format('D MMM YYYY');
                e[i].product = `<img src="${public+e[i].ms_image}" class="me-2" style="width:30px">`+e[i].ms_name;
                e[i].action=`
                    <a class="p-2 btn-action-icon btn_delete" href="javascript:void(0);">
                        <i data-feather="trash-2"></i>
                    </a>
                `;
            }
            tableIn.rows.add(e).draw();
            tableOut.rows.add(e).draw();
            tableAll.rows.add(e).draw();
            feather.replace();
        },
        error: function (e) {
            console.log(e.responseText);
        },
    });
}

$(document).on("click",".btn_scan",function(){
    mode= $(this).val();
    $('#input_barcode').val("");
    $('#input_qty').val("1");
    $('#input_barcode').trigger("focus");
});

// $(document).on("change","#filter_start_date,#filter_end_date",function(){
//     afterInsert();
// });


$(document).on("click",".nav-jenis",function(){
    type= $(this).attr("tipe");
    // afterInsert();
     $('#input_barcode').trigger("focus");
});

$('#input_barcode').on('keyup', function(e) {
    if (e.which === 13) { // 13 = Enter
        const barcode = $(this).val();
        console.log("Barcode:", barcode);
        if(mode==1){
            // Auto scan mode
            // insertData(barcode);
            $(this).val(""); // reset input
        }
        else{
            $('#input_qty').focus().select();
        }
    } 
});

$('#input_qty').on('keyup', function(e) {
    if (e.which === 13) { // 13 = Enter
        const barcode = $(this).val();
        // insertData(barcode);
        $('#input_barcode').val("");
        $('#input_qty').val("1");
        $('#input_barcode').trigger("focus");
    } 
});

// function insertData() {
//     LoadingButton(this);
//     $('.is-invalid').removeClass('is-invalid');
//     var url ="/insertManageProduct";
//     var valid=1;

//     $("#modalInsert .fill").each(function(){
//         if($(this).val()==null||$(this).val()=="null"||$(this).val()==""){
//             valid=-1;
//             $(this).addClass('is-invalid');
//         }
//     });

//     if(valid==-1){
//         notifikasi('error', "Gagal Insert", 'Silahkan cek kembali inputan anda');
//         ResetLoadingButton('.btn-save', 'Save changes');
//         return false;
//     };

//     param = {
//         ms_type:$('#input-type').val(),//1 = In , 2 = Out
//         barcode:$('#input_barcode').val(),
//         ms_stock:$('#input_qty').val(),
//         jenis_insert:2,//2 = Product , 1 = supplies
//          _token:token
//     };

//     LoadingButton($(this));
    
//     $.ajax({
//         url:url,
//         data: param,
//         method:"post",
//         headers: {
//             'X-CSRF-TOKEN': token
//         },
//         success:function(e){      
//             if($('#input-type').val()==1){
//                 toastr.success('', 'Successfully Added Incoming Item');
//             }
//             else{
//                 toastr.success('', 'Successfully Added Outgoing Item');
                
//             }
//             afterInsert();
//         },
//         error:function(e){
//             console.log(e);
//         }
//     });
// }


// function afterInsert() {
//     if(type==1){
//         getAll();
//     }else if(type==2){
//         getIn();
//     }else{
//         getOut();
//     }
// }