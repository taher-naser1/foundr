listenClick('.delete-prescription-btn', function (event) {
    let prescriptionId = $(event.currentTarget).attr('data-id');
    deleteItem(route("prescriptions.destroy",prescriptionId) ,
        $('#prescriptionLang').val());
});

listenChange('.prescriptionStatus', function (event) {
    let prescriptionId = $(event.currentTarget).attr('data-id');
    prescriptionUpdateStatus(prescriptionId);
});

function prescriptionUpdateStatus(id) {
    $.ajax({
        url: $('#indexPrescriptionUrl').val() + '/' + +id + '/active-deactive',
        method: 'post',
        cache: false,
        success: function (result) {
            if (result.success) {
                displaySuccessMessage(result.message);
                hideDropdownManually($('#prescriptionFilterBtn'), $('#prescriptionFilter'));
            }
        },
    });
}

listenClick('#prescriptionResetFilter', function () {
    $('#prescriptionHead').val('2').trigger('change');
    hideDropdownManually($('#prescriptionFilterBtn'), $('.dropdown-menu'));
});


listenChange('#prescriptionHead', function () {
    window.livewire.emit('changeFilter', 'statusFilter', $(this).val())
});
listenChange('#prescriptionHead', function () {
    window.livewire.emit('changeFilter', 'statusFilter', $(this).val())
});
