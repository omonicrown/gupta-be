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

                <img src="https://www.mygupta.co/image.png" alt="https://afriproedu.com/logo192.png" class="img" />
                <!-- <div class="centered">Welcome to AfriProEdu:<br/> Your Gateway to a Bright Future in Finland!</div> -->
            </div>

            <h1>Dear {{$details['custname']}},</h1>


            <p> Welcome to Gupta, </p>
            <p> Your ultimate SaaS platform for optimizing WhatsApp communication! We're thrilled to have you on board,
                and we can't wait for you to experience the power of Gupta in enhancing your business interactions on
                WhatsApp.</p>

            <!-- <p>My name is Treasure and I'll be your personal study support counsellor to help guide you on questions that you may have during your study abroad journey or if you need any help while using the platform. I am excited that you have taken the first step in your study dream, and I can't wait to commence this journey with you.</p> -->
            <!-- <p>Kindly follow the steps below to submit an application.</p> -->

            <p>
                <span style="font-weight: 500;">Here's a brief overview of what Gupta has to offer: </span><br /><br />
                <span>- <b>Customized WhatsApp Links:</b> Generate personalized links for direct customer
                    engagement.</span><br /><br />
                <span>- <b>Multilinks Management:</b>Simplify link sharing with consolidated multilinks for an improved
                    user experience.</span><br /><br />
                <span>- <b>Redirect Links: </b> Seamlessly guide users to any destination URL, enhancing your online
                    presence.</span><br /><br />
                <span>- <b>Mini Store:</b> A pivotal component, enabling vendors to create market links with custom URLs representing their shops. Subscription plans dictate customization options. Vendors populate these market links with products, fostering a seamless shopping experience.</span><br /><br />
                <span>- <b>Pay with Gupta:</b> Vendors seamlessly collect payments through Gupta payment links on product pages. The platform includes a wallet section for vendors to monitor customer payments and facilitate withdrawals.</span><br /><br />

                <!-- <span>- You get guaranteed job placement for work practise, then possible permanent contract</span><br /><br />
                <span><a href="https://afriproedu.com/course-details/2"> Click here for more information </a></span><br /><br /> -->

            </p>



            <p>
                <span style="font-weight: 500;">Getting started is easy: </span><br /><br />
                <span>- Sign up for a Gupta account and provide essential business information.</span><br /><br />
                <span>- Explore the intuitive dashboard to manage customized links, multilinks, redirects, and automated messages.</span><br /><br />

                
            </p>



            <!-- <p>_Note: your unique code is your means of identification and pass to the exam hall. Do not share this code with anyone. _</p> -->

            <p>Thank you for choosing Gupta! We're excited to be your partner in revolutionizing WhatsApp business communication.
                <br />
                Sincerely,<br />
                The Gupta Team
            </p>


            <!-- <div class="btn">
                <button class="button button1">Call To Action</button>
            </div> -->

        </div>
    </div>
    <!-- START FOOTER -->
    <div class="footer">
        <p>Sent by Gupta © 2024. All Rights Reserved.</p>
    </div>
    <!-- END FOOTER -->


</body>

</html>