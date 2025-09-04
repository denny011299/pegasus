$(document).on('click', '.btnAdd', function(){
    mode=1;
    $('#addProduction .modal-title').html("Create Production");
    $('#addProduction input').val("");
    $('.is-invalid').removeClass('is-invalid');
    $('#addProduction').modal("show");
})