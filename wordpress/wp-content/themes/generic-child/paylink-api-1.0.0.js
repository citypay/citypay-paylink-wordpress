function Paylink(key, config) {
    "use strict";
    if (this.version = "1.0", this.key = key, void 0 === key)
        return void alert("No Paylink key specified, cannot continue...");
    var defaultConfig = {config: {expireIn: 86400}, test: !1, cart: {productInformation: "Paylink Order"}, cardholder: {}, form: {identifier: {placeholder: "N0000", label: "Invoice No", type: "text", required: !0, order: 10, pattern: ".{5,}"}, amount: {placeholder: "0.00", type: "number", required: !0, order: 20, label: "Amount"}}};
    this.config = $.extend(defaultConfig, config), this.utils = {digitsOnly: function (v) {
            return void 0 === v ? void 0 : v.replace(/\D/g, "")
        }, decorateFormEl: function (label, e) {
            return e.addClass("form-control"), $('<div class="form-group" />').append($('<label class="col-sm-2 control-label"/>').text(label)).attr("for", e.name).append($('<div class="col-sm-10" />').append(e))
        }}
}
Number.prototype.formatAmount = function (w, dec, thou) {
    "use strict";
    var c = isNaN(Math.abs(w)) ? 2 : Math.abs(w), d = void 0 === dec ? "." : dec, t = void 0 === thou ? "," : thou, n = Math.abs(+this || 0).toFixed(c), s = 0 > n ? "-" : "", i = parseInt(n, 10).toString(10), j = i.length;
    return j = j > 3 ? j % 3 : 0, s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "")
}, Paylink.prototype.billPayment = function (target, config) {
    "use strict";
    var e, k, el, paylink = this, c = {}, fields = [], f = {}, frm = $('<form role="form" id="billPaymentForm" class="form-horizontal" />'), b = $('<button class="btn btn-success" type="submit" id="billPaymentButton"><em class="glyphicon glyphicon-circle-arrow-right"></em> Pay</button>');
    $.extend(c, this.config), $.extend(f, this.config.form), config && config.form && ($.extend(f.amount, config.form.amount), $.extend(f.identifier, config.form.identifier), delete config.form.amount, delete config.form.identifier, $.extend(f, config.form)), delete c.form;
    for (k in f)
        f.hasOwnProperty(k) && (f[k].name = k, fields.push(f[k]));
    for (fields.sort(function (a, b) {
        return void 0 === a.order && (a.order = 0), void 0 === b.order && (b.order = 0), a.order === b.order ? a.label > b.label ? 1 : -1 : a.order - b.order
    }), k = 0; k < fields.length; k++)
        el = fields[k], e = $("<input/>").attr("type", void 0 === el.type ? "text" : el.type).attr("required", el.required).attr("name", el.name).attr("pattern", el.pattern).attr("placeholder", el.placeholder), frm.append(this.utils.decorateFormEl(el.label, e));
    return frm.append($('<div class="form-group"/>').append($('<div class="col-sm-offset-2 col-sm-1"/>').append(b)).append('<div class="col-sm-9"><div id="msg" class="hidden alert alert-warning" /></div>')), frm.find('input[name="amount"]').on("blur", function () {
        var am = parseFloat($(this).val(), 10).formatAmount();
        $(this).val(am)
    }), $(target).append(frm), $(frm).submit(function () {
        var n, msg = function (str) {
            var m = $(target).find("#msg");
            m.html(str), m.removeClass("hidden")
        };
        try {
            for ($("#billPaymentButton").prop("disable", "true").find("em").removeClass("glyphicon-circle-arrow-right").addClass("rotate glyphicon-refresh"), k = 0; k < fields.length; k++)
                n = fields[k].name, "amount" !== n && (c[n] = frm.find("input[name='" + n + "']").val());
            c.amount = paylink.utils.digitsOnly(frm.find("input[name='amount']").val()), c.clientVersion = "PaylinkJS_" + paylink.version, c.key = paylink.key, $.ajax({type: "POST", url: "https://secure.citypay.com/paylink3/create", data: JSON.stringify(c), contentType: "application/json; charset=utf-8", dataType: "json", success: function (data) {
                    if (1 === data.result)
                        document.location.href = data.url;
                    else if (0 === data.result) {
                        for (var i = 0, a = []; i < data.errors.length; i++)
                            a.push(data.errors[i].code + ": " + data.errors[i].msg);
                        msg("Unable to forward you for payment: <br/>" + a.join(", "))
                    } else
                        msg("Failed to submit, unrecognised response")
                }, failure: function () {
                    msg("Failure submitting form")
                }, always: function () {
                }})
        } catch (e) {
            console.log(e), msg(e)
        } finally {
            return $("#billPaymentButton").removeProp("disable").find("em").removeClass("rotate glyphicon-refresh").addClass("glyphicon-circle-arrow-right"), !1
        }
    }), Paylink
};