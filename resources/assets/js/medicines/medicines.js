document.addEventListener("turbo:load", loadMedicineCreateData);

("use strict");

function loadMedicineCreateData() {
    $('#medicineCategoryId,#medicineBrandId').select2({
        width:'100%',
    });
    listenClick(".showMedicineBtn", function (event) {
        event.preventDefault();
        let medicineId = $(event.currentTarget).attr("data-id");
        renderMedicineData(medicineId);
    });

    function renderMedicineData(id) {
        $.ajax({
            url: route("medicines.show.modal", id),
            type: "GET",
            success: function (result) {
                if (result.success) {
                    $("#showMedicineName").text(result.data.name);
                    $("#showMedicineBrand").text(result.data.brand_name);
                    $("#showMedicineCategory").text(result.data.category_name);
                    $("#showMedicineSaltComposition").text(
                        result.data.salt_composition
                    );
                    $("#showMedicineSellingPrice").text(
                        result.data.selling_price
                    );
                    $("#showMedicineBuyingPrice").text(
                        result.data.buying_price
                    );
                    $("#showMedicineQuanity").text(
                        addCommas(result.data.quantity)
                    );
                    $("#showMedicineAvailableQuanity").text(
                        addCommas(result.data.available_quantity)
                    );
                    $("#showMedicineSideEffects").text(
                        result.data.side_effects
                    );
                    moment.locale($("#medicineLanguage").val());
                    let createDate = moment(result.data.created_at);
                    $("#showMedicineCreatedOn").text(createDate.fromNow());
                    $("#showMedicineUpdatedOn").text(
                        moment(result.data.updated_at).fromNow()
                    );
                    $("#showMedicineDescription").text(result.data.description);
                    setValueOfEmptySpan();
                    $("#showMedicine").appendTo("body").modal("show");
                }
            },
            error: function (result) {
                displayErrorMessage(result.responseJSON.message);
            },
        });
    }
}

listenClick(".deleteMedicineBtn", function (event) {
    let id = $(event.currentTarget).attr("data-id");
    medicineDeleteItem(
        route("check.use.medicine", id),
        Lang.get("messages.medicine.medicine")
    );
});

window.medicineDeleteItem = function (url, header) {
    var tableId = null;
    var callFunction = null;
    $.ajax({
        url: url,
        type: "GET",
        success: function (result) {
            if (result.success) {
                let popUpText =
                    result.data.result == true
                        ? "This medicine is already used in medicine bills, are you sure want to delete it?"
                        : $(".confirmVariable").val() + header + "?";
                swal({
                    title: Lang.get('messages.common.deleted'),
                    text: popUpText,
                    icon: 'warning',
                    buttons: {
                        confirm: Lang.get('messages.common.yes'),
                        cancel: Lang.get('messages.common.no'),
                    },
                }).then((popResult) => {
                    if (popResult) {
                        deleteMedicineAjax(
                            $("#indexMedicineUrl").val() + "/" + result.data.id,
                            (tableId = null),
                            header,
                            (callFunction = null)
                        );
                    }
                });
            }
        },
        error: function (result) {
            displayErrorMessage(result.responseJSON.message);
        },
    });
};

function deleteMedicineAjax(url, tableId = null, header, callFunction = null) {
    $.ajax({
        url: url,
        type: "DELETE",
        dataType: "json",
        success: function (obj) {
            if (obj.success && obj.data) {
                swal({
                    title: obj.message,
                    text: $('.confirmVariable').val() + header + "?",
                    icon: sweetAlertIcon,
                    timer: 3000,
                    buttons: {
                        confirm: Lang.get('messages.common.yes'),
                        cancel: Lang.get('messages.common.no'),
                    },
                }).then((result) => {
                    if (result) {
                        $.ajax({
                            url: url,
                            type: "DELETE",
                            dataType: "json",
                            data: { canDeleteCheck: "yes" },
                            success: function (obj) {},
                            error: function (data) {
                                swal({
                                    title: "",
                                    text: data.responseJSON.message,
                                    confirmButtonColor: "#009ef7",
                                    icon: "error",
                                    timer: 5000,
                                    buttons: {
                                        confirm: Lang.get('messages.prescription.ok'),
                                    },
                                });
                            },
                        });
                    }
                });
            }
            if (obj.success && !obj.data) {
                Livewire.emit("resetPage");
                swal({
                    icon: "success",
                    title: Lang.get('messages.common.deleted'),
                    confirmButtonColor: "#f62947",
                    text: header + " " + Lang.get('messages.common.has_been'),
                    timer: 2000,
                    buttons: {
                        confirm: Lang.get('messages.prescription.ok'),
                    },
                });
                if (callFunction) {
                    eval(callFunction);
                }
            }
        },
        error: function (data) {
            swal({
                title: "",
                text: data.responseJSON.message,
                confirmButtonColor: "#009ef7",
                icon: "error",
                timer: 5000,
                buttons: {
                    confirm: Lang.get('messages.prescription.ok'),
                },
            });
        },
    });
}
