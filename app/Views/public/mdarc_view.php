<!-- Update v3 -->
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MDARC Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="shortcut icon" href="/assets/img/mdarc-icon.ico" type="image/x-icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
</head>
<body class="bg-light">
    <nav class="bg-white border-bottom" aria-label="breadcrumb">
        <div class="container">
            <ol class="breadcrumb justify-content-center py-2 mb-0">
                <li class="breadcrumb-item active" aria-current="page">
                    <a href="/index.php/mdarc">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="https://www.mdarc.org" target="_blank" rel="noopener">MDARC</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="https://mdarc-dev.jlkconsulting.info" target="_blank" rel="noopener">Member Portal</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/index.php/about" target="_blank" rel="noopener">About</a>
                </li>
            </ol>
        </div>
    </nav>

    <main class="container py-4">
        <header class="text-center mb-4">
            <h1 class="h2 mb-1">MDARC Payments</h1>
            <span class="badge text-bg-warning">Test Mode</span>
        </header>

        <section class="row">
            <div class="col-md-8 col-lg-6 mx-auto">
                <div class="card credit-card-box shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                            <h2 class="h5 card-title mb-0">Payment Details</h2>
                            <img class="img-fluid" src="https://files.kulisek.org/cc.png" alt="Accepted credit cards">
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if (! empty($paymentError)): ?>
                            <div class="alert alert-danger" role="alert"><?= esc($paymentError) ?></div>
                        <?php endif; ?>

                        <?php if (! empty($paymentSuccess)): ?>
                            <div class="alert alert-success" role="alert"><?= esc($paymentSuccess) ?></div>
                        <?php endif; ?>

                        <form action="/index.php/mdarc-post" method="post" class="require-validation" data-cc-on-file="false" data-stripe-publishable-key="<?= esc($stripeKey ?? '') ?>" id="payment-form">
                            <input type="hidden" id="mem_val" name="mem_val" value="45">

                            <div class="mb-3 name required">
                                <label class="form-label" for="cc_name">Name on Card</label>
                                <input class="form-control" type="text" id="cc_name" name="cc_name" autocomplete="cc-name">
                            </div>

                            <div class="row">
                                <div class="col-sm-6 mb-3 required">
                                    <label class="form-label" for="email">Your MDARC Listed Email</label>
                                    <input class="form-control" type="email" id="email" name="email" autocomplete="email">
                                </div>
                                <div class="col-sm-6 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="student" name="student" value="studentval" onchange="set_pay(45, 18)">
                                        <label class="form-check-label" for="student">Student in MDARC records?</label>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <p class="text-center text-warning-emphasis fw-semibold mb-3">
                                If amount is to be processed then checkbox must be checked.
                            </p>

                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="mem" name="mem" value="mem" onclick="set_pay(45, 18)" checked>
                                        <label class="form-check-label" for="mem">Membership</label>
                                    </div>
                                    <input class="form-control" type="text" id="memamount" name="memamount" value="$45.00" disabled>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="carrier" name="carrier" value="carrier" onclick="set_pay(45, 18)">
                                        <label class="form-check-label" for="carrier">The Carrier ($18.00)</label>
                                    </div>
                                    <input class="form-control" type="text" id="carramnt" name="carramnt" value="$0.00" disabled>
                                </div>
                            </div>

                            <p class="small text-body-secondary mb-3">The $18.00 is for The Carrier hardcopy via USPS.</p>

                            <div class="row">
                                <div class="col-sm-6 mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="donation" name="donation" value="donation" onclick="set_pay(45, 18)" disabled>
                                        <label class="form-check-label" for="donation">Donation MDARC</label>
                                    </div>
                                    <input class="form-control" type="text" id="donamnt" name="donamnt" value="$0.00" onclick="en_check()" onfocus="this.select();">
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="don_rep" name="don_rep" value="don_rep" onclick="set_pay(45, 18)" disabled>
                                        <label class="form-check-label" for="don_rep">Donation Repeater</label>
                                    </div>
                                    <input class="form-control" type="text" id="repamnt" name="repamnt" value="$0.00" onclick="en_check_rep()" onfocus="this.select();">
                                </div>
                            </div>

                            <div class="mb-3 card required">
                                <label class="form-label" for="card-number">Card Number</label>
                                <input class="form-control card-number" type="text" id="card-number" autocomplete="off">
                            </div>

                            <div class="row">
                                <div class="col-12 col-md-4 mb-3 cvc required">
                                    <label class="form-label" for="card-cvc">CVC</label>
                                    <input class="form-control card-cvc" placeholder="ex. 311" type="text" id="card-cvc" autocomplete="off">
                                </div>
                                <div class="col-12 col-md-4 mb-3 expiration required">
                                    <label class="form-label" for="card-expiry-month">Expiration Month</label>
                                    <input class="form-control card-expiry-month" placeholder="MM" type="text" id="card-expiry-month">
                                </div>
                                <div class="col-12 col-md-4 mb-3 expiration required">
                                    <label class="form-label" for="card-expiry-year">Expiration Year</label>
                                    <input class="form-control card-expiry-year" placeholder="YYYY" type="text" id="card-expiry-year">
                                    <input type="hidden" id="proc_total" name="proc_total" value="45">
                                    <input type="hidden" id="don_val" name="don_val" value="">
                                    <input type="hidden" id="don_rep_val" name="don_rep_val" value="">
                                    <input type="hidden" id="car_val" name="car_val" value="18">
                                </div>
                            </div>

                            <div class="error mb-3 d-none">
                                <div class="alert alert-danger">Please correct the errors and try again.</div>
                            </div>

                            <hr>

                            <div class="form-check text-center mb-3">
                                <input class="form-check-input float-none me-1" type="checkbox" id="ensubmit" name="ensubmit" value="ensubmit" onchange="document.getElementById('btnsubmit').disabled = !this.checked">
                                <label class="form-check-label" for="ensubmit">
                                    Check the total and agree with <a href="/index.php/terms" target="_blank" rel="noopener">Terms and Conditions</a> to submit
                                </label>
                            </div>

                            <button id="btnsubmit" class="btn btn-primary btn-lg w-100" type="submit" disabled>
                                Pay Now Total = <span id="tot_btn">$45.00</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="container pb-4">
        <div class="row">
            <div class="col-sm-10 mx-auto">
                <hr>
            </div>
        </div>
        <div class="row gy-2">
            <div class="col-sm-4 offset-md-2">
                &copy; <a href="https://jlkconsulting.info" target="_blank" rel="noopener">JLK Consulting</a>
            </div>
            <div class="col-sm-4 text-sm-end">
                <a href="https://stripe.com/docs/testing#cards" target="_blank" rel="noopener" class="text-decoration-none">Testing Mode</a>
                <span aria-hidden="true">|</span>
                <a href="/index.php/terms" target="_blank" rel="noopener">Terms &amp; Conditions</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://js.stripe.com/v2/"></script>
    <script src="/assets/main.js?v=20260613-1"></script>
</body>
</html>
