@if (Route::is(['category']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_category" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Category</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Category Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="category_name"
                                            placeholder="Enter Category Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn btn-save">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade contentmodal" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content doctor-profile">
                <div class="modal-header pb-0 border-bottom-0  justify-content-end">
                    <button type="button" class="close-btn" data-bs-dismiss="modal" aria-label="Close"><i
                            class="feather-x-circle"></i></button>
                </div>
                <div class="modal-body">
                    <div class="delete-wrap text-center">
                        <div class="del-icon"><i class="feather-x-circle"></i></div>
                        <h2>Sure you want to delete</h2>
                        <div class="submit-section">
                            <a href="javascript:void(0);" data-bs-dismiss="modal"
                                class="btn btn-success me-2">Yes</a>
                            <a href="#" class="btn btn-danger" data-bs-dismiss="modal">No</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Modal -->
@endif

@if (Route::is(['unit']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_unit" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Unit</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Unit Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_name"
                                            placeholder="Enter Unit Name">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <div class="input-block mb-3">
                                        <label>Short Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="unit_short_name"
                                            placeholder="Enter Short Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn btn-save">Add Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade contentmodal" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content doctor-profile">
                <div class="modal-header pb-0 border-bottom-0  justify-content-end">
                    <button type="button" class="close-btn" data-bs-dismiss="modal" aria-label="Close"><i
                            class="feather-x-circle"></i></button>
                </div>
                <div class="modal-body">
                    <div class="delete-wrap text-center">
                        <div class="del-icon"><i class="feather-x-circle"></i></div>
                        <h2>Sure you want to delete</h2>
                        <div class="submit-section">
                            <a href="javascript:void(0);" data-bs-dismiss="modal"
                                class="btn btn-success me-2">Yes</a>
                            <a href="#" class="btn btn-danger" data-bs-dismiss="modal">No</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Modal -->
@endif

@if (Route::is(['variant']))
    <!-- modal -->
    <div class="modal modal-lg custom-modal fade" id="add_variant" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div class="form-header modal-header-title  text-start mb-0">
                        <h4 class="mb-0 modal-title">Add Variant</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <form action="#">
                    <div class="modal-body">
                        <div class="form-groups-item border-0 pb-0">
                            <div class="row">
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Name<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control fill" id="variant_name"
                                            placeholder="Enter Variant Name">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="input-block mb-3">
                                        <label>Variant<span class="text-danger">*</span></label>
                                        <select class="form-control tagging fill" id="variant_attribute" multiple="multiple">
											
										</select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal"
                            class="btn btn-back cancel-btn me-2">Cancel</button>
                        <button type="submit" data-bs-dismiss="modal"
                            class="btn btn-primary paid-continue-btn btn-save">Add Variant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade contentmodal" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content doctor-profile">
                <div class="modal-header pb-0 border-bottom-0  justify-content-end">
                    <button type="button" class="close-btn" data-bs-dismiss="modal" aria-label="Close"><i
                            class="feather-x-circle"></i></button>
                </div>
                <div class="modal-body">
                    <div class="delete-wrap text-center">
                        <div class="del-icon"><i class="feather-x-circle"></i></div>
                        <h2>Sure you want to delete</h2>
                        <div class="submit-section">
                            <a href="javascript:void(0);" data-bs-dismiss="modal"
                                class="btn btn-success me-2">Yes</a>
                            <a href="#" class="btn btn-danger" data-bs-dismiss="modal">No</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Modal -->
@endif