@extends('layout.mainlayout')

@section('content')
<style>
    @media (max-width: 767.98px) {
    /* Target tabel variasi di halaman update product */
    #productVariantTable input.form-control {
        /* Memaksa input memiliki lebar minimum agar tidak terlalu kecil */
        min-width: 150px; 
    }
    
    /* Memastikan sel (td) tabel tidak terlalu menekan konten */
    #productVariantTable td {
        white-space: nowrap; /* Mencegah teks/input membungkus, memaksa scroll horizontal */
        padding-left: 5px !important;
        padding-right: 5px !important;
    }

    /* Memastikan header tabel juga tidak membungkus */
    #productVariantTable th {
        white-space: nowrap;
    }
      /* 2. Paksa Input Group untuk TIDAK MEMBUNGKUS */
    #productVariantTable .input-group {
        /* KUNCI: Memaksa elemen flex (input dan span) tetap sejajar */
        flex-wrap: nowrap !important;
        /* Memastikan input group punya lebar minimum yang cukup */
        min-width: 120px; 
    }

    /* 3. Atur Lebar Input Angka sekecil mungkin agar span satuan (kg) mendapat tempat */
    #productVariantTable .input-group input.form-control {
        min-width: 40px; 
        /* Pastikan input mengisi sisa ruang setelah satuan */
        width: 100%; 
    }

    /* 4. Pastikan span satuan (kg) tidak terpotong */
    #productVariantTable .input-group .input-group-text {
        min-width: 30px; 
        padding: 0.375rem 0.5rem; /* Sesuaikan padding agar terlihat bagus */
    }

    /* Opsional: Memperbaiki tampilan input group di kolom yang hanya berisi input group */
    #productVariantTable td:has(.input-group) {
        /* Menghilangkan padding yang berlebihan di sel, memberikan ruang lebih ke input group */
        padding: 0.5rem !important;
    }
}
</style>
    <!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content container-fluid">
            <div class="card mb-0 p-3">
                <div class="card-body">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="content-page-header">
                            <div class="d-flex justify-content-between w-100">
                                <h4>{{$title}}</h4>
                                <button class="btn btn-back">Kembali</button>
                            </div>
                        </div>
                    </div>
                    <!-- /Page Header -->
                    <div class="row">
                        <div class="col-md-12">
                            <form action="#">
                                <div class="form-group-item">
                                    <div class="row">
                                        <div class="col-12 col-md-4">
                                            <div class="input-block mb-3">
                                                <label>Nama<span class="text-danger">*</span></label>
                                                <input type="text" class="form-control fill" id="product_name"
                                                    placeholder="Input Nama Produk">
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="input-block mb-3">
                                                <label>Kategori<span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="product_category">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="input-block mb-3">
                                                <label>Satuan<span class="text-danger">*</span></label>
                                                <div class="container-satuan">
                                                    <select class="form-select fill" id="product_unit"  name="product_unit[]" ></select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="input-block mb-3">
                                                <label>Default Unit<span class="text-danger">*</span></label>
                                                <select class="form-select fill select2" id="unit_id">
                                                </select>
                                            </div>
                                        </div>
                                        <hr>
                                       <div class="row mb-3">
                                            <div class="col-12 col-lg-2">
                                                <label>Variasi Produk</label>
                                            </div>

                                            <div class="col-0 col-lg-5"></div>

                                            <div class="col-12 col-lg-3 mb-2 mb-lg-0">
                                                <select id="product_variant" class="form-select select2">
                                                    <option value="">Pilih Variasi</option>
                                                </select>
                                            </div>

                                            <div class="col-12 col-lg-2 text-start text-lg-end">
                                                <button type="button" class="btn btn-primary btnAddRow w-100 w-lg-auto">
                                                    <i class="fa fa-plus-circle me-2"></i>
                                                    <span class="d-none d-lg-inline">Tambah</span>
                                                    <span class="d-inline d-lg-none">Tambah Variasi</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">

                                            <table class="table" id="productVariantTable">
                                                <thead>
                                                    <tr>
                                                        <td>Nama Variasi<span class="text-danger">*</span></td>
                                                        <td>SKU<span class="text-danger">*</span></td>
                                                        <td>Harga<span class="text-danger">*</span></td>
                                                        <td>Barcode</td>
                                                        <td>Stock Alert<span class="text-danger">*</span></td>
                                                        <td class="text-center" style="width:15%">Aksi</td>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbVariant">
                                                
                                                </tbody>
                                            </table>
                                        </div>

                                        
                                    </div>
                                </div>
                                <div class="add-customer-btns text-end">
                                    <a class="btn btn-outline-secondary btn-clear">Clear</a>
                                    <a class="btn btn-primary btn-save">Simpan Perubahan</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal " tabindex="-1" id="modalRelasi" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atur Relasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <table class="table table-bordered mb-2">
                            <thead>
                                <tr>
                                    <td>Name Unit 1<span class="text-danger">*</span></td>
                                    <td>Name Unit 2<span class="text-danger">*</span></td>
                                </tr>
                            </thead>
                            <tbody class="tbRelasi" id="tbRelasi">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary ms-2" id="btnSaveRelasi">Save changes</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('custom_js')
    <script>
        var public = "{{ asset('') }}";
        var mode="{{$mode}}";
        var data=@json($data);
    </script>
    <script src="{{asset('Custom_js/Backoffice/Product/insertProduct.js')}}?v={{time()}}"></script>
@endsection