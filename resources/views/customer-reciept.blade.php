<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script>
        var node = document.getElementById('my-node');
        var btn = document.getElementById('foo');
        btn.onclick = function() {
            node.innerHTML = "I'm an image now."
            domtoimage.toBlob(document.getElementById('my-node'))
                .then(function(blob) {
                    window.saveAs(blob, 'my-node.png');
                });
        }
    </script>
    <style>
        /* 61C193 */
        body {
            background-color: #f6f6f6;
            font-family: sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 14px;
            /* line-height: 1.4; */
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        .content {
            box-sizing: border-box;
            display: block;
            max-width: 500px;
            margin: auto;
            background: white;
            /* padding: 20px; */

            padding-bottom: 20px;
            border-radius: 10px;
        }

        .wrapper {
            margin-top: 50px;
            margin-bottom: 50px;
        }

        .header {

            background-image: linear-gradient(to right, #619fc1, #015081);
            display: flex;
            justify-content: space-between;
            border-top-right-radius: 10px;
            border-top-left-radius: 10px;
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 30px;
            padding-right: 12px;
        }

        .header h3 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 500;
        }

        .header h5 {
            color: #ffffff;
            font-size: 12px;
            padding-top: 6px;
        }

        .info-details {
            padding-left: 30px;
            padding-right: 30px;
        }

        .mail-body {
            display: flex;
            justify-content: space-between;
            /* line-height: 0.5cm; */
        }

        .mail-body h4 {
            font-size: 12px;
        }

        .mail-body h6 {
            font-size: 10px;
            color: #979797;
        }

        hr {
            background-color: #DADADA;
            margin-left: 30px;
            margin-right: 30px;
            /* width: 80%; */
        }

        h3,
        h4,
        h5,
        h6 {
            line-height: 0px;
        }

        .payment-section {
            padding-top: 5px;
            padding-left: 30px;
            padding-right: 30px;
        }

        .payment {
            display: flex;
            justify-content: space-between;

        }

        .payment p {
            color: #979797;
            font-size: 14px;
        }

        .payment h2 {
            color: #979797;
            font-size: 16px;
        }

        .other-details {
            padding-top: 5px;
            padding-left: 30px;
            padding-right: 30px;
        }

        .other-details h3 {
            padding-bottom: 12px;
        }

        .other-details h5 {
            color: #279460;
            padding-top: 5px;
        }

        .other-details p {
            text-align: center;
            color: #FF0000;
            font-size: 12px;
            padding-top: 5px;
        }

        .total {
            display: flex;
            justify-content: space-between;
            padding-left: 30px;
            padding-right: 30px;
        }

        .total .amount {
            color: #00A154;
            font-weight: 700;
        }

        img {
            border: none;
            -ms-interpolation-mode: bicubic;
            max-width: 100%;
            display: block;
            margin-left: auto;
            margin-right: auto;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div>

    </div>

    <div class="wrapper" id="my-node">
        <div class="content" style="border: 1px solid #979797;">
            <div class="header">
                <div>
                    <h3>Customer Receipt</h3>
                    <h5>Your payment has been confirmed</h5>
                </div>
                <div class="img-logo">
                    <div>
                        <img src="https://www.mygupta.co/gupta.jpeg" alt="">
                    </div>
                    <!-- <div>
                        <img src="https://afriproedu.com/images/reciept/download-icon.png" alt="">
                    </div> -->


                </div>
            </div>
            <div class="info-details">
                <div class="mail-body">
                    <h4>Name : {{$details['custname']}}</h4>
                    <!-- <span style="padding-top: 12px;color: #979797;padding-left:4px">Omolade Samuel</span> -->
                </div>
                <div class="mail-body">
                    <h4>Paid For :{{$details['pay_for']}}</h4>
                    <!-- <span style="padding-top: 12px;color: #979797;padding-left:4px">#AFR3456789</span> -->
                </div>
                <div class="mail-body">
                    <h4>Vendor Contact : {{$details['vendor_contact']}}</h4>
                    <!-- <span style="padding-top: 12px;color: #979797;padding-left:4px">samuel@gmail.com</span> -->
                </div>

                <div class="mail-body">
                    <h4>No. of Items : {{$details['quantity']}}</h4>
                    <!-- <span style="padding-top: 12px;color: #979797;padding-left:4px">samuel@gmail.com</span> -->
                </div>

                <div class="mail-body">
                    <h4>Amount Paid : {{$details['amount']}} NGN</h4>
                    <!-- <span style="padding-top: 12px;color: #979797;padding-left:4px">samuel@gmail.com</span> -->
                </div>
            </div>

            <hr />
            <!-- <div class="payment-section">
                <h3>Payment Details
                </h3>
                <div class="payment">
                    <p>AfriProEdu Service Charge for <br />
                        Takk Practical Nursing Examination</p>
                    <h2>$100.00</h2>
                </div>


            </div> -->
            <!-- <hr /> -->
            <!-- <div class="other-details">
                <h3>Other Details</h3>
                <h5>Exam Date : 25th November 2023 </h5>


                <h5>Exam Time : 11 : 00am </h5>
                <h5>Exam Venue : Vantage Hub 5th Floor,</h5>
                <h5> Mosesola House, 103
                    Allen Avenue, Ikeja, Lagos</h5>
                <p>Note : Come along with your government approved ID Card or NIN slip</p>
            </div>
            <hr /> -->
        </div>
    </div>


 <!-- START FOOTER -->
 <div class="footer">
        <p>Sent by Gupta © 2024. All Rights Reserved.</p>
    </div>
    <!-- END FOOTER -->
</body>

</html>