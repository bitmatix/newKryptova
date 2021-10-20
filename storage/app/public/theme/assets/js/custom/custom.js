$("body").on("click", ".rateAgree", function () {
    var id = $(this).data("id");
    var message = "NULL";
    if (id == "3") {
        $("#is_rate_reason").trigger("click");
        $(".bd-example-modal-lg").modal("hide");
    } else {
        $.ajax({
            url: "mid-rate-agree",
            type: "POST",
            data: { id: id, message: message, _token: CSRF_TOKEN },
            beforeSend: function () {
                $(this).attr("disabled", "disabled");
                $(this).html(
                    '<i class="fa fa-spinner fa-spin"></i>  Please Wait...'
                );
            },
            success: function (data) {
                if (data.success == "1") {
                    toastr.success("Submited successfully!");
                    setInterval(function () {
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error("Something went wrong.");
                }
            },
        });
    }
});

$("body").on("click", ".rateAgreeReason", function () {
    var id = "3";
    var message = $("textarea#reclineReason").val();

    if (message == "") {
        toastr.error("Please Enter Decline Reason");
    } else {
        $.ajax({
            url: "mid-rate-agree",
            type: "POST",
            data: { id: id, message: message, _token: CSRF_TOKEN },
            beforeSend: function () {
                $(this).attr("disabled", "disabled");
                $(this).html(
                    '<i class="fa fa-spinner fa-spin"></i>  Please Wait...'
                );
            },
            success: function (data) {
                if (data.success == "1") {
                    toastr.success("Submited successfully!");
                    setInterval(function () {
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error("Something went wrong.");
                }
            },
        });
    }
});

$("body").on("click", ".rateAgreeReasonBack", function () {
    $("#is_rate").trigger("click");
    $(".bd-example-modal-lg1").modal("hide");
});

$(".select2").select2({});

$("#checkAll").on("change", function () {
    $("td input:checkbox, .custom-checkbox input:checkbox").prop(
        "checked",
        $(this).prop("checked")
    );
});