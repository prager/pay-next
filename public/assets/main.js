function set_pay(memb, carr) {
  var mem_val = memb;
  var tot = 0;
  var don = 0;
  var rep = 0;
  if (document.getElementById("student").checked) {
    memb = 15;
  }
  if (document.getElementById("mem").checked == true) {
    document.getElementById("memamount").defaultValue = "$" + memb.toFixed(2);
  } else {
    document.getElementById("memamount").defaultValue = "$0.00";
    if (memb > mem_val - 1) memb -= mem_val;
  }

  if (document.getElementById("carrier").checked == true) {
    document.getElementById("carramnt").defaultValue = "$" + carr.toFixed(2);
  } else {
    document.getElementById("carramnt").defaultValue = "$0.00";
    if (carr > carr - 1) carr -= carr;
  }

  if (document.getElementById("donation").checked == true) {
    var donStr = document.getElementById("donamnt").value;
    var dolSign = donStr.substring(0, 1);
    if (dolSign == "$") {
      don = parseFloat(donStr.substring(1, donStr.length));
    } else {
      don = parseFloat(donStr);
    }
    if (parseFloat(don) < 5) {
      alert("Donation must be at least $5.00");
      document.getElementById("donation").checked = false;
      document.getElementById("donamnt").defaultValue = "$0.00";
      don = 0;
    }
  }

  if (document.getElementById("don_rep").checked == true) {
    var repStr = document.getElementById("repamnt").value;
    var dolSign = repStr.substring(0, 1);
    if (dolSign == "$") {
      rep = parseFloat(repStr.substring(1, repStr.length));
    } else {
      rep = parseFloat(repStr);
    }
    if (parseFloat(rep) < 5) {
      alert("Donation must be at least $5.00");
      document.getElementById("don_rep").checked = false;
      document.getElementById("repamnt").defaultValue = "$0.00";
      rep = 0;
    }
  }

  if (document.getElementById("donation").checked == false) {
    document.getElementById("donamnt").defaultValue = "$0.00";
    don = 0;
  }

  if (document.getElementById("don_rep").checked == false) {
    document.getElementById("repamnt").defaultValue = "$0.00";
    rep = 0;
  }

  tot = memb + carr + don + rep;

  document.getElementById("proc_total").value = tot;
  document.getElementById("don_val").value = don;
  document.getElementById("mem_val").value = memb;
  document.getElementById("car_val").value = carr;
  document.getElementById("tot_btn").textContent = "$" + tot.toFixed(2);
}

function en_check() {
  document.getElementById("donation").disabled = false;
}

function en_check_rep() {
  document.getElementById("don_rep").disabled = false;
}

$(function () {
  var $form = $(".require-validation");
  $("form.require-validation").bind("submit", function (e) {
    var $form = $(".require-validation"),
      inputSelector = [
        "input[type=email]",
        "input[type=password]",
        "input[type=text]",
        "input[type=file]",
        "textarea",
      ].join(", "),
      $inputs = $form.find(".required").find(inputSelector),
      $errorMessage = $form.find("div.error"),
      valid = true;
    $errorMessage.addClass("d-none");
    $(".is-invalid").removeClass("is-invalid");
    $inputs.each(function (i, el) {
      var $input = $(el);
      if ($input.val() === "") {
        $input.addClass("is-invalid");
        $errorMessage.removeClass("d-none");
        e.preventDefault();
      }
    });

    if (!$form.data("cc-on-file")) {
      e.preventDefault();
      Stripe.setPublishableKey($form.data("stripe-publishable-key"));
      Stripe.createToken(
        {
          number: $(".card-number").val(),
          cvc: $(".card-cvc").val(),
          exp_month: $(".card-expiry-month").val(),
          exp_year: $(".card-expiry-year").val(),
        },
        stripeResponseHandler
      );
    }
  });

  function stripeResponseHandler(status, response) {
    if (response.error) {
      $(".error")
        .removeClass("d-none")
        .find(".alert")
        .text(response.error.message);
    } else {
      var token = response["id"];
      $form.find("input[type=text]").empty();
      $form.find("input[type=email]").empty();
      $form.append(
        "<input type='hidden' name='stripeToken' value='" + token + "'/>"
      );
      $form.get(0).submit();
    }
  }
});
