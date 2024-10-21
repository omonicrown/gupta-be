<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    body {
        background-color: #ffffff;
        font-family: sans-serif;
        -webkit-font-smoothing: antialiased;
        font-size: 14px;
        /* line-height: 1.4; */
        margin: 0;
        padding: 0;
        -ms-text-size-adjust: 100%;
        -webkit-text-size-adjust: 100%;
    }

    .wrapper {
        background-color: #F2F5F8;
        margin: auto;
        margin-top: 50px;
        margin-bottom: 40px;
        width: 640px;
        padding-top: 15px;
        padding-bottom: 15px;
        padding-left: 32px;
        padding-right: 32px;

    }

    .content {
        background-color: white;
        width: 576px;
        margin: auto;
        margin-top: 20px;
        padding: 32px;
    }

    .header h3 {
        font-size: 16px;
        color: #333333;
    }

    .text-content {
        margin-top: 20px;
    }

    .text-content p {
        font-size: 14px;
        color: #333333;
    }

    .button {
        border: none;
        background-color: #0071BC;
        color: #ffffff;
        padding: 12px 22px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        border-radius: 20px;
    }

    .footer {
        padding: 32px;
    }

    .footer p {
        font-size: 14px;
        color: #333333;
    }

    .footer-end {
        display: flex;
        justify-content: space-between;

    }

    .footer-end h3 {
        color: #6A7C94;
        font-style: italic;

    }

    .footer-social img {
        padding-left: 10px;
        margin-top: 8px;
    }

    .logo img {
        text-align: center;
        position: relative;
        display: flex;
        justify: center;
        background-color: #3F83F8;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        padding-right: 10px;
    }
</style>

<body>
    <div class="logo">
        <span style="display:flex;justify:center;text-align:center;">
            <img src="https://www.mygupta.co/gt3.png" />
        </span>

    </div>
    <div class="wrapper">

        <div class="content">

            <div class="header">
                <h3> Dear {{$details['custname']}} 🤗, </h3>
                <!-- <p> We hope this email finds you well.</p> -->
                <p> Let’s kick off the week with fresh energy and renewed focus. Remember, every new day is a chance to
                    grow your business, and Gupta is here to make that easier for you.</p>

                <p> Whether it’s creating custom WhatsApp links, setting up your mini store, or managing customer
                    interactions, Gupta has the tools to help you boost sales and keep things running smoothly.</p>

                <p>With Gupta's paid subscriptions, you can unlock even more powerful features to boost your sales and
                    reach more customers. From unlimited custom links to detailed analytics and personalized mini
                    websites, our paid plans give you everything you need to stand out and grow faster.</p>
                
                    <p>Visit www.mygupta.co to get the week started!</p>

                <p>If you need assistance or have any questions, feel free to contact our support team at
                    hello@mygupta.co.</p>
            </div>
            <div class="text-content">


                <p>Best regards,</p>
            </div>
        </div>



        <div class="footer">
            <p>Sent by Gupta © 2024. All Rights Reserved.</p>
        </div>
        <div class="footer-end">
            <div>
                <h3>Gupta</h3>
            </div>
            <div class="footer-social">
                <img src="https://www.mygupta.co/twitter.jpeg" />
                <img src="https://www.mygupta.co/facebook.jpeg" />
                <img src="https://www.mygupta.co/linkedin.jpeg" />
            </div>
        </div>
    </div>
</body>

</html>