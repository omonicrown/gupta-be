<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            background-color: #f6f6f6;
            font-family: sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        img {
            border: none;
            -ms-interpolation-mode: bicubic;
            max-width: 40%;
            max-height: 50%;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        p {
            text-align: justify;
            text-justify: inter-word;
            color: #707070;
        }

        .content {
            box-sizing: border-box;
            display: block;
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
        }

        .wrapper {
            margin-top: 50px;
        }

        .img-container {
            position: relative;
            text-align: center;
            color: white;
        }

        .centered {
            position: absolute;
            top: 30%;
            left: 30%;


        }

        .button {
            background-color: #1DB459;
            /* Green */
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
            margin: 4px 2px;
            transition-duration: 0.4s;
            cursor: pointer;
        }

        .btn {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        h1 {
            font-size: 20px;
            color: #1B212F;
            /* text-align: center; */
        }

        .card {
            background-color: #1DB459;
            color: white;
            text-align: center;
            display: flex;
            justify-content: center;
            margin: 6px;
            height: 170px;
            border-radius: 5px;
        }

        .footer {
            clear: both;
            margin-top: 30px;
            text-align: center;
            width: 100%;
        }

        .footer p,
        .footer a {
            color: #999999;
            font-size: 14px;
            text-align: center;
        }

        /* -------------------------------------
          RESPONSIVE AND MOBILE FRIENDLY STYLES
      ------------------------------------- */
        @media only screen and (max-width: 620px) {
            h1 {
                font-size: 16px;

            }

            .content {
                margin-left: 10px;
                margin-right: 10px;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="content">
            <!-- <img src="www.afriproedu.com/images/logo.svg" alt="www.afriproedu.com/images/logo192.png"/> -->
            <!-- <div class="card">Design</div> -->
            <div class="img-container">

                <img src="https://afriproedu.com/logo192.png" alt="https://afriproedu.com/logo192.png" class="img" />
                <!-- <div class="centered">Welcome to AfriProEdu:<br/> Your Gateway to a Bright Future in Finland!</div> -->
            </div>

            <h1>Welcome {{$details['custname']}},</h1>


            <p> Thank you for choosing Afripay! </p>

            <p>Kindly click on the verification link below to proceed:</p>
            
            <p>https://goafripay.com/email-verify/{{$details['email']}}</p>
                <br />
                Sincerely,<br />
                The AfriPay Team
            </p>


            <!-- <div class="btn">
                <button class="button button1">Call To Action</button>
            </div> -->

        </div>
    </div>
    <!-- START FOOTER -->
    <div class="footer">
        <p>Sent by AfriProEdu © 2023. All Rights Reserved.</p>
    </div>
    <!-- END FOOTER -->


</body>

</html>